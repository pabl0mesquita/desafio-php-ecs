<?php
namespace Core\Database;

use PDOException;

class Connection
{
     /** @const array */
     private const OPTIONS = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
        \PDO::ATTR_CASE => \PDO::CASE_NATURAL
    ];

    /** @var PDO $instance */
    private static $instance;

    public static function getInstance() {
        if( empty(self::$instance) ) {
            try {
                self::$instance = new \PDO(
                    "pgsql:host=".$_ENV['DATABASE_HOST'].';dbname='.$_ENV['DATABASE_NAME'],
                    $_ENV['DATABASE_USER'],
                    $_ENV['DATABASE_PASSWD'],
                    self::OPTIONS
                );

                return self::$instance;

            } catch( PDOException $e ) {
                var_dump($e);exit;
            }
        }

        return self::$instance;
    }

    /**
     * Connect constructor.
     */
    private function __construct(){}

    /**
     * Connect clone.
     */
    private function __clone(){}
}