<?php

namespace Drupal\ad_entity\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\ad_entity\TargetingCollection;

/**
 * Defines the Advertising entity.
 *
 * @ConfigEntityType(
 *   id = "ad_entity",
 *   label = @Translation("Advertising entity"),
 *   label_collection = @Translation("Advertising entities"),
 *   label_singular = @Translation("Advertising entity"),
 *   label_plural = @Translation("Advertising entities"),
 *   handlers = {
 *     "list_builder" = "Drupal\ad_entity\AdEntityListBuilder",
 *     "view_builder" = "Drupal\ad_entity\AdEntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\ad_entity\Form\AdEntityForm",
 *       "edit" = "Drupal\ad_entity\Form\AdEntityForm",
 *       "delete" = "Drupal\ad_entity\Form\AdEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ad_entity\AdEntityHtmlRouteProvider",
 *     },
 *    "access" = "Drupal\entity\EntityAccessControlHandler",
 *    "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *    "required_services" = "Drupal\ad_entity\AdEntityServices"
 *   },
 *   config_prefix = "ad_entity",
 *   admin_permission = "administer ad_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/ad_entity/{ad_entity}",
 *     "add-form" = "/admin/structure/ad_entity/add",
 *     "edit-form" = "/admin/structure/ad_entity/{ad_entity}/edit",
 *     "delete-form" = "/admin/structure/ad_entity/{ad_entity}/delete",
 *     "collection" = "/admin/structure/ad_entity"
 *   }
 * )
 */
class AdEntity extends ConfigEntityBase implements AdEntityInterface {

  /**
   * The Advertising entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Advertising entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * An instance of the view handler plugin.
   *
   * @var \Drupal\ad_entity\Plugin\AdViewInterface
   */
  protected $viewPlugin;

  /**
   * An instance of the type plugin.
   *
   * @var \Drupal\ad_entity\Plugin\AdTypeInterface
   */
  protected $typePlugin;

  /**
   * The handler which delivers any required service.
   *
   * @var \Drupal\ad_entity\AdEntityServices
   */
  protected $services;

  /**
   * A list of third party context data.
   *
   * @var array
   */
  protected $thirdPartyContext;

  /**
   * Get the handler which delivers any required service.
   *
   * @return \Drupal\ad_entity\AdEntityServices
   *   The services handler.
   */
  protected function services() {
    if (!isset($this->services)) {
      $this->services = $this->entityTypeManager()
        ->getHandler($this->getEntityTypeId(), 'required_services');
    }
    return $this->services;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $context_manager = $this->services()->getContextManager();
    foreach ($context_manager->getInvolvedEntities() as $entities) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      foreach ($entities as $entity) {
        if ($entity !== $this) {
          $this->cacheMaxAge = Cache::mergeMaxAges($entity->getCacheMaxAge(), $this->cacheMaxAge);
        }
      }
    }

    return parent::getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $this->addCacheContexts(['url.path']);

    $context_manager = $this->services()->getContextManager();
    foreach ($context_manager->getInvolvedEntities() as $entities) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      foreach ($entities as $entity) {
        if ($entity !== $this) {
          $this->addCacheContexts($entity->getCacheContexts());
        }
      }
    }

    return parent::getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $this->addCacheTags(['config:ad_entity.settings']);

    $context_manager = $this->services()->getContextManager();
    foreach ($context_manager->getInvolvedEntities() as $entities) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      foreach ($entities as $entity) {
        if ($entity !== $this) {
          $this->addCacheTags($entity->getCacheTags());
        }
      }
    }

    return parent::getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getViewPlugin() {
    if (!isset($this->viewPlugin)) {
      $id = $this->get('view_plugin_id');
      $view_manager = $this->services()->getViewManager();
      $this->viewPlugin = ($id && $view_manager->hasDefinition($id)) ?
        $view_manager->createInstance($id) : NULL;
    }
    return $this->viewPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypePlugin() {
    if (!isset($this->typePlugin)) {
      $id = $this->get('type_plugin_id');
      $type_manager = $this->services()->getTypeManager();
      $this->typePlugin = ($id && $type_manager->hasDefinition($id)) ?
        $type_manager->createInstance($id) : NULL;
    }
    return $this->typePlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // Make sure the provider of the view plugin is given as a dependency.
    // The type plugin however usually provides third party settings,
    // which implies that its provider is already added as dependency.
    $view_id = $this->get('view_plugin_id');
    $view_manager = $this->services()->getViewManager();
    if ($view_id && $view_manager->hasDefinition($view_id)) {
      $definition = $view_manager->getDefinition($view_id);
      if (!empty($definition['provider'])) {
        $this->addDependency('module', $definition['provider']);
      }
    }
    return parent::calculateDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function getContextData() {
    $context_data = $this->services()->getContextManager()
      ->getContextDataForEntity($this->id());

    // Also include context data by third party providers.
    foreach ($this->getThirdPartyContextData() as $third_party_data) {
      foreach ($third_party_data as $plugin_id => $settings) {
        if (isset($context_data[$plugin_id])) {
          $context_data[$plugin_id] = array_merge($context_data[$plugin_id], $settings);
        }
        else {
          $context_data[$plugin_id] = $settings;
        }
      }
    }

    return $context_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDataForPlugin($plugin_id) {
    $context_data = $this->services()->getContextManager()
      ->getContextDataForPluginAndEntity($plugin_id, $this->id());

    // Also include plugin data by third party providers.
    foreach ($this->getThirdPartyContextData() as $third_party_data) {
      if (isset($third_party_data[$plugin_id])) {
        $context_data = array_merge($context_data, $third_party_data[$plugin_id]);
      }
    }

    return $context_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetingFromContextData() {
    $collection = new TargetingCollection();
    $data = $this->getContextDataForPlugin('targeting');
    foreach ($data as $settings) {
      if (isset($settings['targeting'])) {
        $collection->collectFromCollection(new TargetingCollection($settings['targeting']));
      }
    }
    return $collection;
  }

  /**
   * Get context data by third party providers.
   *
   * @return array
   *   The backend context data, grouped by module provider.
   */
  public function getThirdPartyContextData() {
    if (!isset($this->thirdPartyContext)) {
      $context_data = [];
      $context_manager = $this->services()->getContextManager();
      $context_plugins = $context_manager->getDefinitions();
      if (!empty($context_plugins)) {
        foreach ($this->getThirdPartyProviders() as $provider) {
          $settings = $this->getThirdPartySettings($provider);
          if (empty($settings) || !is_array($settings)) {
            continue;
          }
          foreach ($settings as $key => $value) {
            if (!isset($context_plugins[$key]) || empty($value)) {
              continue;
            }
            if (!is_array($value)) {
              $decoder = $context_plugins[$key]['class'] . '::getJsonDecode';
              $value = call_user_func($decoder, $value);
            }
            if (empty($value)) {
              $value = [];
            }
            $context_data[$provider][$key][] = $value;
          }
        }
      }
      $this->thirdPartyContext = $context_data;
    }

    return $this->thirdPartyContext;
  }

  /**
   * Set third party context data.
   *
   * @param array $context_data
   *   An array to be set as third party context data.
   */
  public function setThirdPartyContextData(array $context_data) {
    $this->thirdPartyContext = $context_data;
  }

}
