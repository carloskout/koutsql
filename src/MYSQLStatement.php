<?php 
namespace Kout;

class MYSQLStatement extends Statement {

    public function __construct(\PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
}