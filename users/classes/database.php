<?php
class database{
    function opencon() {
        return new PDO(
            'mysql:host=localhost;dbname=naitsa',
            'root',
            ''
        );
    }

    function fetchAllProducts() {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT * FROM product");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}