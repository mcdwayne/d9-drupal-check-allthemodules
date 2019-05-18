<?php

namespace Drupal\acquia_contenthub\EventSubscriber\EntityImport;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\EntityImportEvent;
use Drupal\language\ContentLanguageSettingsInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles ContentLanguageSetting entity saves to apply related schema.
 *
 * This is an event-centric copy of the code found in:
 * https://www.drupal.org/project/drupal/issues/2599228 and can be removed once
 * that issue lands.
 *
 * @todo remove after https://www.drupal.org/project/drupal/issues/2599228 lands.
 */
class ContentLanguageSettings implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::ENTITY_IMPORT_NEW][] = 'onImportNew';
    $events[AcquiaContentHubEvents::ENTITY_IMPORT_UPDATE][] = 'onImportUpdate';
    return $events;
  }

  /**
   * Handles the importing of new ContentLanguageSettings entities.
   *
   * @param \Drupal\acquia_contenthub\Event\EntityImportEvent $event
   *   The entity import event.
   */
  public function onImportNew(EntityImportEvent $event) {
    $settings = $event->getEntity();
    // Early return if this isn't the class of entity we care about.
    if (!$settings instanceof ContentLanguageSettingsInterface) {
      return;
    }
    // @see \Drupal\content_translation\ContentTranslationManager::isEnabled()
    if ($settings->getThirdPartySetting('content_translation', 'enabled', FALSE)) {
      $this->processSettings($settings);
    }
  }

  /**
   * Handles the importing of existing ContentLanguageSettings entities.
   *
   * @param \Drupal\acquia_contenthub\Event\EntityImportEvent $event
   *   The entity import event.
   */
  public function onImportUpdate(EntityImportEvent $event) {
    $settings = $event->getEntity();
    // Early return if this isn't the class of entity we care about.
    if (!$settings instanceof ContentLanguageSettingsInterface) {
      return;
    }
    /** @var \Drupal\language\ContentLanguageSettingsInterface $original_settings */
    $original_settings = $settings->get('original');
    // If the original settings don't exist, treat this as though it were new.
    if (!$original_settings) {
      $this->onImportNew($event);
      return;
    }
    // @see \Drupal\content_translation\ContentTranslationManager::isEnabled()
    if ($settings->getThirdPartySetting('content_translation', 'enabled', FALSE) && !$original_settings->getThirdPartySetting('content_translation', 'enabled', FALSE)) {
      $this->processSettings($settings);
    }
  }

  /**
   * Applies appropriate schema for content entities that support multilingual.
   *
   * This process appears to normally happen through the form submission
   * process which is unreliable for entity imports, so we've replicated the
   * basic functionality until core solves this problem.
   *
   * @param \Drupal\language\ContentLanguageSettingsInterface $settings
   *   The ContentLanguageSettings entity.
   */
  protected function processSettings(ContentLanguageSettingsInterface $settings) {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
    $field_manager = \Drupal::service('entity_field.manager');
    /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $schema_repository */
    $schema_repository = \Drupal::service('entity.last_installed_schema.repository');
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    $entity_type_id = $settings->getTargetEntityTypeId();

    $field_manager->useCaches(FALSE);
    $storage_definitions = $field_manager->getFieldStorageDefinitions($entity_type_id);
    $field_manager->useCaches(TRUE);
    $installed_storage_definitions = $schema_repository->getLastInstalledFieldStorageDefinitions($entity_type_id);

    foreach (array_diff_key($storage_definitions, $installed_storage_definitions) as $storage_definition) {
      // @var $storage_definition \Drupal\Core\Field\FieldStorageDefinitionInterface
      if ($storage_definition->getProvider() == 'content_translation') {
        $definition_update_manager->installFieldStorageDefinition($storage_definition->getName(), $entity_type_id, 'content_translation', $storage_definition);
      }
    }
  }

}
