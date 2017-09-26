<?php

namespace Tests\RDB;

use PHPUnit\Framework\TestCase;
use Tdw\RDB\Database;
use Tdw\RDB\Result\Insert as InsertResult;

class DatabaseTest extends TestCase
{
    /**
     * @var \PDO
     */
    private static $pdo;

    /**
     * @var Database
     */
    private $database;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$pdo = new \PDO('sqlite::memory:');
        //self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec(
            "CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title STRING,description STRING)"
        );
    }

    public static function tearDownAfterClass()
    {
        self::$pdo->exec("DROP TABLE posts");
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        parent::setUp();
        $this->database = new Database(self::$pdo);
        $this->database->beginTransaction();
    }

    public function tearDown()
    {
        $this->database->rollBack();
        $this->database = null;
        parent::tearDown();
    }

    /**
     * @group integration-database-insert
     */
    public function testShouldInsertRowInDatabase()
    {
        //arrange
        $parameters = [
            'title' => 'Title RDB Test',
            'description' => 'Description RDB Test'
        ];
        $insertStatement = $this->database->insert('posts', $parameters);
        /**@var \Tdw\RDB\Result\Insert $result */
        $result = $insertStatement->execute();

        //act
        $rowCount = 1;

        //assert
        $this->assertEquals($rowCount, $result->rowCount());
        $this->assertTrue(is_int($result->lastInsertId()));
    }

    /**
     * @group integration-database-select
     */
    public function testShouldReturnPostsFromDatabase()
    {
        //arrange
        $post = [
            'title' => 'Title RDB Test',
            'description' => 'Description RDB Test'
        ];
        $post2 = [
            'title' => 'Title RDB Test 2',
            'description' => 'Description RDB Test 2'
        ];
        $this->insertRowAndReturnInsertResult($post);
        $this->insertRowAndReturnInsertResult($post2);
        $selectStatement = $this->database->select('posts', ['title','description']);
        /**@var \Tdw\RDB\Result\Select $result */
        $result = $selectStatement->execute();
        //act
        $expected = [$post,$post2];
        $actual = $result->fetchAll();
        //assert
        $this->assertArraySubset($expected, $actual);
    }

    /**
     * @group integration-database-select-where
     */
    public function testShouldReturnPostsFromDatabaseWithWhere()
    {
        //arrange
        $post = [
            'title' => 'Title RDB Test',
            'description' => 'Description RDB Test'
        ];
        $post2 = [
            'title' => 'Title RDB Test 2',
            'description' => 'Description RDB Test 2'
        ];
        /**@var \Tdw\RDB\Result\Insert $resultInsert */
        $this->insertRowAndReturnInsertResult($post2);
        $resultInsert = $this->insertRowAndReturnInsertResult($post);
        $selectStatement = $this->database->select('posts', ['title','description']);
        $selectStatement->where('id', '=', $resultInsert->lastInsertId());
        /**@var \Tdw\RDB\Result\Select $result */
        $result = $selectStatement->execute();

         //act
        $expected = $post;
        $actual = $result->fetch();

        //assert
        $this->assertArraySubset($expected, $actual);
    }

    /**
     * @group integration-database-update
     */
    public function testShouldUpdatePostOnDatabase()
    {
        //arrange
        $post = [
            'title' => 'Title RDB Test',
            'description' => 'Description RDB Test'
        ];
        $post2 = [
            'title' => 'Title RDB Test 2',
            'description' => 'Description RDB Test 2'
        ];
        /**@var \Tdw\RDB\Result\Insert $resultInsert */
        $resultInsert = $this->insertRowAndReturnInsertResult($post);
        $lastInsertId = $resultInsert->lastInsertId();

        /**@var \Tdw\RDB\Result\Update $resultUpdate */
        $updateStatement = $this->database->update('posts', $post2, ['id'=>$lastInsertId]);
        $resultUpdate = $updateStatement->execute();

        //act
        $expected = 1;

        //assert
        $this->assertEquals($expected, $resultUpdate->rowCount());
    }

    /**
     * @group integration-database-delete
     */
    public function testShouldDeletePostOnDatabase()
    {
        //arrange
        $post = [
            'title' => 'Title RDB Test',
            'description' => 'Description RDB Test'
        ];
        /**@var \Tdw\RDB\Result\Insert $resultInsert */
        $resultInsert = $this->insertRowAndReturnInsertResult($post);
        $lastInsertId = $resultInsert->lastInsertId();

        /**@var \Tdw\RDB\Result\Delete $resultDelete */
        $deleteStatement = $this->database->delete('posts', ['id'=>$lastInsertId]);
        $resultDelete = $deleteStatement->execute();

        //act
        $expected = 1;

        //assert
        $this->assertEquals($expected, $resultDelete->rowCount());
    }

    private function insertRowAndReturnInsertResult(array $parameters = []): InsertResult
    {
        $parameters = empty($parameters) ? [
            'title' => 'Title RDB Test',
            'description' => 'Description RDB Test'
        ] : $parameters;
        $insertStatement = $this->database->insert('posts', $parameters);
        return $insertStatement->execute();
    }
}