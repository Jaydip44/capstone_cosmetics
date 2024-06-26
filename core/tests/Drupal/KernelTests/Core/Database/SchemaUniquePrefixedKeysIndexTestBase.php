<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\DatabaseException;

/**
 * Tests adding UNIQUE keys to tables.
 *
 * @group Database
 */
abstract class SchemaUniquePrefixedKeysIndexTestBase extends DriverSpecificDatabaseTestBase {

  /**
   * Set the value used to test the schema unique prefixed keys index.
   *
   * The basic syntax of passing an array (field, prefix length) as a key column
   * specifier must always be accepted by the driver. However, due to technical
   * limitations, some drivers may choose to ignore them.
   *
   * '123456789 bar' if the current database (driver) will conform to the prefix
   * length specified as part of a key column specifier, '123456789 foo' if it
   * will be ignored.
   */
  protected string $columnValue;

  /**
   * Tests UNIQUE keys put directly on the table definition.
   */
  public function testCreateTable(): void {
    $this->connection->schema()->createTable('test_unique', [
      'fields' => [
        'field' => [
          'type' => 'varchar',
          'length' => 50,
        ],
      ],
      'unique keys' => [
        'field' => [['field', 10]],
      ],
    ]);

    $this->checkUniqueConstraintException('test_unique', 'field');
  }

  /**
   * Tests adding a UNIQUE key to an existing table.
   */
  public function testAddUniqueKey(): void {
    $this->connection->schema()
      ->addUniqueKey('test_people', 'job', [['job', 10]]);

    $this->checkUniqueConstraintException('test_people', 'job');
  }

  /**
   * Tests adding a new field with UNIQUE key.
   */
  public function testAddField(): void {
    $field_spec = [
      'type' => 'varchar',
      'length' => 50,
    ];
    $keys_spec = [
      'unique keys' => [
        'field' => [['field', 10]],
      ],
    ];
    $this->connection->schema()
      ->addField('test', 'field', $field_spec, $keys_spec);

    $this->checkUniqueConstraintException('test', 'field');
  }

  /**
   * Tests changing a field to add a UNIQUE key.
   */
  public function testChangeField(): void {
    $field_spec = [
      'description' => "The person's job",
      'type' => 'varchar_ascii',
      'length' => 50,
      'not null' => TRUE,
      'default' => '',
    ];
    $keys_spec = [
      'unique keys' => [
        'job' => [['job', 10]],
      ],
    ];
    $this->connection->schema()
      ->changeField('test_people', 'job', 'job', $field_spec, $keys_spec);

    $this->checkUniqueConstraintException('test_people', 'job');
  }

  /**
   * Verifies that inserting the same value/prefix twice causes an exception.
   *
   * @param string $table
   *   The table to insert into.
   * @param string $column
   *   The column on that table that has a UNIQUE index. If prefix lengths are
   *   accepted for UNIQUE keys on the current database, the prefix length for
   *   the field is expected to be set to 10 characters.
   */
  protected function checkUniqueConstraintException(string $table, string $column): void {
    $this->connection->insert($table)
      ->fields([
        $column => '1234567890 foo',
      ])
      ->execute();

    $this->expectException(DatabaseException::class);
    $this->connection->insert($table)
      ->fields([
        $column => $this->columnValue,
      ])
      ->execute();
  }

}
