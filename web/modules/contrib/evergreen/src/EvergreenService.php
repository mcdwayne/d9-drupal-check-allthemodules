<?php

namespace Drupal\evergreen;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\evergreen\EvergreenServiceInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Cache\CacheFactory;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\evergreen\Entity\EvergreenConfig;
use Drupal\evergreen\Entity\EvergreenConfigInterface;
use Drupal\evergreen\Entity\EvergreenContent;
use Drupal\evergreen\Entity\EvergreenContentInterface;
use Drupal\evergreen\ExpiryParser;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Evergreen service.
 */
class EvergreenService implements ContainerInjectionInterface, EvergreenServiceInterface {

  protected $evergreen;
  protected $entityQuery;
  protected $entityTypeManager;

  protected $skipForms = [];

  /**
   * Internal cache for configured entities.
   */
  protected $configuredEntities = [];

  /**
   * Constructor
   */
  public function __construct(PluginManagerInterface $evergreen, QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager) {
    $this->evergreen = $evergreen;
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.evergreen'),
      $container->get('entity.query'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Check if we should have the evergreen form on this form and add it...
   */
  public function addForm(array &$form, FormStateInterface $form_state, $form_id) {
    $form_object = $form_state->getFormObject();
    if (method_exists($form_object, 'getEntity')) {
      $entity = $form_state->getFormObject()->getEntity();
      if ($entity instanceof ContentEntityBase) {
        if ($this->isEnabled($entity)) {
          evergreen_add_form($form, $form_state, $form_id);
          return;
        }
      }
    }

    // statically cache forms that we skip in case they come up again...
    if (!in_array($form_id, $this->skipForms)) {
      $this->skipForms[] = $form_id;
    }
  }

  /**
   * Returns an array of entity types that are configured to use the module.
   *
   * @return array
   *   An associative array of configured entity types.
   *
   * @SuppressWarnings(StaticAccess)
   */
  public function getConfiguredEntityTypes($type = NULL) {
    $type_label = $type;
    if ($type === NULL) {
      $type_label = '*';
    }
    if (isset($this->configuredEntities[$type_label])) {
      return $this->configuredEntities[$type_label];
    }

    $all_config = $this->entityQuery->get('evergreen_config')->execute();
    $all_config = EvergreenConfig::loadMultiple($all_config);

    $configured_entities = [];

    foreach ($all_config as $config) {
      if ($type && $config->getEvergreenEntityType() != $type) {
        continue;
      }
      $configured_entities[$config->getEvergreenEntityType() . '.' . $config->getEvergreenBundle()] = [
        'entity_type' => $config->getEvergreenEntityType(),
        'bundle' => $config->getEvergreenBundle(),
        'default_status' => $config->getEvergreenStatus(),
        'default_expiry' => $config->getEvergreenExpiry(),
      ];
    }

    $this->configuredEntities[$type_label] = $configured_entities;
    return $configured_entities;
  }

  /**
   * Get the IDs of content that can expire
   */
  public function getExpirableContent(array $options = []) {
    $configured_entities = $this->getConfiguredEntityTypes();
    $expirable_content = [];

    $entity_type_bundle_info = [];

    foreach (array_values($configured_entities) as $entity) {

      // no matter the default setting, we want to find content for this entity
      // type that can expire...
      $query = $this->entityQuery->get('evergreen_content')
        ->condition('evergreen_entity_type', $entity['entity_type'])
        ->condition('evergreen_bundle', $entity['bundle'])
        ->condition('evergreen_status', 0);
      $results = $query->execute();
      $expirable_content += array_map(function ($result) use ($entity) {
        return ['id' => $result, 'entity_type' => $entity['default_status']];
      }, array_keys($results));

      // for content that defaults to expires, we need to find content with
      // an evergreen_content entity that is also set to expires AND content
      // that does not have an evergreen_content entity. This will help capture
      // content that was created before evergreen was configured...
      if ($entity['default_status'] != EVERGREEN_STATUS_EVERGREEN) {
        $query = $this->entityQuery->get($entity['entity_type']);

        // limit to bundle if we can...
        if (!isset($entity_type_bundle_info[$entity['entity_type']])) {
          $info = $this->entityTypeManager->getDefinition($info);
          $entity_type_bundle_info[$entity['entity_type']] = $info->getKey('bundle');
        }
        if ($entity_type_bundle_info[$entity['entity_type']]) {
          $query->condition($entity_type_bundle_info[$entity['entity_type']], $entity['bundle']);
        }


      }
    }

    return $expirable_content;
  }

  /**
   * Get the configuration.
   */
  public function getConfiguration(ContentEntityInterface $entity) {
    $query = $this->entityQuery->get('evergreen_config')
      ->condition('evergreen_entity_type', $entity->getEntityTypeId());
    if (method_exists($entity, 'getType')) {
      $query->condition('evergreen_bundle', $entity->getType());
    }

    $result = $query->execute();
    if (!$result) {
      return FALSE;
    }

    $config = array_keys($result);
    return EvergreenConfig::load(array_shift($config));
  }

  /**
   * Get the evergreen_content entity for this content.
   *
   * @SuppressWarnings(StaticAccess)
   */
  public function getContent(ContentEntityInterface $entity, EvergreenConfigInterface $config) {
    $query = $this->entityQuery->get('evergreen_content')
      ->condition('entity', $entity->id());
    $result = $query->execute();

    if ($result) {
      $config = array_keys($result);
      return EvergreenContent::load(array_shift($config));
    }

    $plugin = $this->getEvergreenProviderPluginForEntity($entity);
    $default_expiration = NULL;
    if ($plugin) {
      $default_expiration = $plugin->getDefaultExpirationDateForEntity($entity, $config);
    }

    $content = EvergreenContent::create([
      'entity' => $entity,
      'evergreen_entity_type' => $config->getEvergreenEntityType(),
      'evergreen_bundle' => $config->getEvergreenBundle(),
      'evergreen_status' => $config->get(EvergreenConfig::STATUS),
      'evergreen_expiry' => $config->get(EvergreenConfig::EXPIRY),
      'evergreen_expires' => $config->get(EvergreenConfig::STATUS) === EVERGREEN_STATUS_EVERGREEN ? NULL : $default_expiration,
    ]);

    return $content;
  }

  /**
   * Check if we have a configuration for this entity.
   */
  public function isEnabled(ContentEntityInterface $entity) {
    $query = $this->entityQuery->get('evergreen_config')
      ->condition('evergreen_entity_type', $entity->getEntityTypeId());
    if (method_exists($entity, 'getType')) {
      $query->condition('evergreen_bundle', $entity->getType());
    }

    $result = $query->execute();
    if ($result) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the expiration date for this content (if applicable).
   */
  public function entityExpirationDate(ContentEntityInterface $entity) {
    if (!$this->isEnabled($entity)) {
      return FALSE;
    }

    if ($this->entityIsEvergreen($entity)) {
      return FALSE;
    }

    $configuration = $this->getConfiguration($entity);
    $content = $this->getContent($entity, $configuration);

    // this entity has a content entity
    if ($content) {
      return $content->get('evergreen_expires')->value;
    }

    // get default expiration time from the evergreen provider...
    $plugin = $this->getEvergreenProviderPluginForEntity($entity);
    if ($plugin) {
      return $plugin->getDefaultExpirationDateForEntity($entity, $configuration);
    }
    return NULL;
  }

  /**
   * Get the EvergreenProvider plugin for this entity.
   */
  public function getEvergreenProviderPluginForEntity(ContentEntityInterface $entity) {
    $instance = $this->evergreen->createInstance($entity->getEntityTypeId());
    if ($instance) {
      return $instance;
    }
    return FALSE;
  }

  /**
   * Check if the entity has expired.
   *
   * @return bool
   *   Returns TRUE if the entity has expired.
   */
  public function entityHasExpired(ContentEntityInterface $entity) {
    if ($this->isEnabled($entity)) {
      $configuration = $this->getConfiguration($entity);
      $content = $this->getContent($entity, $configuration);

      if ($content && !$content->isEvergreen()) {
        return $content->isExpired();
      }
    }
    return FALSE;
  }

  /**
   * Return if an entity has an evergreen content entity associated with it.
   *
   * @return bool
   *   Returns TRUE if it has an evergreen content entity.
   */
  public function entityHasEvergreenContentEntity(ContentEntityInterface $entity) {
    if ($this->isEnabled($entity)) {
      $query = $this->entityQuery->get('evergreen_content')
        ->condition('entity', $entity->id());
      $result = $query->execute();
      return !empty($result);
    }
    return FALSE;
  }

  /**
   * Check if the entity is evergreen.
   */
  public function entityIsEvergreen(ContentEntityInterface $entity) {
    if ($this->isEnabled($entity)) {
      $configuration = $this->getConfiguration($entity);
      $content = $this->getContent($entity, $configuration);

      // if there is a content entity, we should use that to determine if this
      // is evergreen
      if ($content) {
        return $content->isEvergreen();
      }

      // if there is no content entity, we base it off the default configuration
      if ($configuration->get(EvergreenConfig::STATUS) === EVERGREEN_STATUS_EVERGREEN) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Parse the expiry string with ExpiryParser
   */
  public function parseExpiry($expiry) {
    $parser = new ExpiryParser();
    return $parser->parse($expiry);
  }

}
