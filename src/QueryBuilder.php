<?php

namespace Kout;

use Kout\ResultSet;
use PDOStatement;

abstract class QueryBuilder
{

    /** @var \PDO */
    private $conn;

    /**
     * Buffer de Instruções SQL
     *
     * @var string
     */
    private $sql;

    /**
     * Campos de entrada para instrução SQL.
     * Esta array é usado nos métodos 'insert(...$fields)'
     * e 'update(...$fields)'
     *
     * @var array
     */
    private $cols;

    private $data = array();

    /**
     * Esta varável controla a chamada
     * encadeada do método values(...$values)
     * da instrução INSERT
     *
     * @var boolean
     */
    private $hasValues = false;

    public function __construct(\PDO $pdo = null)
    {
        $this->conn = $pdo;
    }

    /**
     * Instrução SELECT.
     *
     * @param varArgs ...$fields - Lista de campos que serão
     * selecionados na tabela.
     * 
     * @return QueryBuilder
     */
    public function get(string $table, array $cols = []): QueryBuilder
    {
        $this->clear();
        $cols = empty($cols) ? '*' : Util::convertArrayToString($cols);
        $this->sql = "SELECT $cols FROM $table";
        return $this;
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
        $this->clear();
        $this->sql = "SELECT DISTINCT " . Util::convertArrayToString(Util::varArgs($fields));
        return $this;
    }*/

    /**
     * Instrução INSERT.
     *
     * @param [type] ...$fields - Nomes do campos que serão
     * inseridos na tabela.
     * @return QueryBuilder
     */
    /*public function insert(...$fields): QueryBuilder
    {
        $this->clear();
        $this->sql = 'INSERT INTO';
        $this->fields = $fields;
        return new QueryBuilder($sql, $fields);
    }*/

    /**
     * Instrução UPDATE.
     *
     * @param [type] ...$fields - Nomes dos campos que serão
     * atualizados na tabela.
     * @return QueryBuilder
     */
    /*public static function update(...$fields): QueryBuilder
    {
        $sql = "UPDATE";
        return new QueryBuilder($sql, $fields);
    }*/

    /**
     * Esse metodo monta a expressao SET da instrucao
     * UPDATE fazendo a atribuição dos valores aos campos.
     * O valor pode ser tanto um literal quanto um
     * callback para subquery.
     * 
     * @param varArgs ...$values - Literal ou callback
     * @return QueryBuilder
     */
    /*public function set(...$values): QueryBuilder
    {
        $arg = Util::varArgs($values);

        $this->sql .= " SET";

        //a quantidade de campos a serem atualizados
        //devem bater com a quantidade de valores
        if (count($this->fields) === count($arg)) {

            for ($i = 0; $i < count($arg); $i++) {
                $this->sql .= " " . $this->fields[$i];

                //verifica se o indece atual contem um callback
                // se sim, entao temos como valor para o campo
                //atual uma subquery
                if (is_callable($arg[$i])) {
                    $this->sql .= " = (" . $this->createSubquery($arg[$i]) . ")";
                } else {
                    //se o valor de entrada atual for um placeholder :name ou ?
                    //entao será associado ao campo atual
                    if($this->isPlaceholders($arg[$i])) {
                        $this->sql .= " = " . $arg[$i];
                    } else {
                        // caso o valor de entrada atual seja um
                        //literal entao no campo atual será associado
                        //um mask placeholder e valor literal será
                        //adicionado ao array de dados que será enviado
                        //junto à instrução sql quando esta for executada.
                        $this->sql .= " = ?";
                        $this->addData($arg[$i]);
                    }
                }

                if ($i != count($arg) - 1) {
                    $this->sql .= ",";
                }
            }
        } else {
            throw new \Exception(
                "A quantidade de campos a serem atualizados é 
                incompatível com a quantidade de valores passados
                por parâmetros!"
            );
        }

        return $this;
    }*/

    /**
     * Instrução DELETE.
     *
     * @param string $tableName - Nome da tabela
     * @return QueryBuilder
     */
    /*public static function delete(string $tableName): QueryBuilder
    {
        $sql = "DELETE FROM ${tableName}";
        return new QueryBuilder($sql);
    }*/

    /**
     * Adiciona a função agregação min() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    /*public function min(string $field): QueryBuilder {
        if(isset($this)) {
            $this->sql .= ", MIN(${field})";
            return $this;
        } else {
            $sql = "SELECT MIN(${field})";
            return new QueryBuilder($sql);
        }
    }*/

    /**
     * Adiciona a função agregação max() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    /*public function max(string $field): QueryBuilder {
        if(isset($this)) {
            $this->sql .= ", MAX(${field})";
            return $this;
        } else {
            $sql = "SELECT MAX(${field})";
            return new QueryBuilder($sql);
        }
    }*/

    /**
     * Adiciona a função agregação count() à instrução SQL.
     * QueryBuilder::count('*')
     * QueryBuilder::select('*')->count('*')
     * @param string $field
     * @return QueryBuilder
     */
    /*public function count(string $field): QueryBuilder
    {
        if(isset($this)) {
            $this->sql .= ", COUNT(${field})";
            return $this;
        } else {
            $sql = "SELECT COUNT(${field})";
            return new QueryBuilder($sql);
        }
    }*/

    /**
     * Adiciona a função agregação sum() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    /*public function sum(string $field): QueryBuilder {
        if(isset($this)) {
            $this->sql .= ", SUM(${field})";
            return $this;
        } else {
            $sql = "SELECT SUM(${field})";
            return new QueryBuilder($sql);
        }
    }*/

    /**
     * Adiciona a função agregação avg() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    /*public function avg(string $field): QueryBuilder {
        if(isset($this)) {
            $this->sql .= ", AVG(${field})";
            return $this;
        } else {
            $sql = "SELECT AVG(${field})";
            return new QueryBuilder($sql);
        }
    }*/

    /**
     * Abre uma transacao, executa a instrução SQL
     * e depois fecha a transacao.
     *
     * @param [type] $callback
     * @return void
     */
    /*public static function transaction($callback)
    {
        self::checkConnection();
        $rs = null;

        if (!is_callable($callback)) {
            throw new \Exception(
                'Parâmetro inválido! Esperava-se que um callable
                fosse passado para o método QueryBuilder::transaction, mas 
                um ' . gettype($callback) . ' foi passado'
            );
        }

        self::$conn->beginTransaction();
        $rs = call_user_func($callback);
        self::$conn->commit();

        return $rs;
    }*/

    /**
     * Usado na instruçao INSERT.
     * Insere valores de entrada na instrução insert.
     * Permite o encadeamento de chamada ao metodo values().
     * Permite tambem a insercao a partir de uma 
     * subquery.
     * 
     * @param array ...$values
     * @return QueryBuilder
     */
    /*public function values(...$values): QueryBuilder
    {
        $arg = Util::varArgs($values);

        //se $values for um callback entao será feito
        //um insert usando subquery
        if (isset($arg[0]) && is_callable($arg[0])) {
            $this->sql .= " (" . Util::convertArrayToString($this->fields) . ")";
            $this->sql .= " " . $this->createSubquery($arg[0]);
        } else {
            if (!$this->hasValues) {
                $this->hasValues = true;
                $this->sql .= " (" . Util::convertArrayToString($this->fields) . ")";
                $this->sql .= " VALUES (" . $this->covertDataToMaskPlaceholders($arg) . ")";
            } else {
                $this->sql .= ", (" . $this->covertDataToMaskPlaceholders($arg) . ")";
            }
        }
        return $this;
    }*/

    /**
     * Adiciona cláusula where à instrução SQL.
     * 
     * Para fazer comparação entre colunas informe
     * o caractere '*' no inicio de $value
     *
     * @param string $col - Nome da coluna
     * @param string $op - Operador relacional
     * @param mixed $value - Valor literal ou callback 
     * @return QueryBuilder
     */
    public function filter(
        string $col,
        string $op = null,
        $valueOrSubquery = null
    ): QueryBuilder {
        $this->sql .= " WHERE $col";
        if (!empty($op) && !empty($valueOrSubquery)) {
            if($op == '^' || $op == '.' || $op == '$') { // Like operator
                $this->addLikeOperator($valueOrSubquery, $op);
            } else {
                $this->addRelationalOperator($valueOrSubquery, $op);
            }
        }
        return $this;
    }

    public function subexpr(
        string $col,
        string $relOperator = null,
        $valueOrSubquery = null
    ): QueryBuilder {
        $this->sql .= " $col";
        if (!empty($relOperator) && !empty($valueOrSubquery)) {
            $this->addRelationalOperator($valueOrSubquery, $relOperator);
        }
        return $this;
    }

    /**
     * Adiciona o operador relacional '=' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '='. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return QueryBuilder
     */
    public function eqValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator($value, '=');
    }

    /**
     * Use este método para fazer comparação entre campos
     * de tabelas.
     *
     * @param string $column - Campo a ser comparado
     * @return QueryBuilder
     */
    public function eqColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '=');
    }

    /**
     * Adiciona o operador relacional '!=' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '!='. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return QueryBuilder
     */
    public function neValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator($value, '!=');
    }

    /**
     * Use este método para fazer comparação entre campos
     * de tabelas.
     *
     * @param string $column - Campo a ser comparado
     * @return QueryBuilder
     */
    /*public function neColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '!=');
    }*/

    /**
     * Adiciona o operador relacional '<' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '<'. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return QueryBuilder
     */
    public function ltValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator($value, '<');
    }

    /**
     * Use este método para fazer comparação entre campos
     * de tabelas.
     *
     * @param string $column - Campo a ser comparado
     * @return QueryBuilder
     */
    public function ltColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '<');
    }

    /**
     * Adiciona o operador relacional '>' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '>'. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return QueryBuilder
     */
    public function gtValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator($value, '>');
    }

    /**
     * Use este método para fazer comparação entre campos
     * de tabelas.
     *
     * @param string $column - Campo a ser comparado
     * @return QueryBuilder
     */
    public function gtColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '>');
    }

    /**
     * Adiciona o operador relacional '<=' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '<='. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return QueryBuilder
     */
    public function leValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator($value, '<=');
    }

    /**
     * Use este método para fazer comparação entre campos
     * de tabelas.
     *
     * @param string $column - Campo a ser comparado
     * @return QueryBuilder
     */
    public function leColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '<=');
    }

    /**
     * Adiciona o operador relacional '>=' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '>='. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return QueryBuilder
     */
    public function geValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator($value, '>=');
    }

    /**
     * Use este método para fazer comparação entre campos
     * de tabelas.
     *
     * @param string $column - Campo a ser comparado
     * @return QueryBuilder
     */
    public function geColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '>=');
    }

    /**
     * Adiciona o operador lógico 'and' à instrução SQL.
     *
     * @param mixed $value1 - Coluna ou callback para subexpressoes
     * @param string $relOp - Relational Operator
     * @param mixed $value2 - Valor literal ou callback para subquery
     * @return QueryBuilder
     */

    public function and(
        $valueOrSubexpression = null,
        string $relOperator = null,
        $valueOrSubquery = null
    ): QueryBuilder {
        return $this->addLogicalOperator($valueOrSubexpression, $relOperator, $valueOrSubquery, 'AND');
    }

    /**
     * Adiciona o operador lógico 'or' à instrução SQL.
     *
     * @param mixed $value1 - Coluna ou callback para subexpressoes
     * @param string $relOp - Relational Operator
     * @param mixed $value2 - Valor literal ou callback para subquery
     * @return QueryBuilder
     */
    public function or(
        $valueOrSubexpression = null,
        string $relOperator = null,
        $valueOrSubquery = null
    ): QueryBuilder {
        return $this->addLogicalOperator($valueOrSubexpression, $relOperator, $valueOrSubquery, 'OR');
    }

    /**
     * Adiciona o operador lógico 'like pattern%' à instrução SQL.
     *
     * @param string $value
     * @return QueryBuilder
     */
    public function startsWith(string $value): QueryBuilder
    {
        return $this->addLikeOperator($value, '^');
    }

    /**
     * Adiciona o operador lógico 'like %pattern%' à instrução SQL.
     *
     * @param string $value
     * @return QueryBuilder
     */
    public function contains(string $value): QueryBuilder
    {
        return $this->addLikeOperator($value, '.');
    }

    /**
     * Adiciona o operador lógico 'like %pattern' à instrução SQL.
     *
     * @param string $value
     * @return QueryBuilder
     */
    public function endsWith($value): QueryBuilder
    {
        return $this->addLikeOperator($value, '$');
    }

    /**
     * Adiciona o operador lógico 'between' à instrução SQL.
     *
     * @param mix $low
     * @param mix $high
     * @return QueryBuilder
     */
    public function between($low, $high): QueryBuilder
    {
        return $this->_between($low, $high);
    }

    /**
     * Adiciona o operador lógico 'not between' à instrução SQL.
     *
     * @param mix $low
     * @param mix $high
     * @return QueryBuilder
     */
    public function notBetween($low, $high): QueryBuilder
    {
        return $this->_between($low, $high, 'not');
    }

    /**
     * Adiciona o operador lógico 'in' à instrução SQL.
     *
     * @param varArgs ...$values - Lista de valores para comparação.
     * se $values for uma funcao, entao será processada como uma subquery
     * @return QueryBuilder
     */
    public function in(...$values): QueryBuilder
    {
        return $this->_in($values);
    }

    /**
     * Adiciona o operador lógico 'not in' à instrução SQL.
     *
     * @param varArgs ...$values - Lista de valores para comparação.
     * se $values for uma funcao, entao será processada como uma subquery
     * @return QueryBuilder
     */
    public function notIn(...$values): QueryBuilder
    {
        return $this->_in($values, 'not');
    }

    /**
     * Adiciona o operador 'is null' à instrução SQL.
     * @return QueryBuilder
     */
    /*public function isNull(): QueryBuilder
    {
        $this->sql .= " IS NULL";
        return $this;
    }*/

    /**
     * Adiciona o operador 'is not null' à instrução SQL.
     * @return QueryBuilder
     */
    /*public function isNotNull(): QueryBuilder
    {
        $this->sql .= " IS NOT NULL";
        return $this;
    }*/

    /**
     * Adiciona a cláusula 'limit' à instrução SQL.
     *
     * @param integer $number - Valor inteiro que limita
     * o numero de registros retornados pela query.
     * 
     * @return QueryBuilder
     */
    /*public function limit(int $limit, int $offset = 0): QueryBuilder
    {
        $this->sql .= " LIMIT";

        if($offset > 0) {
            $this->sql .= " ${offset},";
        }

        $this->sql .= " $limit";
        return $this;
    }*/

    /**
     * Adicona a cláusula 'order by field asc' à instrução SQL.
     *
     * @param string $field - O campo a ser ordenado
     * @return QueryBuilder
     */
    /*public function orderByAsc(...$fields): QueryBuilder
    {
        return $this->_orderBy($fields, 'ASC');
    }*/

    /**
     * Adicona a cláusula 'order by field desc' à instrução SQL.
     *
     * @param string $field - O campo a ser ordenado
     * @return QueryBuilder
     */
    /*public function orderByDesc(...$fields): QueryBuilder
    {
        return $this->_orderBy($fields, 'DESC');
    }*/

    /**
     * Adiciona a cláusula 'exists(subquery)' à instrução SQL.
     *
     * @param callable $callback - função que será processada como uma
     * subquery
     * 
     * @return QueryBuilder
     */
    /*public function exists($callback): QueryBuilder
    {
        return $this->_exists($callback);
    }*/

    /**
     * Adiciona a cláusula 'not exists(subquery)' à instrução SQL.
     *
     * @param callable $callback - função que será processada como uma
     * subquery
     * 
     * @return QueryBuilder
     */
    /*public function notExists($callback): QueryBuilder
    {
        return $this->_exists($callback, 'NOT');
    }*/

    /**
     * Adiciona a cláusula 'inner join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return QueryBuilder
     */
    /*public function innerJoin(string $tableName): QueryBuilder
    {
        $this->sql .= " INNER JOIN ${tableName}";
        return $this;
    }*/

    /**
     * Adiciona a cláusula 'left join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return QueryBuilder
     */
    /*public function leftJoin(string $tableName)
    {
        $this->sql .= " LEFT JOIN ${tableName}";
        return $this;
    }*/

    /**
     * Adiciona a cláusula 'right join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return QueryBuilder
     */
    /*public function rightJoin(string $tableName)
    {
        $this->sql .= " RIGHT JOIN ${tableName}";
        return $this;
    }*/

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
    /*public function crossJoin(string $tableName): QueryBuilder
    {
        $this->sql .= " CROSS JOIN ${tableName}";
        return $this;
    }*/

    //usar este metodo quando os campos de juncao
    //das tabelas tiverem nomes diferentes

    /**
     * Adiciona a clásula 'on' à intrução SQL.
     * Usar este método quando os campos de juncão
     * das tabelas tiverem nomes diferentes
     * 
     * @param string $field - Campo que será usado
     * no predicado
     * @return QueryBuilder
     */
    /*public function on(string $field): QueryBuilder
    {
        $this->sql .= " ON ${field}";
        return $this;
    }*/

    /*
     * Adiciona a clásula 'using' à intrução SQL.
     * Usar este método quando os campos de juncão
     * das tabelas tiverem os mesmos nomes.
     * 
     * @param string $field - Nome do campo comum às
     * tabelas de junção.
     * 
     * @return QueryBuilder
     */
    /*public function using(string $field): QueryBuilder
    {
        $this->sql .= " USING(${field})";
        return $this;
    }*/

    /**
     * Adiciona a cláusula 'groupy by' à instrução SQL.
     *
     * @param varArgs ...$fields - Nomes do campos para
     * agrupamentos
     * 
     * @return QueryBuilder
     */
    /*public function groupBy(...$fields): QueryBuilder
    {
        $this->sql .= " GROUP BY " . Util::convertArrayToString(Util::varArgs($fields));
        return $this;
    }*/

    /**
     * Adiciona a cláusula 'groupy by $fields with rollup' à instrução SQL.
     *
     * @param varArgs ...$fields - Nomes do campos para
     * agrupamentos
     * 
     * @return QueryBuilder
     */
    /*public function groupByWithRollup(...$fields): QueryBuilder
    {
        $this->sql .= " GROUP BY " . Util::convertArrayToString(Util::varArgs($fields));
        $this->sql .= " WITH ROLLUP";
        return $this;
    }*/

    /**
     * Adiciona a cláusula 'union (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return QueryBuilder
     */
    /*public function union($callback): QueryBuilder
    {
        return $this->_union($callback);
    }*/

    /**
     * Adiciona a cláusula 'union all (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return QueryBuilder
     */
    /*public function unionAll($callback): QueryBuilder
    {
        return $this->_union($callback, 'ALL');
    }*/

    /**
     * Adiciona a cláusula 'union distinct (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return QueryBuilder
     */
    /*public function unionDistinct($callback): QueryBuilder
    {
        return $this->_union($callback, 'DISTINCT');
    }*/

    /**
     * Adiciona a cláusula 'having' à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    /*public function having(string $field): QueryBuilder
    {
        $this->sql .= " HAVING ${field}";
        return $this;
    }*/

    /**
     * Retorna a instrução SQL no formato string
     *
     * @return string
     */
    public function sql(): string
    {
        return $this->sql;
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
        $this->clear();
        $this->sql = $sql;
        return $this->list($data);
    }

    /**
     * Executa instruções SQL
     *
     * @return PDOStatement
     */
    private function exec(?array $data = null): ?PDOStatement
    {
        if (!empty($this->data)) {
            $data = $this->data;
        } else if (!empty($data)) {
            $data = Util::prepareInputData($data);
        }

        try {
            $statement = $this->conn->prepare($this->sql, [
                \PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL
            ]);

            if (empty($data)) {
                $statement->execute();
            } else {
                $statement->execute($data);
            }
            return $statement;
        } catch (\PDOException $e) {
            if (self::$conn->inTransaction()) {
                self::$conn->rollBack();
            }
            throw $e;
        }

        return null;
    }

    public function list(array $data = null)
    {
        $st = $this->exec($data);
        if (!$st) {
            return null;
        }
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona operador relacional à instrução SQL
     *
     * @param mix $valueOrSubquery - Pode ser uma string que representa
     * um valor a ser comparado. Ou pode ser um callable que
     * representa uma subquery.
     * 
     * @param string $op - Operador relacional 
     * (=, !=, <, >, >=, <=)
     * @return self
     */
    private function addRelationalOperator($valueOrSubquery, string $op): QueryBuilder
    {
        if (!$valueOrSubquery) {
            $this->sql .= " " . strtoupper($op);
            return $this;
        }

        // Se o valor for uma subquery
        if (is_callable($valueOrSubquery)) {
            $this->sql .= " $op (" . $this->createSubquery($valueOrSubquery) . ")";
        } 
        //Se o valor for um placeholder
        else if (Util::isPlaceholders($valueOrSubquery)) {
            $this->sql .= " $op ${valueOrSubquery}";
        } 
        //Se o valor for um campo de tabela
        else if (Util::startsWith('*', $valueOrSubquery)) {
            $this->sql .= " $op " . str_replace('*', '', $valueOrSubquery);
        } 
        // Senão É um valor literal
        else {
            $this->sql .= " $op ?";
            $this->addData($valueOrSubquery);
        }

        return $this;
    }

    /**
     * Adiciona operadores logicos à instruçao SQL
     *
     * @param mixed $valueOrSubexpression - Coluna ou callback para subexpressoes
     * @param string $relOperator - Operador Relacional
     * @param mixed $valueOrSubquery - Valor literal ou callback para subquery
     * @param string $logicalOperator - Operator Logico
     * @return QueryBuilder
     */

    private function addLogicalOperator(
        $valueOrSubexpression = null,
        ?string $relOperator = null,
        $valueOrSubquery = null,
        string $logicalOperator
    ): QueryBuilder {
        $this->sql .= " $logicalOperator";

        //Se for uma subexpressao
        if (is_callable($valueOrSubexpression)) {
            $this->sql .= " (" . $this->createSubquery($valueOrSubexpression) . ")";
        }
        // Se for uma expressao que tem comparação
        else if (
            is_string($valueOrSubexpression) &&
            !empty($relOperator) &&
            !empty($valueOrSubquery)
        ) {
            $this->sql .= " $valueOrSubexpression";
            $this->addRelationalOperator($valueOrSubquery, $relOperator);
        }
        // Se for uma expressao com apenas uma coluna
        else if (!empty($valueOrSubexpression) && is_string($valueOrSubexpression)) {
            $this->sql .= " $valueOrSubexpression";
        }
        return $this;
    }

    /**
     * Adiciona operador like à instrução SQL
     *
     * @param string $value - Representa um valor literal ou um
     * placeholder
     * @param string $type - Tipo do like (starts, contains, ends)
     * @return QueryBuilder
     */

     private function addLikeOperator(string $value, string $type): QueryBuilder
    {
        if (Util::isPlaceholders($value)) {
            $this->sql .= " LIKE $value";
        } else {
            $this->sql .= " LIKE ?";
            if($type == '^') {
                $this->addData($value . '%');
            } else if($type == '.') {
                $this->addData('%' . $value . '%');
            } else {
                $this->addData('%' . $value);
            }
        }
        return $this;
    }

    
    private function _in(array $values, string $type = null): QueryBuilder
    {
        $arg = Util::varArgs($values);

        if ($type) {
            $this->sql .= " ${type}";
        }

        //verifica se na primeira posicao do array
        //existe um callback, caso positivo, cria instrucao
        // 'in' com subqueries
        if (isset($arg[0]) && is_callable($arg[0])) {
            $this->sql .= " IN(" . $this->createSubquery($arg[0]) . ")";
        } else {
            $this->sql .= " IN(" . $this->covertDataToMaskPlaceholders(Util::varArgs($values)) . ")";
        }

        return $this;
    }

    
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

    private function _between($low, $high, string $type = null): QueryBuilder
    {
        if ($type) {
            $this->sql .= " ${type}";
        }

        if (Util::isPlaceholders($low) && Util::isPlaceholders($high)) {
            $this->sql .= " BETWEEN ${low} AND ${high}";
        } else {
            $this->sql .= " BETWEEN ? AND ?";
            $this->addData($low);
            $this->addData($high);
        }


        return $this;
    }

    private function _exists($callback, $type = null): QueryBuilder
    {
        if(!Util::contains('WHERE', $this->sql)) {
            $this->sql .= ' WHERE';
        }

        if ($type) {
            $this->sql .= " ${type}";
        }

        $this->sql .= " EXISTS (" . $this->createSubquery($callback) . ")";
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

    private function createSubquery($callback): string
    {
        if (!is_callable($callback)) {
            throw new \Exception("Callback ${callback} inválido.");
        }
        $subquery = call_user_func($callback, new $this); // return QueryBuilder
        $this->data = array_merge($this->data, $subquery->data);
        return trim($subquery->sql());
    }

    private function addData($value)
    {
        array_push($this->data, $value);
    }

    /*recebe um array que pode conter tanto placeholders
    quanto valores literais. Se for placeholder, entao
    será retornado uma lista separada por virgulas
    Ex. :nome, :senha ou ?,?,?

    Caso receba valores de entrada, entao sera retornado
    uma lista com mask placeholders representando os valores
    de entradas.

    EX. valores entrada: 'carlos', 'Masculino'
     Lista gerada: ?, ?
    */
    private function covertDataToMaskPlaceholders(array $values): string
    {
        $values = array_map(function ($value) {
            if (!Util::isPlaceholders($value)) {
                $this->addData($value);
                return '?';
            }
            return $value;
        }, $values);

        return implode(", ", $values);
    }





    // definicao de metodos magicos para chamada de funcoes do banco de dados
    /*
    public function __call($name, $args)
    {
        return self::fn($name, $args, $this);
    }

    public static function __callStatic($name, $args)
    {
        return self::fn($name, $args, null);
    }*/

    /*private static function fn(string $fnName, array $fields, $_this): QueryBuilder 
    {

        //verifica se o nome do método inicia com o prefixo 'fn'
        if(!Util::startsWith('fn', $fnName))
            throw new \Exception($fnName . ' Não é uma função de Banco de Dados válida');

        $fnName = str_replace('fn', '', $fnName);
        $fnName = Util::underlineConverter($fnName);

        $fields = Util::varArgs($fields);

        if(!$_this) {
            $sql = "SELECT $fnName(" . Util::convertArrayToString($fields) . ")";
            return new QueryBuilder($sql);
        } else {
            $_this->sql .= ", $fnName(". Util::convertArrayToString($fields) . ")";
            return $_this;
        } 
    }*/

    public function clear()
    {
        $this->sql = '';
        $this->cols = [];
        $this->data = [];
    }
}
