<?php 
namespace Kout;

trait Query {

    /**
     *
     *
     * @param [type] $table
     * @param array $cols
     * @return Statement
     */
    public function get($table, array $cols = []): Statement
    {
        $this->reset();
        $this->type = Statement::SELECT;
        Util::push(empty($cols) ? '*' : $cols, $this->selectList);
        Util::push(is_string($table) ? $table : $table, $this->table);
        return $this;
    }

    public function offset(int $value): Statement
    {
        Util::push("OFFSET $value ROWS", $this->orderBy);
        return $this;
    }

    public function fetch(int $value): Statement
    {
        if(Util::contains('OFFSET', $this->sql())) {
            Util::push("FETCH NEXT $value ROWS ONLY", $this->orderBy);
        } else {
            Util::push("OFFSET 0 ROWS FETCH FIRST $value ROWS ONLY", $this->orderBy);
        }
        return $this;
    }

    /**
     * Adicona a cláusula 'order by field asc' à instrução SQL.
     *
     * @param string $field - O campo a ser ordenado
     * @return Statement
     */
    public function orderByAsc(...$fields): Statement
    {
        return $this->addOrderByClause($fields, 'ASC');
    }

    /**
     * Adicona a cláusula 'order by field desc' à instrução SQL.
     *
     * @param string $field - O campo a ser ordenado
     * @return Statement
     */
    public function orderByDesc(...$fields): Statement
    {
        return $this->addOrderByClause($fields, 'DESC');
    }

    

    /**
     * Adiciona a cláusula 'inner join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return Statement
     */
    public function innerJoin(string $table, string $col1, string $col2): Statement
    {
        $this->sql .= " INNER JOIN $table ON $col1 = $col2";
        return $this;
    }

    /**
     * Adiciona a cláusula 'left join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return Statement
     */
    public function leftJoin(string $table, string $col1, string $col2)
    {
        $this->sql .= " LEFT JOIN $table ON $col1 = $col2";
        return $this;
    }

    /**
     * Adiciona a cláusula 'right join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return Statement
     */
    public function rightJoin(string $table, string $col1, string $col2)
    {
        $this->sql .= " RIGHT JOIN $table ON $col1 = $col2";
        return $this;
    }

    /**
     * Adiciona a cláusula 'cross join table_name' à
     * instrução SQL.
     * 
     * Cross join não faz uso de predicados 
     * especificados nas cláusulas 'on' e 'using'.
     *
     * @param string $tableName
     * @return Statement
     */
    public function crossJoin(string $table, string $col1, string $col2): Statement
    {
        $this->sql .= " CROSS JOIN $table ON $col1 = $col2";
        return $this;
    }

    public function fullJoin(string $table, string $col1, string $col2): Statement
    {
        $this->sql .= " FULL JOIN $table ON $col1 = $col2";
        return $this;
    }

    /**
     * Adiciona a cláusula 'groupy by' à instrução SQL.
     *
     * @param varArgs ...$fields - Nomes do campos para
     * agrupamentos
     * 
     * @return Statement
     */
    public function groupBy(...$fields): Statement
    {
        $this->sql .= " GROUP BY " . Util::convertArrayToString(Util::varArgs($fields));
        return $this;
    }

    /**
     * Adiciona a cláusula 'groupy by $fields with rollup' à instrução SQL.
     *
     * @param varArgs ...$fields - Nomes do campos para
     * agrupamentos
     * 
     * @return Statement
     */
    public function groupByWithRollup(...$fields): Statement
    {
        $this->sql .= " GROUP BY " . Util::convertArrayToString(Util::varArgs($fields));
        $this->sql .= " WITH ROLLUP";
        return $this;
    }

    /**
     * Adiciona a cláusula 'union (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return Statement
     */
    public function union($callback): Statement
    {
        return $this->addUnionClause($callback);
    }

    /**
     * Adiciona a cláusula 'union all (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return Statement
     */
    public function unionAll($callback): Statement
    {
        return $this->addUnionClause($callback, 'ALL');
    }

    /**
     * Adiciona a cláusula 'union distinct (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return Statement
     */
    public function unionDistinct($callback): Statement
    {
        return $this->addUnionClause($callback, 'DISTINCT');
    }

    /**
     * Adiciona a cláusula 'having' à instrução SQL.
     *
     * @param string $field
     * @return Statement
     */
    public function having(
        string $col,
        string $op = null,
        $value = null): Statement
    {
        $this->sql .= " HAVING $col";

        if (is_null($op) && is_null($value)) {
            return $this;
        }

        return $this->createExpr($op, $value);
    }

    /**
     * Executa instrução sql nativa
     *
     * @param string $sql
     * @param array $data
     * @return ResultSet
     */
    public function nativeSQL(string $sql, array $data = null)
    {
        $this->reset();
        $this->sql = $sql;
        return $this->list($data);
    }

     /**
     * Instrução SELECT DISTINCT.
     *
     * @param [type] ...$fields - Nomes do campos que serão
     * inseridos na tabela.
     * 
     * @return Statement
     */
    public function distinct(...$fields): Statement
    {
        return $this->processDBFuncAndDistinctClause(
            "DISTINCT " . Util::convertArrayToString(Util::varArgs($fields))
        );
    }

    private function createSubquery($callback): string
    {
        if (!is_callable($callback)) {
            throw new \Exception("Callback ${callback} inválido.");
        }
        $subquery = call_user_func($callback, new $this); // return Statement
        Util::push(array_merge($this->data, $subquery->data), $this->data);
        return trim($subquery->sql());
    }

    private function addOrderByClause(array $fields, $type): Statement
    {
        $fields = Util::varArgs($fields);
        if (count($fields) == 1) {// um campo para ordenar
            if (!Util::contains('ORDER BY', $this->sql())) {
                Util::push("ORDER BY $fields[0] ${type}", $this->orderBy);
            } else {
                Util::push(", $fields[0] ${type}", $this->orderBy);
            }
        } else if(count($fields) > 1) {// muitos campos para ordenar
            Util::push("ORDER BY " . Util::convertArrayToString($fields, ', ') . " $type", $this->orderBy);
        }

        return $this;
    }

    private function addUnionClause($callback, string $type = null): Statement
    {
        $union = "UNION";

        if ($type) {
            $union .= " ${type}";
        }

        $this->sql .= " ${union} " . $this->createSubquery($callback);
        return $this;
    }

    private function processDBFuncAndDistinctClause(string $include) : Statement
    {
        $pos = Util::getPos(' FROM', $this->sql);
        if($this->sql[$pos -1] === '*') {
            $this->sql = substr_replace($this->sql, $include, $pos - 1, 1);
        } else {
            $this->sql = substr_replace($this->sql, ", $include ", $pos, 1);
        }
        return $this;
    }

}