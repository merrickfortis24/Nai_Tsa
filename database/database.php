<?php
 
class database{
 
    function opencon() {
 
        return new PDO(
            'mysql:host=localhost;dbname=naitsa',
            'root',
            ''
        );
    }

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
}