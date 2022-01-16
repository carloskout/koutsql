<?php 
namespace Kout;

abstract class Statement {

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