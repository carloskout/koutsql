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
    protected $cols;

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

    public function sql(): string
    {
        $this->createSQLStatement();
        return $this->sql;
    }

    private function createSQLStatement():void
    {
        switch($this->type) {
            case self::SELECT:
                $this->createSelectStatement();
            break;
            default:
                $this->createExprStatement();
        }
    }

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

    private function createExprStatement(): void 
    {
        $this->sql = Util::convertArrayToString($this->filterBuffer);
    }

    /**
     * Executa instruções SQL
     *
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
        $this->currentCol = '';
    }

}