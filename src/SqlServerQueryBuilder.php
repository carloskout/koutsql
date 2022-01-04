<?php
namespace Kout;

class SqlServerQueryBuilder extends QueryBuilder {

    public function __construct(\PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
}