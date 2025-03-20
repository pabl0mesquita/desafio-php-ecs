<?php 
require_once __DIR__.'/../vendor/autoload.php';

use Core\Database\Connection;
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));

$dotenv->load();

$con = Connection::getInstance();

if($con) {
    echo "Application start connection...";
}else {
    echo "Failed database connection...";
}




   


