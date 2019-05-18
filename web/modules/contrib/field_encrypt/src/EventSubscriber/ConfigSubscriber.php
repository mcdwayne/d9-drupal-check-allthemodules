<?php

namespace Drupal\field_encrypt\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\DynamicallyFieldableEntityStorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_encrypt\EncryptedFieldValueManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Updates existing data when field encryption settings are updated.
 */
class ConfigSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The EncryptedFieldValue entity manager.
   *
   * @var \Drupal\field_encrypt\EncryptedFieldValueManagerInterface
   */
  protected $encryptedFieldValueManager;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new ConfigSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   * @param \Drupal\field_encrypt\EncryptedFieldValueManagerInterface $encrypted_field_value_manager
   *   The EncryptedFieldValue entity manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query, QueueFactory $queue_factory, TranslationInterface $translation, EncryptedFieldValueManagerInterface $encrypted_field_value_manager, EntityManagerInterface $entity_manager, StateInterface $state) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
    $this->queueFactory = $queue_factory;
    $this->stringTranslation = $translation;
    $this->encryptedFieldValueManager = $encrypted_field_value_manager;
    $this->entityManager = $entity_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 0);
    $events[ConfigEvents::DELETE][] = array('onConfigDelete', 0);
    return $events;
  }

  /**
   * React on the configuration save event.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   *
   * @todo why is this not just using hook_field_storage_update?
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if (substr($config->getName(), 0, 14) == 'field.storage.') {
      // Get the original field_encrypt configuration.
      $original_config = $config->getOriginal('third_party_settings.field_encrypt');

      // Update the uncacheable entity types list.
      $this->setUncacheableEntityTypes();

      // Update existing entities, if data encryption settings changed.
      if ($this->encryptionConfigChanged($config)) {
        // Get the entity type and field from the changed config key.
        $storage_name = substr($config->getName(), 14);
        list($entity_type, $field_name) = explode('.', $storage_name, 2);

        // Load the FieldStorageConfig entity that was updated.
        $field_storage_config = FieldStorageConfig::loadByName($entity_type, $field_name);
        if ($field_storage_config) {
          if ($field_storage_config->hasData()) {
            // Get entities that need updating, because they contain the field
            // that has its field encryption settings updated.
            $query = $this->entityQuery->get($entity_type);
            // Check if the field is present.
            $query->exists($field_name);
            // Make sure to get all revisions for revisionable entities.
            if ($this->entityTypeManager->getDefinition($entity_type)
              ->hasKey('revision')
            ) {
              $query->allRevisions();
            }
            $entity_ids = $query->execute();

            if (!empty($entity_ids)) {
              // Call the Queue API and add items for processing.
              /** @var \Drupal\Core\Queue\QueueInterface $queue */
              $queue = $this->queueFactory->get('cron_encrypted_field_update');

              foreach (array_keys($entity_ids) as $entity_id) {
                $data = [
                  "entity_id" => $entity_id,
                  "field_name" => $field_name,
                  "entity_type" => $entity_type,
                  "original_config" => $original_config,
                ];
                $queue->createItem($data);
              }
            }

            drupal_set_message($this->t('Updates to entities with existing data for this field have been queued to be processed. You should immediately <a href=":url">run this process manually</a>. Alternatively, the updates will be performed automatically by cron.', array(
              ':url' => Url::fromRoute('field_encrypt.field_update')
                ->toString()
            )));
          }
        }
      }
    }
  }

  /**
   * React on the configuration delete event.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if (substr($config->getName(), 0, 14) == 'field.storage.') {
      // Get the entity type and field from the changed config key.
      $storage_name = substr($config->getName(), 14);
      list($entity_type, $field_name) = explode('.', $storage_name, 2);
      $this->encryptedFieldValueManager->deleteEncryptedFieldValuesForField($entity_type, $field_name);
    }
  }

  /**
   * Check whether the field encryption config has changed.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config to check.
   *
   * @return bool
   *   Whether the config has changed.
   */
  protected function encryptionConfigChanged(Config $config) {
    // Get both the newly saved and original field_encrypt configuration.
    $new_config = $config->get('third_party_settings.field_encrypt');
    $original_config = $config->getOriginal('third_party_settings.field_encrypt');

    // Don't compare 'uncacheable' setting.
    unset($new_config['uncacheable']);
    unset($original_config['uncacheable']);
    return $new_config !== $original_config;
  }

  /**
   * Figure out which entity types are uncacheable due to encrypted fields.
   */
  protected function setUncacheableEntityTypes() {
    $types = [];
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $storage_class = $this->entityManager->createHandlerInstance($entity_type->getStorageClass(), $entity_type);
        if ($storage_class instanceof DynamicallyFieldableEntityStorageInterface) {
          // Query by filtering on the ID as this is more efficient than filtering
          // on the entity_type property directly.
          $ids = $this->entityQuery->get('field_storage_config')
            ->condition('id', $entity_type->id() . '.', 'STARTS_WITH')
            ->execute();
          // Fetch all fields on entity type.
          $field_storages = FieldStorageConfig::loadMultiple($ids);
          if ($field_storages) {
            foreach ($field_storages as $storage) {
              // Check if field is encrypted.
              if ($storage->getThirdPartySetting('field_encrypt', 'uncacheable', FALSE) == TRUE) {
                // If there is an encrypted field, mark this entity type as
                // uncacheable.
                $type = $storage->getTargetEntityTypeId();
                $types[$type] = $type;
              }
            }
          }
        }
      }
    }
    $this->state->set('uncacheable_entity_types', $types);
  }

}
