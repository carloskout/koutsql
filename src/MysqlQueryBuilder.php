<?php 
namespace Kout;

class MysqlQueryBuilder extends QueryBuilder {

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
    }
}