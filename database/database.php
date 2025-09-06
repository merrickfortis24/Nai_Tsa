<?php
 
class database {
    // Hostinger DB credentials
    private string $host = 'mysql.hostinger.com';                 // from hPanel
    private string $db   = 'u677397674_naitsa';            // MySQL Database
    private string $user = 'u677397674_naitsa_user';            // MySQL User
    private string $pass = 'Naitsa@123';                // set in hPanel

    private static ?PDO $pdo = null;

    public function opencon(): PDO {
        if (self::$pdo instanceof PDO) return self::$pdo;

        $dsn = "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4";
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // real prepares
            PDO::ATTR_PERSISTENT         => false,   // safer on shared hosting
        ];
        try {
            self::$pdo = new PDO($dsn, $this->user, $this->pass, $opts);
        } catch (PDOException $e) {
            error_log('DB connect error: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed.');
        }
        return self::$pdo;
    }

    // (Email verification columns ensured manually via SQL migration)

    function addCustomer($name, $email, $password) {
        $con = $this->opencon();
        $stmt = $con->prepare("INSERT INTO customer (Customer_Name, Customer_Email, Customer_Password) VALUES (?, ?, ?)");
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        return $stmt->execute([$name, $email, $hashed]);
    }

    function checkEmailExists($email) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT COUNT(*) FROM customer WHERE Customer_Email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    // New: Register customer with duplicate check
    function registerCustomer($name, $email, $password) {
        if ($this->checkEmailExists($email)) {
            return "Email already registered!";
        }
        $success = $this->addCustomer($name, $email, $password);
        if ($success) {
            return true;
        } else {
            return "Sign up failed. Please try again.";
        }
    }

    public function getUserByEmail($email, $account_type) {
        $con = $this->opencon();
        if ($account_type === 'admin') {
            $stmt = $con->prepare("SELECT * FROM admin WHERE Admin_Email = ?");
        } else {
            $stmt = $con->prepare("SELECT * FROM customer WHERE Customer_Email = ?");
        }
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getRecommendedProducts($customer_id, $limit = 4) {
        $con = $this->opencon();
        // Native MySQL prepares can't bind LIMIT with emulation off. Cast and inline.
        $limit = max(1, (int)$limit);

        $sql = "
            SELECT p.*
            FROM product p
            JOIN order_item oi ON p.Product_ID = oi.Product_ID
            JOIN orders o ON oi.Order_ID = o.Order_ID
            WHERE o.Customer_ID = ?
            GROUP BY p.Product_ID
            ORDER BY COUNT(*) DESC
            LIMIT $limit
        ";
        $stmt = $con->prepare($sql);
        $stmt->execute([(int)$customer_id]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$products) {
            $sql = "
                SELECT p.*
                FROM product p
                JOIN order_item oi ON p.Product_ID = oi.Product_ID
                GROUP BY p.Product_ID
                ORDER BY COUNT(*) DESC
                LIMIT $limit
            ";
            $stmt = $con->prepare($sql);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $products;
    }

    // Generate and store a verification token for a customer email
    public function issueVerificationToken($email) {
        $con = $this->opencon();
        $token = bin2hex(random_bytes(32));
        $stmt = $con->prepare("UPDATE customer SET verification_token = ?, verification_sent_at = NOW(), is_verified = 0 WHERE Customer_Email = ?");
        $stmt->execute([$token, $email]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        return $token;
    }

    // Verify account by token
    public function verifyByToken($token) {
        $con = $this->opencon();
        // Find customer by token
        $stmt = $con->prepare("SELECT Customer_ID FROM customer WHERE verification_token = ?");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        $cid = (int)$row['Customer_ID'];
        // Mark verified and clear token
        $upd = $con->prepare("UPDATE customer SET is_verified = 1, verified_at = NOW(), verification_token = NULL WHERE Customer_ID = ?");
        $upd->execute([$cid]);
        return $upd->rowCount() > 0;
    }

    // Issue a 6-digit OTP for email verification; stores a hash in verification_token and timestamp in verification_sent_at
    public function issueEmailOtp(string $email, int $digits = 6): array|false {
        $con = $this->opencon();
        // Generate numeric code
        $min = (int) pow(10, $digits - 1);
        $max = (int) pow(10, $digits) - 1;
        $code = (string) random_int($min, $max);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $stmt = $con->prepare("UPDATE customer SET verification_token = ?, verification_sent_at = NOW(), is_verified = 0 WHERE Customer_Email = ?");
        $stmt->execute([$hash, $email]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        return ['code' => $code, 'digits' => $digits];
    }

    // Verify a 6-digit OTP for the given email with TTL seconds (default 5 minutes)
    public function verifyEmailOtp(string $email, string $code, int $ttlSeconds = 300): bool {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Customer_ID, verification_token, verification_sent_at, is_verified FROM customer WHERE Customer_Email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        if ((int)($row['is_verified'] ?? 0) === 1) return true; // already verified
        $hash = $row['verification_token'] ?? null;
        $sentAt = $row['verification_sent_at'] ?? null;
        if (!$hash || !$sentAt) return false;
        // Check expiry
        $sentTs = strtotime($sentAt);
        if ($sentTs === false || ($sentTs + $ttlSeconds) < time()) {
            return false; // expired
        }
        if (!password_verify($code, $hash)) {
            return false;
        }
        // Mark verified and clear token
        $upd = $con->prepare("UPDATE customer SET is_verified = 1, verified_at = NOW(), verification_token = NULL WHERE Customer_ID = ?");
        $upd->execute([(int)$row['Customer_ID']]);
        return $upd->rowCount() > 0;
    }

    /* =================== Merged Admin / Product / Order / Payment Methods =================== */

    function addProduct($product_name, $product_desc, $category_id, $price_id, $admin_id, $image_name = '', $product_Allergens = '') {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("INSERT INTO product 
                (Product_Name, Product_desc, Product_allergens, Product_Image, Category_ID, Price_ID, Created_at, Updated_at, Admin_ID)
                VALUES (:name, :desc, :allergens, :image, :category, :price, NOW(), NOW(), :admin)");
            $stmt->execute([
                ':name' => $product_name,
                ':desc' => $product_desc,
                ':allergens' => $product_Allergens,
                ':image' => $image_name,
                ':category' => $category_id,
                ':price' => $price_id,
                ':admin' => $admin_id
            ]);
            return ['success' => true, 'message' => 'Product added successfully!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
        }
    }

    function getAllProducts($limit = 10, $offset = 0, $category_id = null) {
        $con = $this->opencon();
        $limit = (int)$limit; $offset = (int)$offset;
        $sql = "SELECT p.*, pp.Price_Amount, c.Category_Name, a.Admin_Name
                FROM product p
                LEFT JOIN product_price pp ON p.Price_ID = pp.Price_ID
                LEFT JOIN category c ON p.Category_ID = c.Category_ID
                LEFT JOIN admin a ON p.Admin_ID = a.Admin_ID";
        $params = [];
        if ($category_id) { $sql .= " WHERE p.Category_ID = ?"; $params[] = $category_id; }
        $sql .= " ORDER BY p.Created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $con->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getAllCategories() { $con = $this->opencon(); $stmt = $con->query("SELECT * FROM category ORDER BY Category_ID DESC"); return $stmt->fetchAll(PDO::FETCH_ASSOC); }

    function getAllPrices($onlyCurrent = false) {
        $con = $this->opencon();
        if ($onlyCurrent) {
            $today = date('Y-m-d');
            $stmt = $con->prepare("SELECT Price_ID, Price_Amount, Effective_From, Effective_To FROM product_price WHERE Effective_From <= :t AND (Effective_To IS NULL OR Effective_To >= :t) ORDER BY Price_ID ASC");
            $stmt->execute([':t'=>$today]);
        } else { $stmt = $con->query("SELECT Price_ID, Price_Amount, Effective_From, Effective_To FROM product_price ORDER BY Price_ID ASC"); }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function saveProduct($product_name, $product_desc, $category_id, $price_id, $admin_id, $image_name, $product_id = null, $product_allergens = '') {
        $con = $this->opencon();
        if ($product_id) {
            $stmt = $con->prepare("UPDATE product SET Product_Name=?, Product_desc=?, Product_allergens=?, Category_ID=?, Price_ID=?, Admin_ID=?, Product_Image=? WHERE Product_ID=?");
            $stmt->execute([$product_name,$product_desc,$product_allergens,$category_id,$price_id,$admin_id,$image_name,$product_id]);
        } else {
            $stmt = $con->prepare("INSERT INTO product (Product_Name, Product_desc, Product_allergens, Category_ID, Price_ID, Admin_ID, Product_Image) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$product_name,$product_desc,$product_allergens,$category_id,$price_id,$admin_id,$image_name]);
        }
        return ['success'=>true,'message'=>'Product saved successfully'];
    }

    function deleteProduct($product_id){ $con=$this->opencon(); try { $stmt=$con->prepare("DELETE FROM product WHERE Product_ID = ?"); $stmt->execute([$product_id]); return ['success'=>true,'message'=>'Product deleted successfully.']; } catch(PDOException $e){ if($e->getCode()==23000){ return ['success'=>false,'message'=>'Cannot delete this product because it is used in existing orders.']; } return ['success'=>false,'message'=>'Database error: '.$e->getMessage()]; } }
    function deleteCategory($category_id){ $con=$this->opencon(); try { $stmt=$con->prepare("DELETE FROM category WHERE Category_ID=?"); $stmt->execute([$category_id]); return ['success'=>true,'message'=>'Category deleted successfully!']; } catch(PDOException $e){ return ['success'=>false,'message'=>'Database Error: '.$e->getMessage()]; } }
    function searchAdmin($kw){ $con=$this->opencon(); $kw='%'.$kw.'%'; $stmt=$con->prepare("SELECT * FROM admin WHERE Admin_Name LIKE ? OR Admin_Email LIKE ? ORDER BY Created_At DESC"); $stmt->execute([$kw,$kw]); return $stmt->fetchAll(PDO::FETCH_ASSOC);}    
    function deleteAdmin($admin_id){ $con=$this->opencon(); try { $stmt=$con->prepare("DELETE FROM admin WHERE Admin_ID = ?"); $stmt->execute([$admin_id]); return ['success'=>true,'message'=>'Admin deleted successfully!']; } catch(PDOException $e){ return ['success'=>false,'message'=>'Database Error: '.$e->getMessage()]; } }
    function viewSales(){ $con=$this->opencon(); $stmt=$con->query("SELECT s.Sale_ID,s.Product_ID,p.Product_Name,s.Quantity,s.Total_Amount,s.Sale_Date,a.Admin_Name FROM sales s JOIN product p ON s.Product_ID=p.Product_ID JOIN admin a ON s.Admin_ID=a.Admin_ID ORDER BY s.Sale_Date DESC"); return $stmt->fetchAll(PDO::FETCH_ASSOC);}    
    function fetchOrders(){ $con=$this->opencon(); $stmt=$con->query("SELECT o.*, p.payment_status, o.Driver_Status FROM orders o LEFT JOIN payment p ON o.Order_ID=p.Order_ID ORDER BY o.Order_Date DESC"); return $stmt->fetchAll(PDO::FETCH_ASSOC);}    
    function fetchOrderItems($order_id){ $con=$this->opencon(); $stmt=$con->prepare("SELECT oi.*, p.Product_Name FROM order_item oi JOIN product p ON oi.Product_ID=p.Product_ID WHERE oi.Order_ID=?"); $stmt->execute([$order_id]); return $stmt->fetchAll(PDO::FETCH_ASSOC);}    
    function updateAdmin($id,$name,$email,$role,$status,$new_password='',$confirm=''){ if(!$id) throw new Exception('Admin ID is missing'); if(!$name||!$email||!$role) throw new Exception('All fields are required!'); if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email format!'); if($new_password && $new_password!==$confirm) throw new Exception('Passwords do not match!'); $con=$this->opencon(); $params=[':name'=>$name,':email'=>$email,':role'=>$role,':status'=>$status,':id'=>$id]; if($new_password){ $hash=password_hash($new_password,PASSWORD_DEFAULT); $sql="UPDATE admin SET Admin_Name=:name, Admin_Email=:email, Admin_Role=:role, Status=:status, Admin_Password=:password, Updated_At=NOW() WHERE Admin_ID=:id"; $params[':password']=$hash; } else { $sql="UPDATE admin SET Admin_Name=:name, Admin_Email=:email, Admin_Role=:role, Status=:status, Updated_At=NOW() WHERE Admin_ID=:id"; } $stmt=$con->prepare($sql); $stmt->execute($params); return $stmt->rowCount()>0 ? ['success'=>true,'message'=>'Admin updated successfully!'] : ['success'=>false,'message'=>'No changes made or admin not found.']; }
    function updatePaymentStatus($payment_id,$payment_status){ $con=$this->opencon(); $stmt=$con->prepare("UPDATE payment SET payment_status=? WHERE Payment_ID=?"); return $stmt->execute([$payment_status,$payment_id]); }
    function updateOrderStatus($order_id,$order_status){ $con=$this->opencon(); $cur=$con->prepare("SELECT order_status, Street, City, Contact_Number FROM orders WHERE Order_ID=? LIMIT 1"); $cur->execute([$order_id]); $row=$cur->fetch(PDO::FETCH_ASSOC); if(!$row) return false; $from=strtolower(trim($row['order_status'])); $to=strtolower(trim($order_status)); $isDelivery = !empty($row['Street'])||!empty($row['City'])||!empty($row['Contact_Number']); $normalize=fn($s)=>ucwords($s); $forwardDelivery=['pending'=>['processing','cancelled'],'processing'=>['ready to deliver','cancelled'],'ready to deliver'=>['on the way','cancelled'],'on the way'=>['delivered'],'delivered'=>[],'cancelled'=>[]]; $forwardPickup=['pending'=>['processing','cancelled'],'processing'=>['ready to pick up','cancelled'],'ready to pick up'=>['received','cancelled'],'received'=>[],'cancelled'=>[]]; $forward=$isDelivery?$forwardDelivery:$forwardPickup; if($from===$to) return true; if($to==='cancelled' && in_array($from,['delivered','received','cancelled'],true)) return false; if(in_array($from,['cancelled','delivered','received'],true)) return false; if(!in_array($to,$forward[$from]??[],true)) return false; $stmt=$con->prepare("UPDATE orders SET order_status=? WHERE Order_ID=?"); return $stmt->execute([$normalize($to),$order_id]); }
    function updatePaymentStatusByOrder($order_id,$payment_status){ $con=$this->opencon(); $stmt=$con->prepare("UPDATE payment SET payment_status=? WHERE Order_ID=?"); return $stmt->execute([$payment_status,$order_id]); }
    function getAllPayments(){ $con=$this->opencon(); $stmt=$con->query("SELECT p.*, o.order_status FROM payment p LEFT JOIN orders o ON p.Order_ID=o.Order_ID ORDER BY p.Payment_Date DESC"); return $stmt->fetchAll(PDO::FETCH_ASSOC);}    
    function countUnpaidPayments(){ $con=$this->opencon(); $stmt=$con->query("SELECT COUNT(*) FROM payment p LEFT JOIN orders o ON p.Order_ID=o.Order_ID WHERE p.payment_status='Unpaid' AND (o.order_status IS NULL OR o.order_status <> 'Cancelled')"); return (int)$stmt->fetchColumn(); }
    function getAllOrdersStatus(){ $con=$this->opencon(); $stmt=$con->query("SELECT order_status FROM orders"); return $stmt->fetchAll(PDO::FETCH_ASSOC);}    
    function countPendingOrProcessingOrders(){ $orders=$this->fetchOrders(); $c=0; foreach($orders as $o){ if(in_array($o['order_status'],['Pending','Processing'],true)) $c++; } return $c; }
    function resetAdminPasswordByToken($token,$password,$confirm){ $con=$this->opencon(); $stmt=$con->prepare("SELECT Admin_ID, Reset_Expires FROM admin WHERE Reset_Token=?"); $stmt->execute([$token]); $admin=$stmt->fetch(PDO::FETCH_ASSOC); if(!$admin || strtotime($admin['Reset_Expires'])<=time()) return ['success'=>false,'message'=>'Invalid or expired token.']; if(!$password || $password!==$confirm) return ['success'=>false,'message'=>'Passwords do not match.']; $hash=password_hash($password,PASSWORD_DEFAULT); $upd=$con->prepare("UPDATE admin SET Admin_Password=?, Reset_Token=NULL, Reset_Expires=NULL WHERE Admin_ID=?"); $upd->execute([$hash,$admin['Admin_ID']]); return $upd->rowCount()>0 ? ['success'=>true,'message'=>"Password reset successful! <a href='login.php'>Login here</a>."] : ['success'=>false,'message'=>'Failed to reset password.']; }
    function isValidAdminResetToken($token){ $con=$this->opencon(); $stmt=$con->prepare("SELECT Reset_Expires FROM admin WHERE Reset_Token=?"); $stmt->execute([$token]); $a=$stmt->fetch(PDO::FETCH_ASSOC); return ($a && strtotime($a['Reset_Expires'])>time()); }
    function getCustomerNameById($cid){ $con=$this->opencon(); $stmt=$con->prepare("SELECT Customer_Name FROM customer WHERE Customer_ID=?"); $stmt->execute([$cid]); $name=$stmt->fetchColumn(); return $name?:'Unknown'; }
    function getCustomerNameByOrderId($oid){ $con=$this->opencon(); $stmt=$con->prepare("SELECT c.Customer_Name FROM orders o JOIN customer c ON o.Customer_ID=c.Customer_ID WHERE o.Order_ID=?"); $stmt->execute([$oid]); $name=$stmt->fetchColumn(); return $name?:'Unknown'; }
    function insertSalesIfDeliveredAndPaid($order_id,$admin_id){ $con=$this->opencon(); $stmt=$con->prepare("SELECT o.*, p.payment_status FROM orders o LEFT JOIN payment p ON o.Order_ID=p.Order_ID WHERE o.Order_ID=?"); $stmt->execute([$order_id]); $order=$stmt->fetch(PDO::FETCH_ASSOC); if($order && $order['order_status']==='Delivered' && $order['payment_status']==='Paid'){ $check=$con->prepare("SELECT COUNT(*) FROM sales WHERE Order_ID=?"); $check->execute([$order_id]); if($check->fetchColumn()==0){ $items=$this->fetchOrderItems($order_id); foreach($items as $it){ $ins=$con->prepare("INSERT INTO sales (Order_ID, Product_ID, Quantity, Total_Amount, Sale_Date, Admin_ID) VALUES (?,?,?,?,NOW(),?)"); $ins->execute([$order_id,$it['Product_ID'],$it['Quantity'],$order['Order_Amount'],$admin_id]); } } } }
    function adminLogin($email,$password){ $con=$this->opencon(); $stmt=$con->prepare("SELECT Admin_ID, Admin_Name, Admin_Password, Admin_Role, Status FROM admin WHERE Admin_Email=:e"); $stmt->bindParam(':e',$email); $stmt->execute(); if($stmt->rowCount()===1){ $admin=$stmt->fetch(PDO::FETCH_ASSOC); if(password_verify($password,$admin['Admin_Password'])){ if($admin['Status']==='Active'){ return ['success'=>true,'admin'=>$admin,'message'=>'Login Successful']; } return ['success'=>false,'message'=>'Your account is inactive. Please contact the system administrator.']; } } return ['success'=>false,'message'=>'Invalid email or password!']; }
    function addAdmin($name,$email,$password,$role,$status){ $con=$this->opencon(); $stmt=$con->prepare("SELECT COUNT(*) FROM admin WHERE Admin_Email=?"); $stmt->execute([$email]); if($stmt->fetchColumn()>0) return ['success'=>false,'message'=>'This email is already taken!']; $hash=password_hash($password,PASSWORD_DEFAULT); $ins=$con->prepare("INSERT INTO admin (Admin_Name, Admin_Password, Admin_Email, Admin_Role, Created_At, Status) VALUES (:n,:p,:e,:r,NOW(),:s)"); $ins->bindParam(':n',$name); $ins->bindParam(':p',$hash); $ins->bindParam(':e',$email); $ins->bindParam(':r',$role); $ins->bindParam(':s',$status); return $ins->execute() ? ['success'=>true] : ['success'=>false,'message'=>'Error adding admin']; }
    function getAllAdmins(){ $con=$this->opencon(); $stmt=$con->query("SELECT * FROM admin ORDER BY Created_At DESC"); return $stmt->fetchAll(PDO::FETCH_ASSOC);}    
    function getAdminStats(){ $con=$this->opencon(); return [ 'total'=>(int)$con->query("SELECT COUNT(*) FROM admin")->fetchColumn(), 'active'=>(int)$con->query("SELECT COUNT(*) FROM admin WHERE Status='Active'")->fetchColumn(), 'inactive'=>(int)$con->query("SELECT COUNT(*) FROM admin WHERE Status='Inactive'")->fetchColumn(), 'super'=>(int)$con->query("SELECT COUNT(*) FROM admin WHERE Admin_Role='Super Admin'")->fetchColumn(), ]; }
    function createPasswordResetToken($email){ $con=$this->opencon(); $stmt=$con->prepare("SELECT Admin_ID FROM admin WHERE Admin_Email=?"); $stmt->execute([$email]); if($stmt->rowCount()===1){ $token=bin2hex(random_bytes(32)); $expires=date('Y-m-d H:i:s',strtotime('+1 hour')); $upd=$con->prepare("UPDATE admin SET Reset_Token=?, Reset_Expires=? WHERE Admin_Email=?"); $upd->execute([$token,$expires,$email]); return ['success'=>true,'token'=>$token,'expires'=>$expires]; } return ['success'=>false]; }
    function adminEmailExists($email){ $con=$this->opencon(); $stmt=$con->prepare("SELECT COUNT(*) FROM admin WHERE Admin_Email=?"); $stmt->execute([trim($email)]); return $stmt->fetchColumn()>0; }
    function addPrice($amount,$from,$to=null){ $con=$this->opencon(); try { $stmt=$con->prepare("INSERT INTO product_price (Price_Amount, Effective_From, Effective_To) VALUES (:a,:f,:t)"); $stmt->execute([':a'=>$amount,':f'=>$from,':t'=>$to?:null]); return ['success'=>true,'message'=>'Price added successfully!']; } catch(PDOException $e){ return ['success'=>false,'message'=>'Database Error: '.$e->getMessage()]; } }
    function saveCategory($name,$id=null){ $con=$this->opencon(); try { if($id){ $stmt=$con->prepare("UPDATE category SET Category_Name=? WHERE Category_ID=?"); $stmt->execute([$name,$id]); return ['success'=>true,'message'=>'Category updated successfully!']; } else { $stmt=$con->prepare("INSERT INTO category (Category_Name) VALUES (?)"); $stmt->execute([$name]); return ['success'=>true,'message'=>'Category added successfully!']; } } catch(PDOException $e){ return ['success'=>false,'message'=>'Database error: '.$e->getMessage()]; } }
    function getProductsCount($category_id=null){ $con=$this->opencon(); if($category_id){ $stmt=$con->prepare("SELECT COUNT(*) FROM product WHERE Category_ID=?"); $stmt->execute([$category_id]); return $stmt->fetchColumn(); } return $con->query("SELECT COUNT(*) FROM product")->fetchColumn(); }
}

/* ===================== Add-ons Helper (CRUD only; mapping removed) ===================== */
class addons_helper extends database {
    public function getAllAddons(): array { $con=$this->opencon(); $stmt=$con->query("SELECT Addon_ID, Addon_Name, Addon_Price, Status, Created_At, Updated_At FROM addons ORDER BY Status DESC, Addon_Name ASC"); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
    public function addAddon(string $name, float $price, string $status='Active'): array { $con=$this->opencon(); $stmt=$con->prepare("INSERT INTO addons (Addon_Name, Addon_Price, Status) VALUES (:n,:p,:s)"); $ok=$stmt->execute([':n'=>$name,':p'=>$price,':s'=>($status==='Inactive' ? 'Inactive':'Active')]); return ['success'=>(bool)$ok,'id'=>$ok?(int)$con->lastInsertId():null]; }
    public function updateAddon(int $id, string $name, float $price, string $status='Active'): bool { $con=$this->opencon(); $stmt=$con->prepare("UPDATE addons SET Addon_Name=:n, Addon_Price=:p, Status=:s WHERE Addon_ID=:id"); return $stmt->execute([':n'=>$name,':p'=>$price,':s'=>($status==='Inactive'?'Inactive':'Active'),':id'=>$id]); }
    public function deleteAddon(int $id): bool { $con=$this->opencon(); $stmt=$con->prepare("DELETE FROM addons WHERE Addon_ID=:id"); return $stmt->execute([':id'=>$id]); }
}