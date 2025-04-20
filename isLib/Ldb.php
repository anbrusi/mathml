<?php

namespace isLib;

class Ldb {

    private static \PDO $dbh;

    public static function connect():bool {
        try {
            $user = 'iststch_user';
            $pass = 'iststch_user';
            self::$dbh = new \PDO('mysql:host=localhost;dbname=iststch_mathml', $user, $pass);
            self::$dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    } 

    public static function prepare(string $query, array $options=[]):\PDOStatement {
        return self::$dbh->prepare($query, $options);
    }

    public static function lastInsertId():string|false {
        return self::$dbh->lastInsertId();
    }
}