<?php
 
class database{
 
    function opencon() {
 
        return new PDO(
            'mysql:host=localhost; dbname=naitsa',
            username: 'root',
            password: '');
   
 
        }

        // make the functions here
 



}