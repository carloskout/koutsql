<?php 
namespace Kout\Tests;

use PHPUnit\Framework\TestCase;
use Kout\Util;

class UtilTest extends TestCase {

    public function testStartsWith() 
    {
        // caso de sucesso
        $rs = Util::startsWith('foo', 'foo bar');
        $this->assertTrue($rs);

        // caso de falha
        $rs = Util::startsWith('doe', 'foo bar');
        $this->assertFalse($rs);
    }

    public function testContens() 
    {
        // caso de sucesso
        $rs = Util::contains('ba', 'foo bar');
        $this->assertTrue($rs);

        // caso de falha
        $rs = Util::contains('doe', 'foo bar');
        $this->assertFalse($rs);
    }

    public function testEndsWith() 
    {
        // caso de sucesso
        $rs = Util::endsWith('bar', 'foo bar');
        $this->assertTrue($rs);

        // caso de falha
        $rs = Util::endsWith('foo', 'foo bar');
        $this->assertFalse($rs);
    }

    public function testConvertArrayToString() 
    {
        $arr = ['a', 'b', 'c'];
        $exp = 'a b c';
        $rs = Util::convertArrayToString($arr);
        $this->assertEquals($exp, $rs);
    }

    public function testVarArgs() 
    {
        $varArgs = [[1,2,3]];
        $exp = [1,2,3];
        $rs = Util::varArgs($varArgs);
        $this->assertEquals($exp, $rs);
    }

    public function testCreateMaskPlaceholders()
    {
        // recebendo array com valores literais
        $rs = Util::createMaskPlaceholders(['title', 'price']);
        $this->assertEquals('?, ?', $rs);

        // recebendo array com mask placeholders
        $rs = Util::createMaskPlaceholders(['?', '?']);
        $this->assertEquals('?, ?', $rs);
    }

    public function testCreateNamedPlaceholders()
    {
        // recebendo array com valores literais
        $rs = Util::createNamedPlaceholders(['title', 'price']);
        $this->assertEquals(':title, :price', $rs);

        // recebendo array com mask placeholders
        $rs = Util::createNamedPlaceholders([':title', ':price']);
        $this->assertEquals(':title, :price', $rs);
    }

    public function testCreateSetColumns()
    {
        $exp = 'title = :title, price = :price';
        $rs = Util::createSetColumns(['title', 'price']);
        $this->assertEquals($exp, $rs);
    }

    public function testContainsPlaceholders()
    {
        // caso de sucesso passando array como param.
        $rs = Util::containsPlaceholders([':title', '?']);
        $this->assertTrue($rs);

        // caso de sucesso passando string como param.
        $rs = Util::containsPlaceholders(':title');
        $this->assertTrue($rs);

        // caso de falha passando array como param.
        $rs = Util::containsPlaceholders(['title', '1']);
        $this->assertFalse($rs);
    }

    public function testPrepareSQLInputData()
    {
        //passando um array associativo
        $arr = ['title' => 'foo', 'price' => 2.99];
        $exp = [':title' => 'foo', ':price' => 2.99];
        $rs = Util::prepareSQLInputData($arr);
        $this->assertEquals($exp, $rs);

        // passando um array indexado
        $arr = ['a', 'b', 'c'];
        $exp = ['a', 'b', 'c'];
        $rs = Util::prepareSQLInputData($arr);
        $this->assertEquals($exp, $rs);
    }

    public function testCreateRandomColumn()
    {
        // criando uma coluna
        $rs = Util::createRandomColumn();
        $this->assertEquals('col_1', $rs);

        // criando mais de uma coluna
        $rs = Util::createRandomColumn(3);
        $this->assertEquals(['col_2', 'col_3', 'col_4'], $rs);
    }

    public function testPush()
    {
        // passando valor literal
        $arr = [];
        Util::push(1, $arr);
        $this->assertEquals([1], $arr);

        //passando array
        Util::push([2,3], $arr);
        $this->assertEquals([1, 2, 3], $arr);
    }
}