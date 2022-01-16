<?php
namespace Kout;

final class DBConfig {

    private function __construct()
    {
        
    }

    public static function getStatement(\PDO $pdo): Statement
    {
        if(!$pdo) {
            throw new \Exception('Objeto PDO não pode ser nulo');
        }

        $drive = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        switch($drive) {
            case 'mysql':
                return new MYSQLStatement($pdo);
            case 'sqlsrv':
                return new SQLServerStatement($pdo);
            default:
                throw new \Exception('Driver de banco de dados não suportado.');
        }
    }
}