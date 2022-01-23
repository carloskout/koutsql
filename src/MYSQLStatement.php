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
    
}