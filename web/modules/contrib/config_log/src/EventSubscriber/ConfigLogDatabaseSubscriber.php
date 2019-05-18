<?php

/**
 * @file
 * Contains Drupal\config_log\EventSubscriber\ConfigLogDatabaseSubscriber.
 */

namespace Drupal\config_log\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\DumpException;
use Symfony\Component\Yaml\Parser;

/**
 * ConfigLog subscriber for configuration CRUD events.
 */
class ConfigLogDatabaseSubscriber extends ConfigLogSubscriberBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * A shared YAML dumper instance.
   *
   * @var Symfony\Component\Yaml\Dumper
   */
  protected $dumper;

  /**
   * The type of the subscriber.
   */
  public static $type = 'custom';

  /**
   * Constructs the ConfigLogDatabaseSubscriber object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory services.
   */
  function __construct(Connection $database, ConfigFactoryInterface $config_factory) {
    $this->database = $database;
    parent::__construct($config_factory);
  }

  /**
   * Encode data as YAML
   *
   * @see: Drupal\Core\Config\FileStorage:encode()
   *
   * @throws Symfony\Component\Yaml\Exception\DumpException
   */
  protected function encode($data) {
    if (!isset($this->dumper)) {
      // Set Yaml\Dumper's default indentation for nested nodes/collections to
      // 2 spaces for consistency with Drupal coding standards.
      $this->dumper = new Dumper(2);
    }
    // The level where you switch to inline YAML is set to PHP_INT_MAX to ensure
    // this does not occur. Also set the exceptionOnInvalidType parameter to
    // TRUE, so exceptions are thrown for an invalid data type.
    return $this->dumper->dump($data, PHP_INT_MAX, 0, TRUE);
  }

  /**
   * Insert record to the database if the table exists.
   *
   * @param array $values
   * @throws \Exception
   */
  protected function insertRecord(array $values) {
    // When uninstalling the module the table is removed before this code runs.
    // For now this check is there for all inserts, this can be improved later.
    if ($this->database->schema()->tableExists('config_log')) {
      $this->database
        ->insert('config_log')
        ->fields(array_keys($values))
        ->values($values)
        ->execute();
    }
  }

  /**
   * React to configuration ConfigEvent::SAVE events.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event to process.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    if (!$this->isEnabled()) {
      return;
    }
    $config = $event->getConfig();
    if ($this->isIgnored($config->getName())) {
      return;
    }

    $values = array(
      'uid' => \Drupal::currentUser()->id(),
      'operation' => $config->isNew() ? 'create' : 'update',
      'name' => $config->getName(),
      'data' => $this->encode($config->get()),
    );
    $this->insertRecord($values);
  }

  /**
   * React to configuration ConfigEvent::RENAME events.
   *
   * @param \Drupal\Core\Config\ConfigRenameEvent $event
   *   The event to process.
   */
  public function onConfigRename(ConfigRenameEvent $event) {
    if (!$this->isEnabled()) {
      return;
    }
    $config = $event->getConfig();
    if ($this->isIgnored($config->getName())) {
      return;
    }

    $values = array(
      'uid' => \Drupal::currentUser()->id(),
      'operation' => 'rename',
      'name' => $config->getName(),
      'old_name' => $event->getOldName(),
      'data' => $this->encode($config->get()),
    );
    $this->insertRecord($values);
  }

  /**
   * React to configuration ConfigEvent::DELETE events.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event to process.
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    if (!$this->isEnabled()) {
      return;
    }
    $config = $event->getConfig();
    if ($this->isIgnored($config->getName())) {
      return;
    }

    $values = array(
      'uid' => \Drupal::currentUser()->id(),
      'operation' => 'delete',
      'name' => $config->getName(),
    );
    $this->insertRecord($values);
  }

  /**
   * React to configuration ConfigEvent::IMPORT events.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The event to process.
   */
  public function onConfigImport(ConfigImporterEvent $event) {
    if (!$this->isEnabled() || $this->isConfigImportIgnored()) {
      return;
    }

    // Get the changelist and insert records for each change if not ignored.
    foreach ($event->getChangelist() as $operation => $config_names) {
      array_map(
        function ($config_name) use ($operation) {
          if (!$this->isIgnored($config_name)) {
            $this->insertRecord([
              'uid' => 1,
              'operation' => $operation,
              'name' => $config_name,
            ]);
          }
        },
        $config_names
      );
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 10);
    $events[ConfigEvents::DELETE][] = array('onConfigDelete', 10);
    $events[ConfigEvents::RENAME][] = array('onConfigRename', 10);
    $events[ConfigEvents::IMPORT][] = array('onConfigImport', 10);
    return $events;
  }
}
