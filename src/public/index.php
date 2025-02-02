<?php require_once __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));
$dotenv->load();

use Source\Core\Database\Connection;

try {
    $con = Connection::getInstance();
    var_dump($con);

}catch(Exception $e) {
    var_dump($e);
}

