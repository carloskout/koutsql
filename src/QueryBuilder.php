<?php

namespace Kout;

use Kout\ResultSet;
use PDOStatement;

abstract class QueryBuilder
{

use QueryBuilderTrait;

   

    

    /**
     * Esta varável controla a chamada
     * encadeada do método values(...$values)
     * da instrução INSERT
     *
     * @var boolean
     */
    private $hasValues = false;

    

    

   

    

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
     * Retorna a instrução SQL no formato string
     *
     * @return string
     */
    

    

    

    

    
}
