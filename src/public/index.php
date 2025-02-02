<?php 

require_once __DIR__.'/../vendor/autoload.php';
// use Core\Database\Connection;
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));

$dotenv->load();

try {
    // $con = Connection::getInstance();
    // var_dump($con);
    echo "Rodando...";

}catch(Exception $e) {
    var_dump($e);
}

