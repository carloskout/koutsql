<?php 
namespace Kout;

class MYSQLStatement extends Statement {

    public function __construct(\PDO $pdo = null)
    {
        parent::__construct($pdo);
        $this->setDriver('mysql');
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
        // se for especificado apenas o offset sem o fetch então será retornado
        // um limite de apenas 1000 linhas a partir do offset especificado
        Util::push("LIMIT 1000 OFFSET $value", $this->orderByBuffer);
        return $this;
    }

    // a orderm de chamada => offset()->fetch()
    public function fetch(int $value): Statement
    {
        if(Util::contains('LIMIT 1000', $this->sql())) { //JA EXISTE O OFFSET
            //Util::push("$value", $this->orderByBuffer);
            $lastElem = $this->orderByBuffer[count($this->orderByBuffer) - 1];
            $lastElem = str_replace('1000', $value, $lastElem);
            $this->orderByBuffer[count($this->orderByBuffer) - 1] = $lastElem;
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
        $rs = $this->exec($data)->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($rs)) {
            return $rs[count($rs) - 1];
        }

        return [];
    }
    
}