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


    
}