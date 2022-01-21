<?php
namespace Kout;

final class DB {

    private function __construct()
    {
        
    }

    /**
     * Cria um objeto MYSQLStatement ou SQLServerStatement de acordo
     * com driver informado na conexão PDO passado por parâmetro
     *
     * @param \PDO $pdo - Conexão com banco de dados
     * @return Statement
     */
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