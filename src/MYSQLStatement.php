<?php 
namespace Kout;

class MYSQLStatement extends Statement {

    public function __construct(\PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function fullJoin(string $table, string $col1, string $col2): Statement
    {
        $pos = Util::getPos('FROM ', $this->sql) + 4; // +4 é pra deixar logo na posicao do espaco
        var_dump($pos);
        $this->sql .= " FULL JOIN $table ON $col1 = $col2";
        return $this;
    }

    public function offset(int $value): Statement
    {
        
        Util::push("LIMIT $value,", $this->orderBy);
        return $this;
    }

    public function fetch(int $value): Statement
    {
        if(Util::contains('LIMIT', $this->sql())) { //JA EXISTE O OFFSET
            Util::push("$value", $this->orderBy);
        } else {
            Util::push("LIMIT $value", $this->orderBy);
        }
        
        return $this;
    }
    
}