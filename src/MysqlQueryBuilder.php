<?php 
namespace Kout;

class MysqlQueryBuilder extends QueryBuilder {

    public function __construct(\PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
}