<?php

namespace Kout;

use Kout\ResultSet;
use PDOStatement;

abstract class QueryBuilder
{

use QueryBuilderTrait;

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

    /**
     * Instrução INSERT.
     *
     * @param [type] ...$fields - Nomes do campos que serão
     * inseridos na tabela.
     * @return QueryBuilder
     */
    public function add(string $table, ...$cols): QueryBuilder
    {
        $this->reset();
        $cols = Util::convertArrayToString(Util::varArgs($cols));
        $this->sql = "INSERT INTO $table ($cols)";
        return $this;
    }

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
                    if($this->containsPlaceholders($arg[$i])) {
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
    public function min(string $col): QueryBuilder {
        $this->_fn('MIN', $col);
        return $this;
    }

    /**
     * Adiciona a função agregação max() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function max(string $col): QueryBuilder {
        $this->_fn('MAX', $col);
        return $this;
    }

    /**
     * Adiciona a função agregação count() à instrução SQL.
     * @param string $field
     * @return QueryBuilder
     */
    public function count(string $col): QueryBuilder
    {
        $this->_fn('COUNT', $col);
        return $this;
    }

    /**
     * Adiciona a função agregação sum() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function sum(string $col): QueryBuilder {
        $this->_fn('SUM', $col);
        return $this;
    }

    /**
     * Adiciona a função agregação avg() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function avg(string $col): QueryBuilder {
        $this->_fn('AVG', $col);
        return $this;
    }

    /**
     * Abre uma transacao, executa a instrução SQL
     * e depois fecha a transacao.
     *
     * @param [type] $callback
     * @return void
     */
    public function transaction($callback)
    {
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
    }

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

    public function input(array $data, array $data1 = null): int
    {
        if(empty($data)) {
            throw new \Exception('Parâmetro inválido! O campo $data não pode ser vazio ou nulo.');
        }

        if($this->containsPlaceholders(Util::convertArrayToString($data)) 
        && !is_null($data1)) {
            // $data são os placeholders e $data1 são os dados para os placesholders
            $this->sql .= " VALUES (" . Util::convertArrayToString($data) . ")";
            $this->addData($data1);
        } else {
            // $data são os dados de entrada
            $this->sql .= " VALUES (" . $this->covertDataToMaskPlaceholders($data) . ")";
        }

        $this->exec();

        return $this->conn->lastInsertId();
    }

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
            } else if($op == 'in' || $op == 'not in') {
                $this->_in($valueOrSubquery, $op);
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
        return $this->_in($values, 'in');
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
        return $this->_in($values, 'not in');
    }

    /**
     * Adiciona o operador 'is null' à instrução SQL.
     * @return QueryBuilder
     */
    public function isNull(): QueryBuilder
    {
        $this->sql .= " IS NULL";
        return $this;
    }

    /**
     * Adiciona o operador 'is not null' à instrução SQL.
     * @return QueryBuilder
     */
    public function isNotNull(): QueryBuilder
    {
        $this->sql .= " IS NOT NULL";
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
     * Adiciona a cláusula 'exists(subquery)' à instrução SQL.
     *
     * @param callable $callback - função que será processada como uma
     * subquery
     * 
     * @return QueryBuilder
     */
    public function exists($callback): QueryBuilder
    {
        return $this->_exists($callback);
    }

    /**
     * Adiciona a cláusula 'not exists(subquery)' à instrução SQL.
     *
     * @param callable $callback - função que será processada como uma
     * subquery
     * 
     * @return QueryBuilder
     */
    public function notExists($callback): QueryBuilder
    {
        return $this->_exists($callback, 'NOT');
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
        $this->reset();
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
            $data = $this->prepareInputData($this->data);
        } else if (!empty($data)) {
            $data = $this->prepareInputData($data);
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

}
