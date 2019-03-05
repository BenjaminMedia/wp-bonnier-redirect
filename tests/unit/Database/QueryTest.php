<?php

namespace Bonnier\WP\Redirect\Tests\unit\Database;

use Bonnier\WP\Redirect\Database\Query;
use Codeception\Test\Unit;

class QueryTest extends Unit
{
    public function testCanBuildSimpleQuery()
    {
        $query = new Query('table');
        try {
            $query->select('*');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame('SELECT * FROM `table`', $queryString);
    }

    public function testCanSelectColumns()
    {
        $query = new Query('table');
        try {
            $query->select(['column_a', 'column_b']);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame('SELECT `column_a`, `column_b` FROM `table`', $queryString);
    }

    public function testCanSelectSQLFunctions()
    {
        $query = new Query('table');
        try {
            $query->select('COUNT(id)');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame('SELECT COUNT(id) FROM `table`', $queryString);
    }

    public function testCanSelectSQLFunctionsAndColumns()
    {
        $query = new Query('table');
        try {
            $query->select(['COUNT(id)', 'test']);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame('SELECT COUNT(id), `test` FROM `table`', $queryString);
    }

    public function testCanBuildWhereQuery()
    {
        $query = new Query('table');
        try {
            $query->select('*');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        $query->where(['column_a', 'string']);
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame('SELECT * FROM `table` WHERE `column_a` = \'string\'', $queryString);
    }

    public function testCanBuildWhereQueryWithCustomComparator()
    {
        $query = new Query('table');
        try {
            $query->select('*');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        $query->where(['column_a', '%string%', 'LIKE']);
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame(
            'SELECT * FROM `table` WHERE `column_a` LIKE \'%string%\'',
            $queryString
        );
    }

    public function testCanBuildWhereQueryWithDigitFormattedValue()
    {
        $query = new Query('table');
        try {
            $query->select('*');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        $query->where(['column_a', 42], Query::FORMAT_INT);
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame('SELECT * FROM `table` WHERE `column_a` = 42', $queryString);
    }

    public function testCanBuildWhereOrWhereQuery()
    {
        $query = new Query('table');
        try {
            $query->select('*');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        $query->where(['column_a', 'test a'])
            ->orWhere(['column_b', 'test b']);
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame(
            'SELECT * FROM `table` WHERE `column_a` = \'test a\' OR `column_b` = \'test b\'',
            $queryString
        );
    }

    public function testCanOrderByQuery()
    {
        $query = new Query('table');
        try {
            $query->select('*');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        $query->orderBy('column_a');
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame('SELECT * FROM `table` ORDER BY `column_a`', $queryString);
    }

    public function testCanOrderByAndOrderQuery()
    {
        $query = new Query('table');
        try {
            $query->select('*');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating redirect (%s)', $exception->getMessage()));
        }
        $query->orderBy('column_a', Query::ORDER_DESC);
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame('SELECT * FROM `table` ORDER BY `column_a` DESC', $queryString);
    }

    public function testDiscardsInvalidOrderProperty()
    {
        $query = new Query('table');
        try {
            $query->select('*');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        $query->orderBy('column_a', 'TEST');
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame('SELECT * FROM `table` ORDER BY `column_a`', $queryString);
    }

    public function testCanLimitQuery()
    {
        $query = new Query('table');
        try {
            $query->select('*');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        $query->limit(10);
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame('SELECT * FROM `table` LIMIT 10', $queryString);
    }

    public function testCanOffsetQuery()
    {
        $query = new Query('table');
        try {
            $query->select('*');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed creating query (%s)', $exception->getMessage()));
        }
        $query->limit(10)
            ->offset(20);
        try {
            $queryString = $query->getQuery();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed getting query string (%s)', $exception->getMessage()));
            return;
        }
        $this->assertSame("SELECT * FROM `table` LIMIT 10 OFFSET 20", $queryString);
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
