<?php
session_start();
require_once('../classes/database.php');

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate inputs (price dropdown removed -> require manual base_price and effective_from)
    $required = ['product_name', 'category_id', 'base_price', 'effective_from'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $db = new database();

    $product_name = $_POST['product_name'];
    $product_desc = $_POST['product_desc'];
    $category_id = $_POST['category_id'];
    $base_price = (float)$_POST['base_price'];
    $effective_from = $_POST['effective_from'];
    $effective_to = isset($_POST['effective_to']) && $_POST['effective_to']!=='' ? $_POST['effective_to'] : null;
    $admin_id = $_SESSION['admin_id'];
    $product_id = !empty($_POST['product_id']) ? $_POST['product_id'] : null;

    // Handle allergens (array to comma-separated string)
    $product_allergens = '';
    if (isset($_POST['Product_Allergens'])) {
        if (is_array($_POST['Product_Allergens'])) {
            $product_allergens = implode(',', $_POST['Product_Allergens']);
        } else {
            $product_allergens = $_POST['Product_Allergens'];
        }
    }

    // Handle image upload/preserve/remove for add/edit
    $image_name = '';
    $existing_name = isset($_POST['product_image_existing']) ? trim($_POST['product_image_existing']) : '';
    $uploadDir = realpath(__DIR__ . '/../uploads/products');
    if ($uploadDir === false) { $uploadDir = __DIR__ . '/../uploads/products'; }
    // If a new file is uploaded, save it and plan to replace existing
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $orig = basename($_FILES['product_image']['name']);
        // sanitize file name
        $safe = preg_replace('/[^A-Za-z0-9._-]/','_', $orig);
        $image_name = uniqid('p_', true) . '_' . $safe;
        @mkdir($uploadDir, 0775, true);
        move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $image_name);
        // If there was an existing image, remove it to avoid orphaned files
        if ($existing_name) {
            $oldPath = $uploadDir . DIRECTORY_SEPARATOR . basename($existing_name);
            if (is_file($oldPath)) { @unlink($oldPath); }
        }
    } else if ($product_id) {
        // Editing: no new file uploaded
        if ($existing_name !== '') {
            // Keep current image
            $image_name = $existing_name;
        } else {
            // Explicitly cleared via UI -> remove current image on update
            $image_name = '';
            if (!empty($_POST['product_image_existing_raw'])) {
                $old = basename($_POST['product_image_existing_raw']);
                $oldPath = $uploadDir . DIRECTORY_SEPARATOR . $old;
                if (is_file($oldPath)) { @unlink($oldPath); }
            }
        }
    }

    // Create/ensure a legacy Price_ID row so existing schema keeps working, then log per-product price
    // Note: We still insert the new price row, but when editing a product and the new Effective_From
    // is in the future we DO NOT immediately assign product.Price_ID to that future row. Instead we
    // preserve the existing Price_ID so the new price only takes effect on its scheduled date.
    $legacyPriceId = $db->insertLegacyPriceAndReturnId($base_price, $effective_from, $effective_to);

    $today = date('Y-m-d');
    $priceToAssign = $legacyPriceId; // default: assign the newly inserted price row

    if ($product_id) {
        // When editing: fetch current product price id so we can preserve it for future-dated changes
        try {
            $con = $db->opencon();
            $stmtCur = $con->prepare("SELECT Price_ID FROM product WHERE Product_ID = ? LIMIT 1");
            $stmtCur->execute([$product_id]);
            $existingPriceId = $stmtCur->fetchColumn();
            if ($existingPriceId === false) { $existingPriceId = null; }
        } catch (Throwable $e) {
            $existingPriceId = null;
        }

        // If the new Effective_From is strictly in the future, keep the current Price_ID so site pricing
        // remains unchanged until the scheduled date. Otherwise assign the new price row immediately.
        if ($effective_from > $today && $existingPriceId) {
            $priceToAssign = $existingPriceId;
        }

        // Update product linking to the chosen price id
        $result = $db->saveProduct($product_name, $product_desc, $category_id, $priceToAssign, $admin_id, $image_name, $product_id, $product_allergens);
        $pidToLog = !empty($result['product_id']) ? (int)$result['product_id'] : (int)$product_id;
        $db->logProductPrice($pidToLog, $base_price, $effective_from, $effective_to);
    } else {
        // Add new product: must assign the created legacy price so product has a base price row
        $result = $db->saveProduct($product_name, $product_desc, $category_id, $legacyPriceId, $admin_id, $image_name, null, $product_allergens);
        if (!empty($result['product_id'])) {
            $db->logProductPrice((int)$result['product_id'], $base_price, $effective_from, $effective_to);
        }
    }

    echo json_encode($result);
} catch (Exception $e) {
    error_log("Add Product Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}