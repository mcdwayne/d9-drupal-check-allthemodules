<?php

namespace Drupal\drupal_content_sync\Plugin;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\drupal_content_sync\ExportIntent;
use Drupal\drupal_content_sync\ImportIntent;
use Drupal\drupal_content_sync\SyncIntent;
use Drupal\drupal_content_sync\Entity\Flow;
use Drupal\drupal_content_sync\Entity\MetaInformation;
use Drupal\drupal_content_sync\Exception\SyncException;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\RenderContext;
use Psr\Log\LoggerInterface;

/**
 * Common base class for entity handler plugins.
 *
 * @see \Drupal\drupal_content_sync\Annotation\EntityHandler
 * @see \Drupal\drupal_content_sync\Plugin\EntityHandlerInterface
 * @see plugin_api
 *
 * @ingroup third_party
 */
abstract class EntityHandlerBase extends PluginBase implements ContainerFactoryPluginInterface, EntityHandlerInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  protected $entityTypeName;
  protected $bundleName;
  protected $settings;

  /**
   * A sync instance.
   *
   * @var \Drupal\drupal_content_sync\Entity\Flow
   */
  protected $flow;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger         = $logger;
    $this->entityTypeName = $configuration['entity_type_name'];
    $this->bundleName     = $configuration['bundle_name'];
    $this->settings       = $configuration['settings'];
    $this->flow           = $configuration['sync'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('drupal_content_sync')
    );
  }

  /**
   * @inheritdoc
   */
  public function getAllowedExportOptions() {
    return [
      ExportIntent::EXPORT_DISABLED,
      ExportIntent::EXPORT_AUTOMATICALLY,
      ExportIntent::EXPORT_AS_DEPENDENCY,
      ExportIntent::EXPORT_MANUALLY,
    ];
  }

  /**
   * @inheritdoc
   */
  public function getAllowedImportOptions() {
    return [
      ImportIntent::IMPORT_DISABLED,
      ImportIntent::IMPORT_MANUALLY,
      ImportIntent::IMPORT_AUTOMATICALLY,
      ImportIntent::IMPORT_AS_DEPENDENCY,
    ];
  }

  /**
   * @inheritdoc
   */
  public function updateEntityTypeDefinition(&$definition) {
  }

  /**
   * @inheritdoc
   */
  public function getHandlerSettings() {
    return [];
  }

  /**
   * Check if the import should be ignored.
   *
   * @param \Drupal\drupal_content_sync\ImportIntent $intent
   *
   * @return bool
   *   Whether or not to ignore this import request.
   */
  protected function ignoreImport(ImportIntent $intent) {
    $reason = $intent->getReason();
    $action = $intent->getAction();

    if ($reason == ImportIntent::IMPORT_AUTOMATICALLY || $reason == ImportIntent::IMPORT_MANUALLY) {
      if ($this->settings['import'] != $reason) {
        // Once imported manually, updates will arrive automatically.
        if (($reason != ImportIntent::IMPORT_AUTOMATICALLY || $this->settings['import'] != ImportIntent::IMPORT_MANUALLY) || $action == SyncIntent::ACTION_CREATE) {
          return TRUE;
        }
      }
    }

    if ($action == SyncIntent::ACTION_UPDATE) {
      $behavior = $this->settings['import_updates'];
      if ($behavior == ImportIntent::IMPORT_UPDATE_IGNORE) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Import the remote entity.
   *
   * @inheritdoc
   */
  public function import(ImportIntent $intent) {
    $action = $intent->getAction();

    if ($this->ignoreImport($intent)) {
      return FALSE;
    }

    /**
     * @var \Drupal\Core\Entity\FieldableEntityInterface $entity
     */
    $entity = $intent->getEntity();

    if ($action == SyncIntent::ACTION_DELETE) {
      if ($entity) {
        return $this->deleteEntity($entity);
      }
      return FALSE;
    }

    if (!$entity) {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($intent->getEntityType());

      $base_data = [
        $entity_type->getKey('bundle') => $intent->getBundle(),
        $entity_type->getKey('label') => $intent->getField('title'),
      ];

      $base_data[$entity_type->getKey('uuid')] = $intent->getUuid();

      $storage = \Drupal::entityTypeManager()->getStorage($intent->getEntityType());
      $entity = $storage->create($base_data);

      if (!$entity) {
        throw new SyncException(SyncException::CODE_ENTITY_API_FAILURE);
      }

      $intent->setEntity($entity);
    }

    if (!$this->setEntityValues($intent)) {
      return FALSE;
    }

    // Make sure that menu items that were created for this entity before
    // the entity was available now reference this entity correctly by ID
    // {@see DefaultLinkHandler}.
    $menu_links = \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->loadByProperties(['link.uri' => 'internal:/' . $this->entityTypeName . '/' . $entity->uuid()]);
    foreach ($menu_links as $item) {
      /**
       * @var \Drupal\menu_link_content\Entity\MenuLinkContent $item
       */
      $item->set('link', 'entity:' . $this->entityTypeName . '/' . $entity->id());
      $item->set('enabled', 1);
      $item->save();
    }

    return TRUE;
  }

  /**
   * Delete a entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to delete.
   *
   * @throws \Drupal\drupal_content_sync\Exception\SyncException
   *
   * @return bool
   *   Returns TRUE or FALSE for the deletion process.
   */
  protected function deleteEntity(FieldableEntityInterface $entity) {
    try {
      $entity->delete();
    }
    catch (\Exception $e) {
      throw new SyncException(SyncException::CODE_ENTITY_API_FAILURE, $e);
    }
    return TRUE;
  }

  /**
   * Set the values for the imported entity.
   *
   * @param \Drupal\drupal_content_sync\SyncIntent $intent
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The translation of the entity.
   *
   * @see Flow::IMPORT_*
   *
   * @throws \Drupal\drupal_content_sync\Exception\SyncException
   *
   * @return bool
   *   Returns TRUE when the values are set.
   */
  protected function setEntityValues(ImportIntent $intent, FieldableEntityInterface $entity = NULL) {
    if (!$entity) {
      $entity = $intent->getEntity();
    }

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $field_definitions = $entityFieldManager->getFieldDefinitions($type, $bundle);

    $entity_type = \Drupal::entityTypeManager()->getDefinition($intent->getEntityType());
    $label       = $entity_type->getKey('label');
    if ($label && !$intent->shouldMergeChanges()) {
      $entity->set($label, $intent->getField('title'));
    }

    $static_fields = $this->getStaticFields();

    $is_translation = boolval($intent->getActiveLanguage());

    foreach ($field_definitions as $key => $field) {
      $handler = $this->flow->getFieldHandler($type, $bundle, $key);

      if (!$handler) {
        continue;
      }

      // This field cannot be updated.
      if (in_array($key, $static_fields) && $intent->getAction() != SyncIntent::ACTION_CREATE) {
        continue;
      }

      if ($is_translation && !$field->isTranslatable()) {
        continue;
      }

      // In the first run we can only set properties, not fields
      // Otherwise Drupal will throw Exceptions when using field references
      // if the translated entity has not been saved before..
      // Error message is: InvalidArgumentException: Invalid translation language (und) specified. in Drupal\Core\Entity\ContentEntityBase->getTranslation() (line 866 of /var/www/html/docroot/core/lib/Drupal/Core/Entity/ContentEntityBase.php).
      // Occurs when using translatable media entities referencing files.
      /*if (substr($key, 0, 6) == "field_") {
      continue;
      }*/

      $handler->import($intent);
    }

    try {
      $entity->save();
    }
    catch (\Exception $e) {
      throw new SyncException(SyncException::CODE_ENTITY_API_FAILURE, $e);
    }

    if ($entity instanceof TranslatableInterface && !$intent->getActiveLanguage()) {
      $languages = $intent->getTranslationLanguages();
      foreach ($languages as $language) {
        /**
         * If the provided entity is fieldable, translations are as well.
         *
         * @var \Drupal\Core\Entity\FieldableEntityInterface $translation
         */
        if ($entity->hasTranslation($language)) {
          $translation = $entity->getTranslation($language);
        }
        else {
          $translation = $entity->addTranslation($language);
        }

        $intent->changeTranslationLanguage($language);
        if (!$this->ignoreImport($intent)) {
          $this->setEntityValues($intent, $translation);
        }
      }

      // Delete translations that were deleted on master site.
      if (boolval($this->settings['import_deletion_settings']['import_deletion'])) {
        $existing = $entity->getTranslationLanguages(FALSE);
        foreach ($existing as &$language) {
          $language = $language->getId();
        }
        $languages = array_diff($existing, $languages);
        foreach ($languages as $language) {
          $entity->removeTranslation($language);
        }
      }

      $intent->changeTranslationLanguage();
    }

    return TRUE;
  }

  /**
   * @param \Drupal\drupal_content_sync\SyncIntent $intent
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *
   * @throws \Drupal\drupal_content_sync\Exception\SyncException
   */
  protected function setSourceUrl(ExportIntent $intent, FieldableEntityInterface $entity) {
    if ($entity->hasLinkTemplate('canonical')) {
      try {
        $url = $entity->toUrl('canonical', ['absolute' => TRUE])
          ->toString(TRUE)
          ->getGeneratedUrl();
        $intent->setField(
          'url',
          $url
        );
      }
      catch (\Exception $e) {
        throw new SyncException(SyncException::CODE_UNEXPECTED_EXCEPTION, $e);
      }
    }
  }

  /**
   * Check if the entity should not be ignored from the export.
   *
   * @param \Drupal\drupal_content_sync\SyncIntent $intent
   *   The API Unify Request.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity that could be ignored.
   * @param string $reason
   *   The reason why the entity should be ignored from the export.
   * @param string $action
   *   The action to apply.
   *
   * @return bool
   *   Whether or not to ignore this export request.
   */
  protected function ignoreExport(ExportIntent $intent) {
    $reason = $intent->getReason();
    $action = $intent->getAction();

    if ($reason == ExportIntent::EXPORT_AUTOMATICALLY || $reason == ExportIntent::EXPORT_MANUALLY) {
      if ($this->settings['export'] != $reason) {
        return TRUE;
      }
    }

    if ($action == SyncIntent::ACTION_UPDATE) {
      $behavior = $this->settings['import_updates'];
      if ($behavior == ImportIntent::IMPORT_UPDATE_FORCE_UNLESS_OVERRIDDEN) {
        $meta_info = MetaInformation::getInfoForEntity(
          $intent->getEntityType(),
          $intent->getUuid(),
          $intent->getFlow(),
          $intent->getPool()
        );
        // The flag means to overwrite locally, so changes should not be pushed.
        if ($meta_info && !$meta_info->isSourceEntity()) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * @inheritdoc
   */
  public function getForbiddenFields() {
    /**
     * @var \Drupal\Core\Entity\EntityTypeInterface $entity_type_entity
     */
    $entity_type_entity = \Drupal::service('entity_type.manager')
      ->getStorage($this->entityTypeName)
      ->getEntityType();
    return [
      // These basic fields are already taken care of, so we ignore them
      // here.
      $entity_type_entity->getKey('id'),
      $entity_type_entity->getKey('revision'),
      $entity_type_entity->getKey('bundle'),
      $entity_type_entity->getKey('uuid'),
      $entity_type_entity->getKey('label'),
      // These are not relevant or misleading when synchronized.
      'revision_default',
      'revision_translation_affected',
      'content_translation_outdated',
    ];
  }

  /**
   * Get a list of fields that can't be updated.
   *
   * @return string[]
   */
  protected function getStaticFields() {
    return [
      'default_langcode',
      'langcode',
    ];
  }

  /**
   * @inheritdoc
   */
  public function export(ExportIntent $intent, FieldableEntityInterface $entity = NULL) {
    if ($this->ignoreExport($intent)) {
      return FALSE;
    }

    if (!$entity) {
      $entity = $intent->getEntity();
    }

    // Base info.
    $intent->setField('title', $entity->label());

    // Menu items.
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $menu_items = $menu_link_manager->loadLinksByRoute('entity.' . $this->entityTypeName . '.canonical', [$this->entityTypeName => $entity->id()]);
    foreach ($menu_items as $menu_item) {
      if (!($menu_item instanceof MenuLinkContent)) {
        continue;
      }

      /**
       * @var \Drupal\menu_link_content\Entity\MenuLinkContent $item
       */
      $item = \Drupal::service('entity.repository')
        ->loadEntityByUuid('menu_link_content', $menu_item->getDerivativeId());
      if (!$item) {
        continue;
      }

      $intent->embedEntity($item, FALSE);
    }

    // Preview.
    $entityTypeManager = \Drupal::entityTypeManager();
    $view_builder = $entityTypeManager->getViewBuilder($this->entityTypeName);

    // Get specified view mode.
    $flow = $this->flow;
    $view_mode = $flow->getPreviewType($entity->getEntityTypeId(), $entity->bundle());

    $preview = $view_builder->view($entity, $view_mode);
    $rendered = \Drupal::service('renderer');
    $html = $rendered->executeInRenderContext(
      new RenderContext(),
      function () use ($rendered, $preview) {
        return $rendered->render($preview);
      }
    );
    $intent->setField('preview', $html);

    // Source URL.
    $this->setSourceUrl($intent, $entity);

    // Fields.
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $type               = $entity->getEntityTypeId();
    $bundle             = $entity->bundle();
    $field_definitions  = $entityFieldManager->getFieldDefinitions($type, $bundle);

    foreach ($field_definitions as $key => $field) {
      $handler = $this->flow->getFieldHandler($type, $bundle, $key);

      if (!$handler) {
        continue;
      }

      $handler->export($intent);
    }

    // Translations.
    if (!$intent->getActiveLanguage() &&
      $entity instanceof TranslatableInterface) {
      $languages = array_keys($entity->getTranslationLanguages(FALSE));

      foreach ($languages as $language) {
        $intent->changeTranslationLanguage($language);
        /**
         * @var \Drupal\Core\Entity\FieldableEntityInterface $translation
         */
        $translation = $entity->getTranslation($language);
        $this->export($intent, $translation);
      }

      $intent->changeTranslationLanguage();
    }

    return TRUE;
  }

}
