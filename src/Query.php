<?php 
namespace Kout;

trait Query {

    /**
     * Recupera dados
     * @param mixed $table - Espera-se que seja passado o nome da tabela
     * tanto como string quanto como array. 
     * 
     * @param array $cols - Lista de colunas a serem recuperadas.
     * @return QueryBuilder
     */
    public function get($table, array $cols = []): QueryBuilder
    {
        $this->reset();
        $cols = empty($cols) ? '*' : Util::convertArrayToString($cols);
        $table = is_string($table) ? $table : Util::convertArrayToString($table);
        $this->sql = "SELECT $cols FROM $table";
        return $this;
    }

    /**
     * Adiciona a cláusula 'limit' à instrução SQL.
     *
     * @param integer $number - Valor inteiro que limita
     * o numero de registros retornados pela query.
     * 
     * @return QueryBuilder
     */
    public function limit(int $limit, int $offset = 0): QueryBuilder
    {
        $this->sql .= " LIMIT";

        if($offset > 0) {
            $this->sql .= " ${offset},";
        }

        $this->sql .= " $limit";
        return $this;
    }

    /**
     * Adicona a cláusula 'order by field asc' à instrução SQL.
     *
     * @param string $field - O campo a ser ordenado
     * @return QueryBuilder
     */
    public function orderByAsc(...$fields): QueryBuilder
    {
        return $this->_orderBy($fields, 'ASC');
    }

    /**
     * Adicona a cláusula 'order by field desc' à instrução SQL.
     *
     * @param string $field - O campo a ser ordenado
     * @return QueryBuilder
     */
    public function orderByDesc(...$fields): QueryBuilder
    {
        return $this->_orderBy($fields, 'DESC');
    }

    

    /**
     * Adiciona a cláusula 'inner join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return QueryBuilder
     */
    public function innerJoin(string $table, string $col1, string $col2): QueryBuilder
    {
        $this->sql .= " INNER JOIN $table ON $col1 = $col2";
        return $this;
    }

    /**
     * Adiciona a cláusula 'left join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return QueryBuilder
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
     * @return QueryBuilder
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
     * @return QueryBuilder
     */
    public function crossJoin(string $table, string $col1, string $col2): QueryBuilder
    {
        $this->sql .= " CROSS JOIN $table ON $col1 = $col2";
        return $this;
    }

    /**
     * Adiciona a cláusula 'groupy by' à instrução SQL.
     *
     * @param varArgs ...$fields - Nomes do campos para
     * agrupamentos
     * 
     * @return QueryBuilder
     */
    public function groupBy(...$fields): QueryBuilder
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
     * @return QueryBuilder
     */
    public function groupByWithRollup(...$fields): QueryBuilder
    {
        $this->sql .= " GROUP BY " . Util::convertArrayToString(Util::varArgs($fields));
        $this->sql .= " WITH ROLLUP";
        return $this;
    }

    /**
     * Adiciona a cláusula 'union (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return QueryBuilder
     */
    public function union($callback): QueryBuilder
    {
        return $this->_union($callback);
    }

    /**
     * Adiciona a cláusula 'union all (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return QueryBuilder
     */
    public function unionAll($callback): QueryBuilder
    {
        return $this->_union($callback, 'ALL');
    }

    /**
     * Adiciona a cláusula 'union distinct (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return QueryBuilder
     */
    public function unionDistinct($callback): QueryBuilder
    {
        return $this->_union($callback, 'DISTINCT');
    }

    /**
     * Adiciona a cláusula 'having' à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function having(string $field): QueryBuilder
    {
        $this->sql .= " HAVING ${field}";
        return $this;
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
     * @return QueryBuilder
     */
    /*public function distinct(...$fields): QueryBuilder
    {
        $this->reset();
        $this->sql = "SELECT DISTINCT " . Util::convertArrayToString(Util::varArgs($fields));
        return $this;
    }*/

    private function _orderBy(array $fields, $type): QueryBuilder
    {
        $fields = Util::varArgs($fields);
        if (count($fields) == 1) {// um campo para ordenar
            if (!Util::contains('ORDER BY', $this->sql)) {
                $this->sql .= " ORDER BY $fields[0] ${type}";
            } else {
                $this->sql .= ", $fields[0] ${type}";
            }
        } else if(count($fields) > 1) {// muitos campos para ordenar
            $this->sql .= " ORDER BY " . Util::convertArrayToString($fields) . " $type";
        }

        return $this;
    }

    private function _union($callback, string $type = null): QueryBuilder
    {
        $union = "UNION";

        if ($type) {
            $union .= " ${type}";
        }

        $this->sql .= " ${union} " . $this->createSubquery($callback);
        return $this;
    }

}