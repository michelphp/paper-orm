<?php

namespace Test\Michel\PaperORM\Common;

use Michel\PaperORM\Parser\DSNParser;
use Michel\UniTester\TestCase;

class DSNParserTest extends TestCase
{

    protected function setUp(): void
    {
        // TODO: Implement setUp() method.
    }

    protected function tearDown(): void
    {
        // TODO: Implement tearDown() method.
    }

    protected function execute(): void
    {
        $this->testParse();
    }

    public function testParse()
    {
         $this->assertEquals(
             [ 'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306, 'user' => 'db_user', 'password' => 'db_password', 'path' => 'db_name', 'memory' => false, 'options' => [ 'serverVersion' => '8.0.37' ]],
             DSNParser::parse("mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=8.0.37")
         );
        $this->assertEquals(
            [ 'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306, 'user' => 'db_user', 'password' => 'db_password', 'path' => 'db_name', 'memory' => false, 'options' => [ 'serverVersion' => 'mariadb-10.5.8' ]],
            DSNParser::parse("mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=mariadb-10.5.8")
        );
        $this->assertEquals(
            [ 'driver' => 'sqlite', 'path' => 'app.db', 'memory' => false, 'options' => []],
            DSNParser::parse("sqlite://app.db")
        );

        $this->assertEquals(
            [ 'driver' => 'sqlite', 'path' => '/app.db', 'memory' => false, 'options' => []],
            DSNParser::parse("sqlite:///app.db")
        );
        $this->assertEquals(
            [ 'driver' => 'sqlite', 'path' => 'var/app.db', 'memory' => false, 'options' => []],
            DSNParser::parse("sqlite://var/app.db")
        );
        $this->assertEquals(
            [ 'driver' => 'sqlite', 'path' => null, 'memory' => true, 'options' => []],
            DSNParser::parse("sqlite:///:memory:")
        );

        $this->assertEquals(
            [ 'driver' => 'sqlite', 'path' => '/app.db', 'memory' => false, 'options' => [
                'mode' => 'ro',
                'cache' => 'shared'
            ]],
            DSNParser::parse('sqlite:///app.db?mode=ro&cache=shared')
        );

        $this->assertEquals(
            [
                'driver' => 'sql',
                'host' => '127.0.0.1',
                'port' => 5002,
                'user' => 'root',
                'password' => '',
                'path' => '/dbs/mydb',
                'memory' => false,
                'options' =>
                    [
                        'charset_utf8' => '1',
                    ],
            ],
            DSNParser::parse('sql://root:@127.0.0.1:5002//dbs/mydb?charset_utf8=1')
        );
    }
}
