<?php

namespace Kout;
use Kout\ResultSet;

abstract class QueryBuilder
{

    /** @var \PDO */
    private static $conn;

    /**
     * Instrução SQL
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
    private $fields;

    private $data = array();

    /**
     * Esta varável controla a chamada
     * encadeada do método values(...$values)
     * da instrução INSERT
     *
     * @var boolean
     */
    private $hasValues = false;

    private function __construct(string $sql = null, array $fields = null)
    {
        $this->sql = trim($sql);
        $this->fields = $fields ? self::varArgs($fields) : $fields;
    }

    /**
     * Instrução SELECT.
     *
     * @param varArgs ...$fields - Lista de campos que serão
     * selecionados na tabela.
     * 
     * @return QueryBuilder
     */
    public static function select(...$fields): QueryBuilder
    {
        $sql = "SELECT " . self::listFields(self::varArgs($fields));
        return new QueryBuilder($sql);
    }

    /**
     * Instrução SELECT DISTINCT.
     *
     * @param [type] ...$fields - Nomes do campos que serão
     * inseridos na tabela.
     * 
     * @return QueryBuilder
     */
    public static function distinct(...$fields): QueryBuilder
    {
        $sql = "SELECT DISTINCT " . self::listFields(self::varArgs($fields));
        return new QueryBuilder($sql);
    }

    /**
     * Instrução INSERT.
     *
     * @param [type] ...$fields - Nomes do campos que serão
     * inseridos na tabela.
     * @return QueryBuilder
     */
    public static function insert(...$fields): QueryBuilder
    {
        $sql = 'INSERT INTO';
        return new QueryBuilder($sql, $fields);
    }

    /**
     * Instrução UPDATE.
     *
     * @param [type] ...$fields - Nomes dos campos que serão
     * atualizados na tabela.
     * @return QueryBuilder
     */
    public static function update(...$fields): QueryBuilder
    {
        $sql = "UPDATE";
        return new QueryBuilder($sql, $fields);
    }

    /**
     * Esse metodo monta a expressao SET da instrucao
     * UPDATE fazendo a atribuição dos valores aos campos.
     * O valor pode ser tanto um literal quanto um
     * callback para subquery.
     * 
     * @param varArgs ...$values - Literal ou callback
     * @return QueryBuilder
     */
    public function set(...$values): QueryBuilder
    {
        $arg = self::varArgs($values);

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
                    $this->sql .= " = (" . $this->subquery($arg[$i]) . ")";
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
    }

    /**
     * Instrução DELETE.
     *
     * @param string $tableName - Nome da tabela
     * @return QueryBuilder
     */
    public static function delete(string $tableName): QueryBuilder
    {
        $sql = "DELETE FROM ${tableName}";
        return new QueryBuilder($sql);
    }

    /**
     * Adiciona a função agregação min() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function min(string $field): QueryBuilder {
        if(isset($this)) {
            $this->sql .= ", MIN(${field})";
            return $this;
        } else {
            $sql = "SELECT MIN(${field})";
            return new QueryBuilder($sql);
        }
    }

    /**
     * Adiciona a função agregação max() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function max(string $field): QueryBuilder {
        if(isset($this)) {
            $this->sql .= ", MAX(${field})";
            return $this;
        } else {
            $sql = "SELECT MAX(${field})";
            return new QueryBuilder($sql);
        }
    }

    /**
     * Adiciona a função agregação count() à instrução SQL.
     * QueryBuilder::count('*')
     * QueryBuilder::select('*')->count('*')
     * @param string $field
     * @return QueryBuilder
     */
    public function count(string $field): QueryBuilder
    {
        if(isset($this)) {
            $this->sql .= ", COUNT(${field})";
            return $this;
        } else {
            $sql = "SELECT COUNT(${field})";
            return new QueryBuilder($sql);
        }
    }

    /**
     * Adiciona a função agregação sum() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function sum(string $field): QueryBuilder {
        if(isset($this)) {
            $this->sql .= ", SUM(${field})";
            return $this;
        } else {
            $sql = "SELECT SUM(${field})";
            return new QueryBuilder($sql);
        }
    }

    /**
     * Adiciona a função agregação avg() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function avg(string $field): QueryBuilder {
        if(isset($this)) {
            $this->sql .= ", AVG(${field})";
            return $this;
        } else {
            $sql = "SELECT AVG(${field})";
            return new QueryBuilder($sql);
        }
    }

    /**
     * Abre uma transacao, executa a instrução SQL
     * e depois fecha a transacao.
     *
     * @param [type] $callback
     * @return void
     */
    public static function transaction($callback)
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
    public function values(...$values): QueryBuilder
    {
        $arg = self::varArgs($values);

        //se $values for um callback entao será feito
        //um insert usando subquery
        if (isset($arg[0]) && is_callable($arg[0])) {
            $this->sql .= " (" . self::listFields($this->fields) . ")";
            $this->sql .= " " . $this->subquery($arg[0]);
        } else {
            if (!$this->hasValues) {
                $this->hasValues = true;
                $this->sql .= " (" . self::listFields($this->fields) . ")";
                $this->sql .= " VALUES (" . $this->listValues($arg) . ")";
            } else {
                $this->sql .= ", (" . $this->listValues($arg) . ")";
            }
        }
        return $this;
    }

    /**
     * Adiciona o nome da tabela à instrução SQL
     *
     * @param string $name - Nome da tabela
     * @return QueryBuilder
     */
    public function table(...$name): QueryBuilder
    {
        $name = implode(', ', self::varArgs($name));
        if(Util::startsWith('SELECT', $this->sql)) {
            $this->sql .= " FROM ${name}";
        } else {
            $this->sql .= " ${name}";
        }
        return $this;
    }

    public function alias(string $alias): QueryBuilder
    {
        if(str_word_count($alias) > 1) {
            $alias = "'${alias}'";
        }
        $this->sql .= " AS ${alias}";
        return $this;
    }

    /**
     * Adiciona cláusula where à instrução SQL.
     *
     * @param string|null $field - Se informado, o campo
     * $field será adicionado logo após a cláusula where.
     * 
     * @return QueryBuilder
     */
    public function cond(string $field = null): QueryBuilder
    {
        $this->sql .= " WHERE";

        if ($field) {
            $this->sql .= " ${field}";
        }
        return $this;
    }

    /**
     * Usado para criar subexpressoes.
     *
     * @param string $name - Nome do campo
     * @return QueryBuilder
     */
    public static function column(string $name): QueryBuilder
    {
        return new QueryBuilder(" ${name}");
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
    public function neColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '!=');
    }

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
     * @param string|callable $value - Adiciona $value
     * após o operador 'and'. Se uma funcao for passado
     * por parâmetro, então será processado como uma expressao
     * @return QueryBuilder
     */
    public function and($value = null): QueryBuilder
    {
        return $this->addLogicalOperator($value, 'and');
    }

    /**
     * Adiciona o operador lógico 'or' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador 'or'. Se uma funcao for passado
     * por parâmetro, então será processado como uma expressao
     * @return QueryBuilder
     */
    public function or($value = null): QueryBuilder
    {
        return $this->addLogicalOperator($value, 'or');
    }

    /**
     * Adiciona o operador lógico 'like pattern%' à instrução SQL.
     *
     * @param string $value
     * @return QueryBuilder
     */
    public function startsWith(string $value): QueryBuilder
    {
        return $this->addLikeOperator($value, 'starts');
    }

    /**
     * Adiciona o operador lógico 'like %pattern%' à instrução SQL.
     *
     * @param string $value
     * @return QueryBuilder
     */
    public function contains(string $value): QueryBuilder
    {
        return $this->addLikeOperator($value, 'contains');
    }

    /**
     * Adiciona o operador lógico 'like %pattern' à instrução SQL.
     *
     * @param string $value
     * @return QueryBuilder
     */
    public function endsWith($value): QueryBuilder
    {
        return $this->addLikeOperator($value, 'ends');
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
    public function innerJoin(string $tableName): QueryBuilder
    {
        $this->sql .= " INNER JOIN ${tableName}";
        return $this;
    }

    /**
     * Adiciona a cláusula 'left join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return QueryBuilder
     */
    public function leftJoin(string $tableName)
    {
        $this->sql .= " LEFT JOIN ${tableName}";
        return $this;
    }

    /**
     * Adiciona a cláusula 'right join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return QueryBuilder
     */
    public function rightJoin(string $tableName)
    {
        $this->sql .= " RIGHT JOIN ${tableName}";
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
    public function crossJoin(string $tableName): QueryBuilder
    {
        $this->sql .= " CROSS JOIN ${tableName}";
        return $this;
    }

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
    public function on(string $field): QueryBuilder
    {
        $this->sql .= " ON ${field}";
        return $this;
    }

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
    public function using(string $field): QueryBuilder
    {
        $this->sql .= " USING(${field})";
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
        $this->sql .= " GROUP BY " . self::listFields(self::varArgs($fields));
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
        $this->sql .= " GROUP BY " . self::listFields(self::varArgs($fields));
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
    public static function nativeSQL(string $sql, array $data = null): ResultSet
    {
        $QueryBuilder = new QueryBuilder();
        $QueryBuilder->sql = $sql;
        return $QueryBuilder->exec($data);
    }

    /**
     * Executa instruções SQL
     *
     * @return ResultSet
     */
    public function exec(array $data = null): ResultSet
    {
        self::checkConnection();

        if (!empty($this->data)) {
            $data = $this->data;
        } else if (!empty($data)) {
            $data = self::addPlaceholders($data);
        }

        $rs = new ResultSet();

        try {
            $statement = self::$conn->prepare($this->sql, [
                \PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL
            ]);

            if (empty($data)) {
                $statement->execute();
            } else {
                $statement->execute($data);
            }

            $rs->setLastInsertedId(self::$conn->lastInsertId())
            ->setRows($statement->rowCount())
            ->setStatement($statement);
        } catch (\PDOException $e) {
            if (self::$conn->inTransaction()) {
                self::$conn->rollBack();
            }
            throw new \Exception(
                $e->getMessage(),
                $e->getCode() or 0,
                $e
            );
        }

        return $rs;
    }

    /**
     * Adiciona operador relacional à instrução SQL
     *
     * @param mix $value - Pode ser uma string que representa
     * um valor a ser comparado. Ou pode ser um callable que
     * representa uma subquery.
     * 
     * @param string $op - Operador relacional 
     * (=, !=, <, >, >=, <=)
     * @return self
     */
    private function addRelationalOperator($value, string $op):QueryBuilder
    {
        if (!$value) {
            $this->sql .= " " . strtoupper($op);
            return $this;
        }

        if (is_callable($value)) {
            // se $value for uma subquery
            $this->sql .= " $op (" . $this->subquery($value) . ")";
        } else {
            if (self::isPlaceholders($value)) {
                // se $value for um placeholder
                $this->sql .= " $op ${value}";
            } else {
                if(Util::startsWith('*', $value)) {
                    // se $value for um campo da tabela
                    $this->sql .= " $op " . str_replace('*', '', $value);
                } else {
                    // se $value for qualquer valor que nao seja um campo de tabela
                    //ou um placeholder
                    $this->sql .= " $op ?";
                    $this->addData($value);
                }
            }
        }
        return $this;
    }

    /**
     * Adiciona operadores logicos à instruçao SQL
     *
     * @param mix $value - Pode ser uma string que representa
     * um campo da tabela do banco de dados ou pode ser um
     * callable que representa uma subexpressao
     * @param string $op - Operador lógico (and, or)
     * @return QueryBuilder
     */
    private function addLogicalOperator($value, string $op): QueryBuilder
    {
        $op = strtoupper($op);
        if (is_callable($value)) {
            $this->sql .= " $op (" . $this->subquery($value) . ")";
        } else {
            $this->sql .= " $op ${value}";
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
        if (self::isPlaceholders($value)) {
            $this->sql .= " LIKE $value";
        } else {
            $this->sql .= " LIKE ?";
            if($type == 'starts') {
                $this->addData($value . '%');
            } else if($type == 'contains') {
                $this->addData('%' . $value . '%');
            } else {
                $this->addData('%' . $value);
            }
        }
        return $this;
    }

    private function _in(array $values, string $type = null): QueryBuilder
    {
        $arg = self::varArgs($values);

        if ($type) {
            $this->sql .= " ${type}";
        }

        //verifica se na primeira posicao do array
        //existe um callback, caso positivo, cria instrucao
        // 'in' com subqueries
        if (isset($arg[0]) && is_callable($arg[0])) {
            $this->sql .= " IN(" . $this->subquery($arg[0]) . ")";
        } else {
            $this->sql .= " IN(" . $this->listValues(self::varArgs($values)) . ")";
        }

        return $this;
    }

    private function _orderBy(array $fields, $type): QueryBuilder
    {
        $fields = self::varArgs($fields);
        if (count($fields) == 1) {// um campo para ordenar
            if (!Util::contains('ORDER BY', $this->sql)) {
                $this->sql .= " ORDER BY $fields[0] ${type}";
            } else {
                $this->sql .= ", $fields[0] ${type}";
            }
        } else if(count($fields) > 1) {// muitos campos para ordenar
            $this->sql .= " ORDER BY " . self::listFields($fields) . " $type";
        }

        return $this;
    }

    private function _between($low, $high, string $type = null): QueryBuilder
    {
        if ($type) {
            $this->sql .= " ${type}";
        }

        if (self::isPlaceholders($low) && self::isPlaceholders($high)) {
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

        $this->sql .= " EXISTS (" . $this->subquery($callback) . ")";
        return $this;
    }

    private function _union($callback, string $type = null): QueryBuilder
    {
        $union = "UNION";

        if ($type) {
            $union .= " ${type}";
        }

        $this->sql .= " ${union} " . $this->subquery($callback);
        return $this;
    }

    private function subquery($callback): string
    {
        if (!is_callable($callback)) {
            throw new \Exception("Callback ${callback} inválido.");
        }
        $subquery = call_user_func($callback, new QueryBuilder()); // return QueryBuilder
        $this->data = array_merge($this->data, $subquery->data);
        return trim($subquery->sql());
    }

    private function addData($value)
    {
        array_push($this->data, $value);
    }

    private static function listFields(array $fields): string
    {
        return implode(', ', $fields);
    }

    /*recebe um array que pode conter tanto placeholders
    quanto valores de entrada. Se for placeholder, entao
    será retornado uma lista separada por virgulas
    Ex. :nome, :senha ou ?,?,?

    Caso receba valores de entrada, entao sera retornado
    uma lista com mask placeholders representando os valores
    de entradas.

    EX. valores entrada: 'carlos', 'Masculino'
     Lista gerada: ?, ?
    */
    private function listValues(array $values): string
    {
        $values = array_map(function ($value) {
            if (!self::isPlaceholders($value)) {
                $this->addData($value);
                return '?';
            }
            return $value;
        }, $values);

        return implode(", ", $values);
    }

    /*
    Esse metodo deve ser chamado para toda entrada de metodos
    onde o paramentro é um array pois o array pode vir como
    um varArgs ...$fields
    */
    private static function varArgs(array $args)
    {
        return is_array($args[0]) ? $args[0] : $args;
    }

    private static function isPlaceholders(string $value): bool
    {
        if (
            preg_match('/^:[a-z0-9]{1,}(_\w+)?$/i', $value)
            || preg_match('/^\?$/', $value)
        ) {
            return true;
        }
        return false;
    }

    private static function addPlaceholders(array $data): array
    {
        $keys = array_keys($data);

        if ($keys[0] !== 0) {
            $placeholders = array_map(function ($key) {
                return ":${key}";
            }, $keys);

            return array_combine($placeholders, array_values($data));
        }
        return $data;
    }

    private static function checkConnection()
    {
        if (!self::$conn) {
            throw new \Exception('Conexão com banco de dados inválida!
            Use o método QueryBuilder::setPDO para definir uma conexão válida.');
        }
    }

    public static function setPDO(\PDO $pdo)
    {
        self::$conn = $pdo;
    }

    // definicao de metodos magicos para chamada de funcoes do banco de dados

    public function __call($name, $args)
    {
        return self::fn($name, $args, $this);
    }

    public static function __callStatic($name, $args)
    {
        return self::fn($name, $args, null);
    }

    private static function fn(string $fnName, array $fields, $_this): QueryBuilder 
    {

        //verifica se o nome do método inicia com o prefixo 'fn'
        if(!Util::startsWith('fn', $fnName))
            throw new \Exception($fnName . ' Não é uma função de Banco de Dados válida');

        $fnName = str_replace('fn', '', $fnName);
        $fnName = Util::underlineConverter($fnName);

        $fields = self::varArgs($fields);

        if(!$_this) {
            $sql = "SELECT $fnName(" . self::listFields($fields) . ")";
            return new QueryBuilder($sql);
        } else {
            $_this->sql .= ", $fnName(". self::listFields($fields) . ")";
            return $_this;
        }
        
    }
}
