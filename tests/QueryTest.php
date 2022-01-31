<?php 
namespace Kout\Tests;

use PHPUnit\Framework\TestCase;
use Kout\DB;
use Kout\Statement;

class QueryTest extends TestCase {

    protected function setUp():void
    {
        $pdo = new \PDO('mysql:dbname=queryb;host=localhost', 'root', 'root');
        //self::$pdo = new \PDO('sqlsrv:Server=localhost;Database=queryb', 'sa', 'root');
        $this->db = DB::getStatement($pdo);
    }

    public function testBasicQuery()
    {
        $rs = $this->db->get('author')->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
    }

    public function testFetchColumn()
    {
        $rs = $this->db->get('author', ['name'])->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilter_LIKE_OperatorStartsWith()
    {
        $rs = $this->db->get('author')->filter('name', '^', 'Ca')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        $rs = $this->db->get('author')->filter('name')->startsWith('Ca')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilter_LIKE_OperatorContains()
    {
        $rs = $this->db->get('author')->filter('name', '.', 'Cou')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        $rs = $this->db->get('author')->filter('name')->contains('Cou')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilter_LIKE_OperatorEndsWith()
    {
        $rs = $this->db->get('author')->filter('name', '$', 'nho')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        $rs = $this->db->get('author')->filter('name')->endsWith('nho')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilter_LIKE_Placeholders()
    {
        $rs = $this->db->get('author')->filter('name', '^', ':name')->first(['name' => 'Ca%']);
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        // testando operador like Ca% passando named placeholders
        $rs = $this->db->get('author')->filter('name')->startsWith(':name')->first(['name' => 'Ca%']);
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilter_IN_OperatorWithLiteralValues()
    {
        // testando o metodo filter com operador IN com valores literais de entrada
        $rs = $this->db->get('author')->filter('id', '->', [1,2])->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
        $this->assertEquals('Delvania Paz', $rs[1]['name']);

        $rs = $this->db->get('author')->filter('id')->in(1,2)->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
        $this->assertEquals('Delvania Paz', $rs[1]['name']);
    }

    public function testFilter_NOT_IN_OperatorWithLiteralValues()
    {
        // testando o metodo filter com operador NOT IN com valores literais de entrada
        $rs = $this->db->get('author')->filter('id', '!->', [1,2])->list();
        $this->assertEquals('Caio Levi', $rs[0]['name']);

        $rs = $this->db->get('author')->filter('id')->notIn(1,2)->list();
        $this->assertEquals('Caio Levi', $rs[0]['name']);
    }

    public function testFilter_IN_WithInvalidValue()
    {
        // testando o lançamento de exceção ao passar parâmetro diferente de array ou callback
        $this->expectException(\Exception::class);
        $this->db->get('author')->filter('id', '->', 12)->list();
    }

    public function testFilter_IN_WithSubquery()
    {
        $subQ = function(Statement $st) {
            return $st->get('category', ['id']);
        };

        $rs = $this->db->get('author')->filter('id', '->', $subQ)->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
        $this->assertEquals('Delvania Paz', $rs[1]['name']);

        $rs = $this->db->get('author')->filter('id')->in($subQ)->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
        $this->assertEquals('Delvania Paz', $rs[1]['name']);
    }

    public function testFilterWithInvalidOperatorAndValue()
    {
        $this->expectException(\Exception::class);
        $this->db->get('author')->filter('id', '', '')->list();
    }

    public function testFilter_IN_WithPlaceholders()
    {
        $rs = $this->db->get('author')->filter('id', '->', [':id1'])->list(['id1' => 1]);
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);

        $rs = $this->db->get('author')->filter('id', '->', ['?'])->list([1]);
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
    }

    public function testFilter_BETWEEN_OperatorWithLiteralValues()
    {
        //testando operador between com valores literais
        $rs = $this->db->get('author')->filter('id', '|', [2,3])->list();
        $this->assertEquals('Delvania Paz', $rs[0]['name']);
        $this->assertEquals('Caio Levi', $rs[1]['name']);

        $this->db->get('author')->filter('id')->between(2,3)->list();
        $this->assertEquals('Delvania Paz', $rs[0]['name']);
        $this->assertEquals('Caio Levi', $rs[1]['name']);
    }

    public function testFilter_NOT_BETWEEN_OperatorWithLiteralValues()
    {
        //testando operador not between com valores literais
        $rs = $this->db->get('author')->filter('id', '^|', [2,3])->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);

        $this->db->get('author')->filter('id')->notBetween(2,3)->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
    }

    public function testFilter_BETWEEN_Placeholders()
    {
        //testando operador between com placeholders
        $rs = $this->db->get('author')->filter('id', '|', [':low',':high'])->list(['low' => 2, 'high' => 3]);
        $this->assertEquals('Delvania Paz', $rs[0]['name']);
        $this->assertEquals('Caio Levi', $rs[1]['name']);

        $rs = $this->db->get('author')->filter('id')->between(':low', ':high')->list(['low' => 2, 'high' => 3]);
        $this->assertEquals('Delvania Paz', $rs[0]['name']);
        $this->assertEquals('Caio Levi', $rs[1]['name']);
    }

    public function test_BETWEEN_InvalidValue()
    {
        //lancando excecao para valor invalido
        $this->expectException(\Exception::class);
        $this->db->get('author')->filter('id', '|', 123)->list();
    }

    public function testIsNull()
    {
        $rs = $this->db->get('category')->filter('category_parent')->isNull()->first();
        $this->assertEquals('Noticias', $rs['name']);
    }

    public function testIsNotNull()
    {
        $rs = $this->db->get('category')->filter('id')->isNotNull()->first();
        $this->assertEquals('Noticias', $rs['name']);
    }

    public function testExists()
    {
        $subQ = function(Statement $st) {
            return $st->get('author', ['id'])->filter('id', '=', 1);
        };

        $rs = $this->db->get('author')->exists($subQ)->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testNotExists()
    {
        $subQ = function(Statement $st) {
            return $st->get('author', ['id'])->filter('id', '=', 2);
        };

        $rs = $this->db->get('author')->notExists($subQ)->first();
        $this->assertEmpty($rs);
    }

    public function testFilter_AND_Operator()
    {
        $rs = $this->db->get('article')->filter('id', '=', 1)->and('author_id', '=', 1)->first();
        $this->assertNotEmpty($rs);

        $rs = $this->db->get('article')->filter('id')
        ->eqValue(1)
        ->and('author_id')
        ->eqValue(1)
        ->first();
        $this->assertNotEmpty($rs);
    }

    public function testFilter_OR_Operator()
    {
        $rs = $this->db->get('article')->filter('id', '=', 1)->or('author_id', '=', 1)->first();
        $this->assertNotEmpty($rs);

        $rs = $this->db->get('article')->filter('id')
        ->eqValue(1)
        ->or('author_id')
        ->eqValue(1)
        ->first();
        $this->assertNotEmpty($rs);
    }

    public function testSubexpr()
    {
        $subExpr = function(Statement $st) {
            return $st->filter('author_id', '=', 1)->or('category_id', '=', 2);
        };

        $rs = $this->db->get('article')->filter('id', '=', 1)->and($subExpr)->list();
        $this->assertNotEmpty($rs);

        //Outra forma usando o metodo subexpr
        $subExpr = function(Statement $st) {
            return $st->subexpr('author_id', '=', 1)->or('category_id', '=', 2);
        };

        $rs = $this->db->get('article')->filter('id', '=', 1)->and($subExpr)->list();
        $this->assertNotEmpty($rs);

        //Outra forma
        $subExpr = function(Statement $st) {
            return $st->subexpr('author_id')
            ->eqValue(1)
            ->or('category_id')
            ->eqValue(2);
        };

        $rs = $this->db->get('article')->filter('id', '=', 1)->and($subExpr)->list();
        $this->assertNotEmpty($rs);

    }

    public function testFilterEqualsOperator()
    {
        $rs = $this->db->get('author')->filter('id', '=', 1)->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        $rs = $this->db->get('author')->filter('id')->eqValue(1)->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilterNotEqualsOperator()
    {
        $rs = $this->db->get('author')->filter('id', '!=', 1)->first();
        $this->assertEquals('Delvania Paz', $rs['name']);

        $rs = $this->db->get('author')->filter('id')->neValue(1)->first();
        $this->assertEquals('Delvania Paz', $rs['name']);
    }

    public function testFilterLessThanOperator()
    {
        $rs = $this->db->get('author')->filter('id', '<', 2)->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        $rs = $this->db->get('author')->filter('id')->ltValue(2)->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilterGreaderThanOperator()
    {
        $rs = $this->db->get('author')->filter('id', '>', 2)->first();
        $this->assertEquals('Caio Levi', $rs['name']);

        $rs = $this->db->get('author')->filter('id')->gtValue(2)->first();
        $this->assertEquals('Caio Levi', $rs['name']);
    }

    public function testFilterLessOrEqualsOperator()
    {
        $rs = $this->db->get('author')->filter('id', '<=', 2)->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        $rs = $this->db->get('author')->filter('id')->leValue(2)->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilterGreaderOrEqualsOperator()
    {
        $rs = $this->db->get('author')->filter('id', '>=', 3)->first();
        $this->assertEquals('Caio Levi', $rs['name']);

        $rs = $this->db->get('author')->filter('id')->geValue(3)->first();
        $this->assertEquals('Caio Levi', $rs['name']);
    }

    public function testFilterRelationalOperatorWithSubquery()
    {
        $subQ = function(Statement $st) {
            return $st->get('author', ['id'])->filter('name', '^', 'Carlos');
        };

        $rs = $this->db->get('author')->filter('id', '=', $subQ)->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        $rs = $this->db->get('author')->filter('id')->eqValue($subQ)->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilterRelationalOperatorWithPlacesholders()
    {
        $rs = $this->db->get('author')->filter('id', '=', ':id')->first(['id' => 1]);
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        $rs = $this->db->get('author')->filter('id')->eqValue(':id')->first(['id' => 1]);
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilterEqualsColumn()
    {
        $rs = $this->db->get('article')->filter('id', '=', '*category_id')->first();
        $this->assertNotEmpty($rs);

        $rs = $this->db->get('article')->filter('id')->eqColumn('category_id')->first();
        $this->assertNotEmpty($rs);
    }

    public function testFilterNotEqualsColumn()
    {
        $rs = $this->db->get('article')->filter('id', '!=', '*category_id')->first();
        $this->assertNotEmpty($rs);

        $rs = $this->db->get('article')->filter('id')->neColumn('category_id')->first();
        $this->assertNotEmpty($rs);
    }

    public function testFilterLessThanColumn()
    {
        $rs = $this->db->get('article')->filter('id', '<', '*category_id')->first();
        $this->assertNotEmpty($rs);

        $rs = $this->db->get('article')->filter('id')->ltColumn('category_id')->first();
        $this->assertNotEmpty($rs);
    }

    public function testFilterGreaderThanColumn()
    {
        $rs = $this->db->get('article')->filter('id', '>', '*category_id')->first();
        $this->assertNotEmpty($rs);

        $rs = $this->db->get('article')->filter('id')->gtColumn('category_id')->first();
        $this->assertNotEmpty($rs);
    }

    public function testFilterLessOrEqualsColumn()
    {
        $rs = $this->db->get('article')->filter('id', '<=', '*category_id')->first();
        $this->assertNotEmpty($rs);

        $rs = $this->db->get('article')->filter('id')->leColumn('category_id')->first();
        $this->assertNotEmpty($rs);
    }

    public function testFilterGreaderOrEqualsColumn()
    {
        $rs = $this->db->get('article')->filter('id', '>=', '*category_id')->first();
        $this->assertNotEmpty($rs);

        $rs = $this->db->get('article')->filter('id')->geColumn('category_id')->first();
        $this->assertNotEmpty($rs);
    }
}