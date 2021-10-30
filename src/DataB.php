<?php

namespace Kout;

use Kout\DataBException;
use Kout\ResultSet;

final class DataB
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
     * @return DataB
     */
    public static function select(...$fields): DataB
    {
        $sql = "select " . self::listFields(self::varArgs($fields));
        return new DataB($sql);
    }

    /**
     * Instrução SELECT DISTINCT.
     *
     * @param [type] ...$fields - Nomes do campos que serão
     * inseridos na tabela.
     * 
     * @return DataB
     */
    public static function selectDistinct(...$fields): DataB
    {
        $sql = "select distinct " . self::listFields(self::varArgs($fields));
        return new DataB($sql);
    }

    /**
     * Instrução INSERT.
     *
     * @param [type] ...$fields - Nomes do campos que serão
     * inseridos na tabela.
     * @return DataB
     */
    public static function insert(...$fields): DataB
    {
        $sql = 'insert into';
        return new DataB($sql, $fields);
    }

    /**
     * Instrução UPDATE.
     *
     * @param [type] ...$fields - Nomes dos campos que serão
     * atualizados na tabela.
     * @return DataB
     */
    public static function update(...$fields): DataB
    {
        $sql = "update";
        return new DataB($sql, $fields);
    }

    /**
     * Esse metodo monta a expressao SET da instrucao
     * UPDATE fazendo a atribuição dos valores aos campos.
     * O valor pode ser tanto um literal quanto um
     * callback para subquery.
     * 
     * @param varArgs ...$values - Literal ou callback
     * @return DataB
     */
    public function set(...$values): DataB
    {
        $arg = self::varArgs($values);

        $this->sql .= " set";

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
            throw new DataBException(
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
     * @return DataB
     */
    public static function delete(string $tableName): DataB
    {
        $sql = "delete from ${tableName}";
        return new DataB($sql);
    }

    /**
     * Adiciona a função agregação min() à instrução SQL.
     *
     * @param string $field
     * @return DataB
     */
    public static function min(string $field): DataB {
        $sql = "select min(${field})";
        return new DataB($sql);
    }

    /**
     * Adiciona a função agregação max() à instrução SQL.
     *
     * @param string $field
     * @return DataB
     */
    public static function max(string $field): DataB {
        $sql = "select max(${field})";
        return new DataB($sql);
    }

    /**
     * Adiciona a função agregação count() à instrução SQL.
     *
     * @param string $field
     * @return DataB
     */
    public static function count(string $field): DataB {
        $sql = "select count(${field})";
        return new DataB($sql);
    }

    /**
     * Adiciona a função agregação sum() à instrução SQL.
     *
     * @param string $field
     * @return DataB
     */
    public static function sum(string $field): DataB {
        $sql = "select sum(${field})";
        return new DataB($sql);
    }

    /**
     * Adiciona a função agregação avg() à instrução SQL.
     *
     * @param string $field
     * @return DataB
     */
    public static function avg(string $field): DataB {
        $sql = "select avg(${field})";
        return new DataB($sql);
    }

    /**
     * Adiciona qualquer função do banco de dados
     * à instrução SQL.
     * 
     * @param string $fnName - Nome da função
     * @param array ...$fields - Parametros para a função
     * @return DataB
     */
    public static function fn(string $fnName, ...$fields): DataB {
        $fields = self::varArgs($fields);
        $sql = "select $fnName(" . self::listFields($fields) . ')';
        return new DataB($sql);
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
            throw new DataBException(
                'Parâmetro inválido! Esperava-se que um callable
                fosse passado para o método DataB::transaction, mas 
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
     * @return DataB
     */
    public function values(...$values): DataB
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
                $this->sql .= " values (" . $this->listValues($arg) . ")";
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
     * @return DataB
     */
    public function table(string $name): DataB
    {
        if(strpos($this->sql, 'select') === 0) {
            $this->sql .= " from ${name}";
        } else {
            $this->sql .= " ${name}";
        }
        return $this;
    }

    /**
     * Adiciona cláusula where à instrução SQL.
     *
     * @param string|null $field - Se informado, o campo
     * $field será adicionado logo após a cláusula where.
     * 
     * @return DataB
     */
    public function cond(string $field = null): DataB
    {
        $this->sql .= " where";

        if ($field) {
            $this->sql .= " ${field}";
        }
        return $this;
    }

    /**
     * Adiciona um campo à instrução SQL. Usado geralmente
     * dentro de expressões.
     *
     * @param string $name - Nome do campo
     * @return DataB
     */
    public function field(string $name): DataB
    {
        $this->sql .= " ${name}";
        return $this;
    }

    /**
     * Adiciona o operador relacional '=' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '='. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return DataB
     */
    public function eq($value = null): DataB
    {
        return $this->addRelationalOperator($value, '=');
    }

    /**
     * Adiciona o operador relacional '!=' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '!='. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return DataB
     */
    public function ne($value = null): DataB
    {
        return $this->addRelationalOperator($value, '!=');
    }

    /**
     * Adiciona o operador relacional '<' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '<'. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return DataB
     */
    public function lt($value = null): DataB
    {
        return $this->addRelationalOperator($value, '<');
    }

    /**
     * Adiciona o operador relacional '>' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '>'. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return DataB
     */
    public function gt($value = null): DataB
    {
        return $this->addRelationalOperator($value, '>');
    }

    /**
     * Adiciona o operador relacional '<=' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '<='. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return DataB
     */
    public function le($value = null): DataB
    {
        return $this->addRelationalOperator($value, '<=');
    }

    /**
     * Adiciona o operador relacional '>=' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador '>='. Se uma funcao for passado
     * por parâmetro, então será processado como uma subquery
     * @return DataB
     */
    public function ge($value = null): DataB
    {
        return $this->addRelationalOperator($value, '>=');
    }

    /**
     * Adiciona o operador lógico 'and' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador 'and'. Se uma funcao for passado
     * por parâmetro, então será processado como uma expressao
     * @return DataB
     */
    public function and($value): DataB
    {
        return $this->addLogicalOperator($value, 'and');
    }

    /**
     * Adiciona o operador lógico 'or' à instrução SQL.
     *
     * @param string|callable $value - Adiciona $value
     * após o operador 'or'. Se uma funcao for passado
     * por parâmetro, então será processado como uma expressao
     * @return DataB
     */
    public function or($value): DataB
    {
        return $this->addLogicalOperator($value, 'or');
    }

    /**
     * Adiciona o operador lógico 'like pattern%' à instrução SQL.
     *
     * @param string $value
     * @return DataB
     */
    public function startsWith(string $value): DataB
    {
        return $this->addLikeOperator($value, 'starts');
    }

    /**
     * Adiciona o operador lógico 'like %pattern%' à instrução SQL.
     *
     * @param string $value
     * @return DataB
     */
    public function contains(string $value): DataB
    {
        return $this->addLikeOperator($value, 'contains');
    }

    /**
     * Adiciona o operador lógico 'like %pattern' à instrução SQL.
     *
     * @param string $value
     * @return DataB
     */
    public function endsWith($value): DataB
    {
        return $this->addLikeOperator($value, 'ends');
    }

    /**
     * Adiciona o operador lógico 'between' à instrução SQL.
     *
     * @param mix $low
     * @param mix $high
     * @return DataB
     */
    public function between($low, $high): DataB
    {
        return $this->_between($low, $high);
    }

    /**
     * Adiciona o operador lógico 'not between' à instrução SQL.
     *
     * @param mix $low
     * @param mix $high
     * @return DataB
     */
    public function notBetween($low, $high): DataB
    {
        return $this->_between($low, $high, 'not');
    }

    /**
     * Adiciona o operador lógico 'in' à instrução SQL.
     *
     * @param varArgs ...$values - Lista de valores para comparação.
     * se $values for uma funcao, entao será processada como uma subquery
     * @return DataB
     */
    public function in(...$values): DataB
    {
        return $this->_in($values);
    }

    /**
     * Adiciona o operador lógico 'not in' à instrução SQL.
     *
     * @param varArgs ...$values - Lista de valores para comparação.
     * se $values for uma funcao, entao será processada como uma subquery
     * @return DataB
     */
    public function notIn(...$values): DataB
    {
        return $this->_in($values, 'not');
    }

    /**
     * Adiciona o operador 'is null' à instrução SQL.
     * @return DataB
     */
    public function isNull(): DataB
    {
        $this->sql .= " is null";
        return $this;
    }

    /**
     * Adiciona o operador 'is not null' à instrução SQL.
     * @return DataB
     */
    public function isNotNull(): DataB
    {
        $this->sql .= " is not null";
        return $this;
    }

    /**
     * Adiciona a cláusula 'limit' à instrução SQL.
     *
     * @param integer $number - Valor inteiro que limita
     * o numero de registros retornados pela query.
     * 
     * @return DataB
     */
    public function limit(int $limit, int $offset = 0): DataB
    {
        $this->sql .= " limit";

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
     * @return DataB
     */
    public function orderByAsc(...$fields): DataB
    {
        return $this->_orderBy($fields, 'asc');
    }

    /**
     * Adicona a cláusula 'order by field desc' à instrução SQL.
     *
     * @param string $field - O campo a ser ordenado
     * @return DataB
     */
    public function orderByDesc(...$fields): DataB
    {
        return $this->_orderBy($fields, 'desc');
    }

    /**
     * Adiciona a cláusula 'exists(subquery)' à instrução SQL.
     *
     * @param callable $callback - função que será processada como uma
     * subquery
     * 
     * @return DataB
     */
    public function exists($callback): DataB
    {
        return $this->_exists($callback);
    }

    /**
     * Adiciona a cláusula 'not exists(subquery)' à instrução SQL.
     *
     * @param callable $callback - função que será processada como uma
     * subquery
     * 
     * @return DataB
     */
    public function notExists($callback): DataB
    {
        return $this->_exists($callback, 'not');
    }

    /**
     * Adiciona a cláusula 'inner join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return DataB
     */
    public function innerJoin(string $tableName): DataB
    {
        $this->sql .= " inner join ${tableName}";
        return $this;
    }

    /**
     * Adiciona a cláusula 'left join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return DataB
     */
    public function leftJoin(string $tableName)
    {
        $this->sql .= " left join ${tableName}";
        return $this;
    }

    /**
     * Adiciona a cláusula 'right join table_name' à
     * instrução SQL.
     *
     * @param string $tableName
     * @return DataB
     */
    public function rightJoin(string $tableName)
    {
        $this->sql .= " right join ${tableName}";
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
     * @return DataB
     */
    public function crossJoin(string $tableName): DataB
    {
        $this->sql .= " cross join ${tableName}";
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
     * @return DataB
     */
    public function on(string $field): DataB
    {
        $this->sql .= " on ${field}";
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
     * @return DataB
     */
    public function using(string $field): DataB
    {
        $this->sql .= " using(${field})";
        return $this;
    }

    /**
     * Adiciona a cláusula 'groupy by' à instrução SQL.
     *
     * @param varArgs ...$fields - Nomes do campos para
     * agrupamentos
     * 
     * @return DataB
     */
    public function groupBy(...$fields): DataB
    {
        $this->sql .= " group by " . self::listFields(self::varArgs($fields));
        return $this;
    }

    /**
     * Adiciona a cláusula 'groupy by $fields with rollup' à instrução SQL.
     *
     * @param varArgs ...$fields - Nomes do campos para
     * agrupamentos
     * 
     * @return DataB
     */
    public function groupByWithRollup(...$fields): DataB
    {
        $this->sql .= " group by " . self::listFields(self::varArgs($fields));
        $this->sql .= " with rollup";
        return $this;
    }

    /**
     * Adiciona a cláusula 'union (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return DataB
     */
    public function union($callback): DataB
    {
        return $this->_union($callback);
    }

    /**
     * Adiciona a cláusula 'union all (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return DataB
     */
    public function unionAll($callback): DataB
    {
        return $this->_union($callback, 'all');
    }

    /**
     * Adiciona a cláusula 'union distinct (subquery)' à instrução SQL.
     *
     * @param callabe $callback - subquery
     * @return DataB
     */
    public function unionDistinct($callback): DataB
    {
        return $this->_union($callback, 'distinct');
    }

    /**
     * Adiciona a cláusula 'having' à instrução SQL.
     *
     * @param string $field
     * @return DataB
     */
    public function having(string $field): DataB
    {
        $this->sql .= " having ${field}";
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
        $DataB = new DataB();
        $DataB->sql = $sql;
        return $DataB->exec($data);
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
            throw new DataBException(
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
    private function addRelationalOperator($value, string $op):DataB
    {
        if (!$value) {
            $this->sql .= " $op";
            return $this;
        }

        if (is_callable($value)) {
            $this->sql .= " $op (" . $this->subquery($value) . ")";
        } else {
            if (self::isPlaceholders($value)) {
                $this->sql .= " $op ${value}";
            } else {
                $this->sql .= " $op ?";
                $this->addData($value);
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
     * @return DataB
     */
    private function addLogicalOperator($value, string $op): DataB
    {
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
     * @return DataB
     */
    private function addLikeOperator(string $value, string $type): DataB
    {
        if (self::isPlaceholders($value)) {
            $this->sql .= " like $value";
        } else {
            $this->sql .= " like ?";
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

    private function _in(array $values, string $type = null): DataB
    {
        $arg = self::varArgs($values);

        if ($type) {
            $this->sql .= " ${type}";
        }

        //verifica se na primeira posicao do array
        //existe um callback, caso positivo, cria instrucao
        // 'in' com subqueries
        if (isset($arg[0]) && is_callable($arg[0])) {
            $this->sql .= " in(" . $this->subquery($arg[0]) . ")";
        } else {
            $this->sql .= " in(" . $this->listValues(self::varArgs($values)) . ")";
        }

        return $this;
    }

    private function _orderBy(array $fields, $type): DataB
    {
        $fields = self::varArgs($fields);
        if (count($fields) == 1) {// um campo para ordenar
            if (strpos($this->sql, 'order by') === false) {
                $this->sql .= " order by $fields[0] ${type}";
            } else {
                $this->sql .= ", $fields[0] ${type}";
            }
        } else if(count($fields) > 1) {// muitos campos para ordenar
            $this->sql .= " order by " . self::listFields($fields) . " $type";
        }

        return $this;
    }

    private function _between($low, $high, string $type = null): DataB
    {
        if ($type) {
            $this->sql .= " ${type}";
        }

        if (self::isPlaceholders($low) && self::isPlaceholders($high)) {
            $this->sql .= " between ${low} and ${high}";
        } else {
            $this->sql .= " between ? and ?";
            $this->addData($low);
            $this->addData($high);
        }


        return $this;
    }

    private function _exists($callback, $type = null): DataB
    {

        if ($type) {
            $this->sql .= " ${type}";
        }

        $this->sql .= " exists (" . $this->subquery($callback) . ")";
        return $this;
    }

    private function _union($callback, string $type = null): DataB
    {
        $union = "union";

        if ($type) {
            $union .= " ${type}";
        }

        $this->sql .= " ${union} " . $this->subquery($callback);
        return $this;
    }

    private function subquery($callback): string
    {
        if (!is_callable($callback)) {
            throw new DataBException("Callback ${callback} inválido.");
        }
        $subquery = call_user_func($callback, new DataB()); // return DataB
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
            throw new DataBException('Nenhuma conexão com o banco de dados foi definida.
            Use o método DataB::setPDO para definir uma conexão com o banco de dados.');
        }
    }

    public static function setPDO(\PDO $pdo)
    {
        self::$conn = $pdo;
    }
}
