<?php

namespace Drupal\precision_modifier\services;

use \Drupal\Core\Entity\EntityTypeManagerInterface;
use \Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Config\ConfigFactoryInterface;

class PrecisionModifierService implements PrecisionModifierServiceInterface {
  use StringTranslationTrait;
  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;
  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  protected $messenger;

  /**
   * PrecisionModifierService constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entityTypeManager,
                              ConfigFactoryInterface $configFactory, MessengerInterface $messenger) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
  }

  /**
   * @inheritdoc
   */
  public function increasePrecision($field, $bundle, $precision, $scale = 0) {
    $database = $this->connection;
    $tables = [
      "node_revision__{$field}",
      "node__{$field}",
    ];
    $settings = ['precision' => $precision, 'scale' => $scale,];
    $existingData = [];

    foreach ($tables as $table) {
      $existingData[$table] = $database->select($table)
        ->fields($table)
        ->execute()
        ->fetchAll(\PDO::FETCH_ASSOC);

      $database->truncate($table)->execute();
    }

    $config = $this->configFactory->getEditable('field.storage.node.'.$field);
    $config->set('settings', $settings)->save();
    $fieldStorage = FieldStorageConfig::loadByName('node', $field);
    $fieldStorage->set('settings', $settings);
    $fieldStorage->save();
    $this->entityTypeManager->clearCachedDefinitions();

    // Restore the data.
    foreach ($tables as $table) {
      $insert_query = $database
        ->insert($table)
        ->fields(array_keys(end($existingData[$table])));
      foreach ($existingData[$table] as $row) {
        $insert_query->values(array_values($row));
      }
      $insert_query->execute();
    }
    $this->entityTypeManager->getStorage('entity_form_display')
      ->load('node' . '.' . $bundle . '.' . 'default')
      ->setComponent($field, ['region' => 'content'])->save();
    $this->entityTypeManager->getStorage('entity_view_display')
      ->load('node' . '.' . $bundle . '.' . 'default')
      ->setComponent($field, ['region' => 'content'])->save();
    $this->messenger->addMessage($this->t('Successfully increased precision or scale'));
  }
}