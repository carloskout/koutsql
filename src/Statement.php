<?php 
namespace Kout;

use Kout\ResultSet;
use PDOStatement;

abstract class Statement {

    use 
    Query,
    Crud,
    AggregateFn,
    Filter, 
    LogicalOperator, 
    RelationalOperator,
    ResultSet;

     /** @var \PDO */
     protected $conn;

     /**@var string */
     protected $driver;

     /**
      * Buffer de Instruções SQL
      *
      * @var string
      */
     protected $sql;

     /**
     * Campos de entrada para instrução SQL.
     * Esta array é usado nos métodos 'insert(...$fields)'
     * e 'update(...$fields)'
     *
     * @var array
     */
    protected $colsBuffer = [];
    protected $valuesBuffer = [];

    protected $currentCol;

    //------------------------
    protected $type;
    protected const SELECT = 1;
    protected const UPDATE = 2;
    protected const DELETE = 3;
    protected const INSERT = 4;

    protected $dataBuffer = [];
    protected $tableBuffer = [];
    protected $selectListBuffer = [];
    protected $filterBuffer = [];
    protected $orderByBuffer = [];
    protected $unionBuffer = [];
    protected $joinBuffer = [];
    

     public function __construct(\PDO $pdo = null)
    {
        $this->conn = $pdo;
    }

    /**
     * Retorna a instrução SQL no formato string
     *
     * @return string
     */
    public function sql(): string
    {
        $this->createSQLStatement();
        return $this->sql;
    }

    /**
     * Determina qua instrução SQL será executada
     *
     * @return void
     */
    private function createSQLStatement():void
    {
        switch($this->type) {
            case self::SELECT:
                $this->createSelectStatement();
            break;
            case self::INSERT:
                $this->createInsertStatement();
                break;
            case self::UPDATE:
                $this->createUpdateStatement();
                break;
            case self::DELETE:
                $this->createDeleteStatement();
                break;
            default:
                $this->createExprStatement();
        }
    }

    /**
     * Prepara a instrução SQL para a consulta de dados
     * @return void
     */
    private function createSelectStatement(): void 
    {
        $selectList = Util::convertArrayToString($this->selectListBuffer, ', ');
        $table = Util::convertArrayToString($this->tableBuffer, ', ');
        $this->sql = "SELECT $selectList FROM $table";

        $where = ' WHERE ';

        if(!empty($this->joinBuffer)) {
            $this->sql .= ' ' . Util::convertArrayToString($this->joinBuffer);
            $where = ' ';
        } 
        
        if(!empty($this->filterBuffer)) {
            $this->sql .=  $where . Util::convertArrayToString($this->filterBuffer);
        }

        if(!empty($this->orderByBuffer)) {
            $this->sql .= ' ' . Util::convertArrayToString($this->orderByBuffer);
        }

        if(!empty($this->unionBuffer)) {
            $this->sql .= ' ' . Util::convertArrayToString($this->unionBuffer);
        }
    }

    /**
     * Prepara a instrução SQL para a inserção de dados
     * @return void
     */
    private function createInsertStatement(): void 
    {
        $table = $this->tableBuffer[0];
        $cols = Util::convertArrayToString($this->colsBuffer, ', ');
        $values = Util::createNamedPlaceholders($this->colsBuffer);
        $this->sql = "INSERT INTO $table ($cols) VALUES ($values)";
    }

    /**
     * Prepara a instrução SQL para atualização de dados
     * @return void
     */
    private function createUpdateStatement(): void 
    {
        $table = $this->tableBuffer[0];
        $cols = Util::createSetColumns($this->colsBuffer);
        $this->sql = "UPDATE $table SET $cols";

        if(!empty($this->filterBuffer)) {
            $this->sql .=  ' WHERE ' . Util::convertArrayToString($this->filterBuffer);
        }
    }

    /**
     * Prepara a instrução SQL para remoção de dados
     * @return void
     */
    private function createDeleteStatement(): void
    {
        $table = $this->tableBuffer[0];
        $this->sql = "DELETE FROM $table";

        if(!empty($this->filterBuffer)) {
            $this->sql .=  ' WHERE ' . Util::convertArrayToString($this->filterBuffer);
        }
    }

    /**
     * Prepara a instrução SQL com um filtro sem a cláusula WHERE
     * @return void
     */
    private function createExprStatement(): void 
    {
        $this->sql = Util::convertArrayToString($this->filterBuffer);
    }

    /**
     * Executa instruções SQL
     *@param bool $isNative - Determina se a execução será de uma instrução SQL nativa
     * ou montada com Statement
     * @return PDOStatement
     */
    private function exec(?array $data = null, bool $isNative = false): ?PDOStatement
    {
        if (!empty($this->dataBuffer)) {
            $data = Util::prepareSQLInputData($this->dataBuffer);
        } else if (!empty($data)) {
            $data = Util::prepareSQLInputData($data);
        }

        if(!$isNative) {
            $this->createSQLStatement();
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

    /**
     * Esse método é chamado a cada vez que uma instrução SQL for executada
     *
     * @return void
     */
    private function reset()
    {
        $this->sql = '';
        $this->cols = [];
        $this->dataBuffer = [];
        $this->tableBuffer = [];
        $this->selectListBuffer = [];
        $this->filterBuffer = [];
        $this->orderByBuffer = [];
        $this->unionBuffer = [];
        $this->joinBuffer = [];
        $this->colsBuffer = [];
        $this->valuesBuffer = [];
        $this->currentCol = '';
    }

    protected function setDriver(string $driver): void 
    {
        $this->driver = $driver;
    }

    public function getDriver(): string 
    {
        return $this->driver;
    }

}