<?php
namespace Kout;

class SQLServerStatement extends Statement {

    public function __construct(\PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
}