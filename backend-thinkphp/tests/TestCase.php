<?php
namespace tests;

use PHPUnit\Framework\TestCase;
use think\App;
use think\facade\Db;

class TestCase extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new App();
        $this->app->initialize();

        $this->setupTestDatabase();
    }

    protected function setupTestDatabase()
    {
        $config = [
            'default' => 'mysql',
            'connections' => [
                'mysql' => [
                    'type' => 'mysql',
                    'hostname' => '127.0.0.1',
                    'database' => 'smart_store_test',
                    'username' => 'root',
                    'password' => '',
                    'hostport' => '3306',
                    'charset' => 'utf8mb4',
                    'prefix' => 'ss_',
                ],
            ],
        ];

        Db::setConfig($config);
    }

    protected function tearDown(): void
    {
        Db::close();
        parent::tearDown();
    }
}
