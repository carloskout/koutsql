<?php 
namespace Kout;

class MYSQLStatement extends Statement {

    public function __construct(\PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function fullJoin(string $table, string $col1, string $col2): Statement
    {
        global $t1;
        global $t2;
        global $c1;
        global $c2;

        $t1 = $this->tableBuffer[0];
        $t2 = $table;
        $c1 = $col1;
        $c2 = $col2;

        Util::push("LEFT JOIN $table ON $col1 = $col2", $this->joinBuffer);

        $closure = function(Statement $st) {
            global $t1;
            global $t2;
            global $c1;
            global $c2;
            return $st->get($t1)->rightJoin($t2, $c1, $c2);
        };

        $this->union($closure);
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

    /**
     * Retorna o primeiro resultado retornado pela query.
     *
     * @param array|null $data - Dados de entrada para execução da instrução SQL.
     * @return array - Array associativo.
     */
    public function last(array $data = null): array 
    {
        throw new \Exception('Não suportado');
    }
    
}