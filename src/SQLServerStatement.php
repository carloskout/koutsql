<?php
namespace Kout;

class SQLServerStatement extends Statement {

    public function __construct(\PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function offset(int $value): Statement
    {
        $this->sql .= " OFFSET $value ROWS";
        return $this;
    }

    public function fetch(int $value): Statement
    {
        if(Util::contains('OFFSET', $this->sql)) {
            $this->sql .= " FETCH NEXT $value ROWS ONLY";
        } else {
            $this->sql .= " OFFSET 0 ROWS FETCH FIRST $value ROWS ONLY";
        }
        return $this;
    }
}