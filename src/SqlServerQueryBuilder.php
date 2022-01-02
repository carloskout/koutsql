<?php
namespace Kout;

class SqlServerQueryBuilder extends QueryBuilder {

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
    }
}