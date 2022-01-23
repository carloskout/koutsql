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

    protected $data = array();

     public function __construct(\PDO $pdo = null)
    {
        $this->conn = $pdo;
    }

    public function sql(): string
    {
        return $this->sql;
    }

    /**
     * Executa instruções SQL
     *
     * @return PDOStatement
     */
    private function exec(?array $data = null): ?PDOStatement
    {
        if (!empty($this->data)) {
            $data = Util::prepareSQLInputData($this->data);
        } else if (!empty($data)) {
            $data = Util::prepareSQLInputData($data);
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

    private function addData($value)
    {
        if(is_array($value)) {
            $this->data = array_merge($this->data, $value);
        } else {
            array_push($this->data, $value);
        }
    }

    private function reset()
    {
        $this->sql = '';
        $this->cols = [];
        $this->data = [];
    }

}