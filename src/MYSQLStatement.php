<?php 
namespace Kout;

class MYSQLStatement extends Statement {

    public function __construct(\PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function fullJoin(string $table, string $col1, string $col2): Statement
    {
        Util::push("LEFT JOIN $table ON $col1 = $col2", $this->filterBuffer);
        Util::push("LEFT JOIN $table ON $col1 = $col2", $this->filterBuffer);
        /**
        *SELECT * FROM t1
        *LEFT JOIN t2 ON t1.id = t2.id
        *UNION
        *SELECT * FROM t1
        *RIGHT JOIN t2 ON t1.id = t2.id
         */
        
        return $this;
    }

    public function offset(int $value): Statement
    {
        Util::push("LIMIT $value,", $this->orderByBuffer);
        return $this;
    }

    public function fetch(int $value): Statement
    {
        if(Util::contains('LIMIT', $this->sql())) { //JA EXISTE O OFFSET
            Util::push("$value", $this->orderByBuffer);
        } else {
            Util::push("LIMIT $value", $this->orderByBuffer);
        }
        return $this;
    }
    
}