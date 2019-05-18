<?php

namespace Drupal\consent\Storage;

use Drupal\consent\ConsentInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * A relational Sql consent storage implementation.
 */
class SqlConsentStorage extends ConsentStorageBase {

  /**
   * The table which stores user consents.
   *
   * @var string
   */
  static protected $table = 'consent';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * SqlConsentStorage constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(ModuleHandlerInterface $module_handler, Connection $database) {
    parent::__construct($module_handler);
    $this->db = $database;
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave(ConsentInterface $consent) {
    try {
      return $this->doWrite($consent);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if ($this->ensureTableExists()) {
        try {
          return $this->doWrite($consent);
        }
        catch (\Exception $e) {}
      }
      // Some other failure that we can not recover from.
      throw new ConsentStorageException($e->getMessage(), $e->getCode());
    }
  }

  /**
   * Writes the given consent information into the database.
   *
   * @param \Drupal\consent\ConsentInterface $consent
   *
   * @throws \Exception
   */
  protected function doWrite(ConsentInterface $consent) {
    if ($consent->isNew()) {
      $this->insert($consent);
    }
    else {
      $this->update($consent);
    }
  }

  /**
   * @param \Drupal\consent\ConsentInterface $consent
   *
   * @throws \Exception
   */
  protected function insert(ConsentInterface $consent) {
    $this->db->insert(static::$table)->fields($consent->storableValues())->execute();
  }

  /**
   * @param \Drupal\consent\ConsentInterface $consent
   *
   * @throws \Exception
   */
  protected function update(ConsentInterface $consent) {
    $this->db->update(static::$table)->fields($consent->storableValues())->where('cid = :cid', [':cid' => $consent->getId()])->execute();
  }

  /**
   * Check if the table exists and create it if not.
   *
   * @return bool
   *   TRUE if the table was created, FALSE otherwise.
   *
   * @throws \Drupal\prepared_data\Storage\StorageException
   *   If a database error occurs.
   */
  protected function ensureTableExists() {
    try {
      if (!$this->db->schema()->tableExists(static::$table)) {
        $this->db->schema()->createTable(static::$table, static::schemaDefinition());
        return TRUE;
      }
    }
    // If another process has already created the table, attempting to
    // recreate it will throw an exception. In this case just catch the
    // exception and do nothing.
    catch (SchemaObjectExistsException $e) {
      return TRUE;
    }
    catch (\Exception $e) {
      throw new ConsentStorageException($e->getMessage(), NULL, $e);
    }
    return FALSE;
  }

  /**
   * Returns the Sql storage schema definition.
   *
   * @return array
   *   The storage schema definition.
   */
  static protected function schemaDefinition() {
    $schema = [
      'description' => 'Contains user consents.',
      'fields' => [
        'cid' => [
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Unique consent storage ID.',
        ],
        'uid' => [
          'description' => 'The {users}.uid.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'timestamp' => [
          'description' => 'Time of consent.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'timezone' => [
          'description' => 'Timezone of timestamp.',
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
        ],
        'client_ip' => [
          'description' => 'The client IP address.',
          'type' => 'varchar',
          'length' => 45,
          'not null' => TRUE,
        ],
        'category' => [
          'description' => 'The categorized type of consent.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ],
        'domain' => [
          'description' => 'The domain where the user gave consent.',
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
        ],
        'further' => [
          'description' => 'Further information about the consent as Json.',
          'type' => 'text',
          'size' => 'normal',
          'not null' => FALSE,
        ],
      ],
      'primary key' => ['cid'],
    ];
    return $schema;
  }

}
