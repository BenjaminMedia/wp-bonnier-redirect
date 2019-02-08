<?php

namespace Bonnier\WP\Redirect\Tests\unit\Database;

use Bonnier\WP\Redirect\Database\Query;
use Codeception\Test\Unit;

class QueryTest extends Unit
{
    public function testCanBuildSimpleQuery()
    {
        $query = new Query('table');
        $query->select('*');
        $this->assertSame('SELECT * FROM `table`', $query->getQuery());
    }

    public function testCanSelectColumns()
    {
        $query = new Query('table');
        $query->select(['column_a', 'column_b']);
        $this->assertSame('SELECT `column_a`, `column_b` FROM `table`', $query->getQuery());
    }

    public function testCanSelectSQLFunctions()
    {
        $query = new Query('table');
        $query->select('COUNT(id)');
        $this->assertSame('SELECT COUNT(id) FROM `table`', $query->getQuery());
    }

    public function testCanSelectSQLFunctionsAndColumns()
    {
        $query = new Query('table');
        $query->select(['COUNT(id)', 'test']);
        $this->assertSame('SELECT COUNT(id), `test` FROM `table`', $query->getQuery());
    }

    public function testCanBuildWhereQuery()
    {
        $query = new Query('table');
        $query->select('*')
            ->where(['column_a', 'string']);
        $this->assertSame('SELECT * FROM `table` WHERE `column_a` = \'string\'', $query->getQuery());
    }

    public function testCanBuildWhereQueryWithCustomComparator()
    {
        $query = new Query('table');
        $query->select('*')
            ->where(['column_a', '%string%', 'LIKE']);
        $this->assertSame(
            'SELECT * FROM `table` WHERE `column_a` LIKE \'%string%\'',
            $query->getQuery()
        );
    }

    public function testCanBuildWhereQueryWithDigitFormattedValue()
    {
        $query = new Query('table');
        $query->select('*')
            ->where(['column_a', 42], Query::FORMAT_INT);
        $this->assertSame('SELECT * FROM `table` WHERE `column_a` = 42', $query->getQuery());
    }

    public function testCanBuildWhereOrWhereQuery()
    {
        $query = new Query('table');
        $query->select('*')
            ->where(['column_a', 'test a'])
            ->orWhere(['column_b', 'test b']);
        $this->assertSame(
            'SELECT * FROM `table` WHERE `column_a` = \'test a\' OR `column_b` = \'test b\'',
            $query->getQuery()
        );
    }

    public function testCanOrderByQuery()
    {
        $query = new Query('table');
        $query->select('*')
            ->orderBy('column_a');
        $this->assertSame('SELECT * FROM `table` ORDER BY `column_a`', $query->getQuery());
    }

    public function testCanOrderByAndOrderQuery()
    {
        $query = new Query('table');
        $query->select('*')
            ->orderBy('column_a', Query::ORDER_DESC);
        $this->assertSame('SELECT * FROM `table` ORDER BY `column_a` DESC', $query->getQuery());
    }

    public function testDiscardsInvalidOrderProperty()
    {
        $query = new Query('table');
        $query->select('*')
            ->orderBy('column_a', 'TEST');
        $this->assertSame('SELECT * FROM `table` ORDER BY `column_a`', $query->getQuery());
    }

    public function testCanLimitQuery()
    {
        $query = new Query('table');
        $query->select('*')
            ->limit(10);
        $this->assertSame('SELECT * FROM `table` LIMIT 10', $query->getQuery());
    }

    public function testCanOffsetQuery()
    {
        $query = new Query('table');
        $query->select('*')
            ->offset(20);
        $this->assertSame('SELECT * FROM `table` OFFSET 20', $query->getQuery());
    }

    public function testThrowsExceptionWhenNoSelectionIsSpecified()
    {
        try {
            $query = new Query('table');
            $query->where(['column_a', 'test']);
            $query->getQuery();
        } catch (\Exception $exception) {
            $this->assertSame('A selection needs to be specified!', $exception->getMessage());
            return;
        }

        $this->fail('Failed throwing exception, when no selection is specified!');
    }

    public function testThrowsExceptionWhenTryingToSpecifySelectionTwice()
    {
        try {
            $query = new Query('table');
            $query->select('*')
                ->select(['column_a', 'column_b']);
        } catch (\Exception $exception) {
            $this->assertSame('Selection already specified!', $exception->getMessage());
            return;
        }

        $this->fail('Failed throwing exception, when selection is specified twice!');
    }
}
