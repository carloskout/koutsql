<?php

namespace Kout;

trait Query
{

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
        Util::push(empty($cols) ? '*' : $cols, $this->selectListBuffer);
        Util::push(is_string($table) ? $table : $table, $this->tableBuffer);
        return $this;
    }

    public function offset(int $value): Statement
    {
        Util::push("OFFSET $value ROWS", $this->orderByBuffer);
        return $this;
    }

    public function fetch(int $value): Statement
    {
        if (Util::contains('OFFSET', $this->sql())) {
            Util::push("FETCH NEXT $value ROWS ONLY", $this->orderByBuffer);
        } else {
            Util::push("OFFSET 0 ROWS FETCH FIRST $value ROWS ONLY", $this->orderByBuffer);
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
        Util::push("INNER JOIN $table ON $col1 = $col2", $this->joinBuffer);
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
        Util::push("LEFT JOIN $table ON $col1 = $col2", $this->joinBuffer);
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
        Util::push("RIGHT JOIN $table ON $col1 = $col2", $this->joinBuffer);
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
        Util::push("CROSS JOIN $table ON $col1 = $col2", $this->joinBuffer);
        return $this;
    }

    public function fullJoin(string $table, string $col1, string $col2): Statement
    {
        Util::push("FULL JOIN $table ON $col1 = $col2", $this->joinBuffer);
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
        Util::push("GROUP BY " . Util::convertArrayToString(Util::varArgs($fields), ', '), 
        $this->filterBuffer);
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
        Util::push("GROUP BY " . Util::convertArrayToString(Util::varArgs($fields), ', '), 
        $this->filterBuffer);
        Util::push("WITH ROLLUP", $this->filterBuffer);
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
        $value = null
    ): Statement {
        $this->currentCol = $col;
        Util::push("HAVING $col", $this->filterBuffer);

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
        return $this->exec($data, true)->fetchAll(\PDO::FETCH_ASSOC);
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
        return $this->addSelectListExpr(
            "DISTINCT " . Util::convertArrayToString(Util::varArgs($fields), ', ')
        );
    }

    private function createSubquery($callback): string
    {
        if (!is_callable($callback)) {
            throw new \Exception("Callback ${callback} inválido.");
        }
        $subquery = call_user_func($callback, new $this); // return Statement
        Util::push($subquery->dataBuffer, $this->dataBuffer);
        return trim($subquery->sql());
    }

    private function addOrderByClause(array $fields, $type): Statement
    {
        $fields = Util::varArgs($fields);
        if (count($fields) == 1) { // um campo para ordenar
            if (!Util::contains('ORDER BY', $this->sql())) {
                Util::push("ORDER BY $fields[0] ${type}", $this->orderByBuffer);
            } else {
                Util::push(", $fields[0] ${type}", $this->orderByBuffer);
            }
        } else if (count($fields) > 1) { // muitos campos para ordenar
            Util::push("ORDER BY " . Util::convertArrayToString($fields, ', ') . " $type", $this->orderByBuffer);
        }

        return $this;
    }

    private function addUnionClause($callback, string $type = null): Statement
    {
        $union = "UNION ";

        if ($type) {
            $union .= " $type";
        }

        Util::push($union . $this->createSubquery($callback), $this->unionBuffer);
        return $this;
    }

    private function addSelectListExpr(string $expr): Statement
    {
        if ($this->selectListBuffer[0] == '*') {
            $this->selectListBuffer[0] = $expr;
        } else {
            Util::push($expr, $this->selectListBuffer);
        }
        return $this;
    }
}
