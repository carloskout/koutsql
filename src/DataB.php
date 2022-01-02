<?php
namespace Kout;

final class DataB {

    private function __construct()
    {
        
    }

    public static function createQueryBuilder(\PDO $pdo): QueryBuilder
    {
        if(!$pdo) {
            throw new \Exception('Objeto PDO não pode ser nulo');
        }

        $drive = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        switch($drive) {
            case 'mysql':
                return new MYSQLQueryBuilder($pdo);
            case 'sqlsrv':
                return new SQLServerQueryBuilder($pdo);
            default:
                throw new \Exception('Driver de banco de dados não suportado.');
        }
    }
}