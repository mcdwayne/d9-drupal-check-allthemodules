<?php

namespace Drupal\entity_ui\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the Entity tab entity.
 *
 * @ConfigEntityType(
 *   id = "entity_tab",
 *   label = @Translation("Entity tab"),
 *   label_singular = @Translation("Entity tab"),
 *   label_plural = @Translation("Entity tabs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Entity tab",
 *     plural = "@count Entity tabs",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\entity_ui\EntityHandler\EntityTabListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_ui\Form\EntityTabForm",
 *       "edit" = "Drupal\entity_ui\Form\EntityTabForm",
 *       "delete" = "Drupal\entity_ui\Form\EntityTabDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity_ui\Routing\EntityTabAdminRouteProvider",
 *     },
 *   },
 *   config_prefix = "entity_tab",
 *   admin_permission = "administer all entity tabs",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/entity_ui/entity_tab/{entity_tab}",
 *     "add-page" = "/admin/structure/entity_ui/entity_tab/add/{target_entity_type_id}",
 *     "add-form" = "/admin/structure/entity_ui/entity_tab/add/{target_entity_type_id}/{plugin_id}",
 *     "edit-form" = "/admin/structure/entity_ui/entity_tab/{entity_tab}/edit",
 *     "delete-form" = "/admin/structure/entity_ui/entity_tab/{entity_tab}/delete",
 *   }
 * )
 */
class EntityTab extends ConfigEntityBase implements EntityTabInterface {

  /**
   * The Entity tab ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Entity tab label.
   *
   * @var string
   */
  protected $label;

  /**
   * The target entity type ID.
   *
   * @var string
   */
  protected $target_entity_type;

  /**
   * The target bundle names.
   *
   * An empty array means the tab applies to all bundles on the target entity
   * type.
   *
   * @var string[]
   */
  protected $target_bundles = [];

  /**
   * The ID of the Entity Tab Content plugin.
   *
   * Needs to be set to a default for the form to work, apparently.
   *
   * @var string
   */
  protected $content_plugin = 'entity_view';

  /**
   * The configuration for the plugin.
   */
  protected $content_config = [];

  /**
   * The weight, relative to other tabs on the same target entity type.
   *
   * This defaults to 10, as tabs defined by the target entity's providing
   * module will tend to have weights lower than this.
   *
   * @var int
   */
  protected $weight = 10;

  /**
   * A collection to store the tab content plugin.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $contentPluginCollection;

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE, EntityInterface $target_entity = NULL) {
    if ($operation == 'view') {
      if (empty($target_entity)) {
        throw new \Exception('The $target_entity parameter must be specified for the view operation.');
      }

      // Check bundle applicability, and then hand over to the plugin for the
      // 'view' operation.
      $bundle_access_result = $this->hasBundleAccess($target_entity);
      $plugin_access = $this->getContentPlugin()->access($target_entity, $account, $return_as_object);

      $result = $bundle_access_result->andIf($plugin_access);

      return $return_as_object ? $result : $result->isAllowed();
    }

    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * Checks the tab is set to apply to the target entity's bundle.
   *
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   The target entity that the entity tab is on.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function hasBundleAccess(EntityInterface $target_entity) {
    $applies_to_all_bundles = empty($this->target_bundles);
    $applies_to_current_bundle = in_array($target_entity->bundle(), $this->target_bundles);
    return AccessResult::allowedIf($applies_to_all_bundles || $applies_to_current_bundle)->addCacheableDependency($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeID() {
    return $this->target_entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    return $this->getContentPlugin()->getPermissions();
  }

  /**
   * {@inheritdoc}
   */
  public function getPathComponent() {
    return $this->get('path');
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    // Namespace the final component with our module name (this is the same
    // pattern as Devel module). This keeps all entity routes with a consistent
    // prefix.
    return "entity.{$this->target_entity_type}.entity_ui_{$this->path}";
  }

  /**
   * {@inheritdoc}
   */
  public function getPageTitle(EntityInterface $target_entity) {
    $page_title = $this->get('page_title');

    $token_service = \Drupal::token();
    return $token_service->replace($page_title, [
      'entity_ui_tab' => $this,
      'entity_ui_target_entity' => $target_entity,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTabTitle() {
    return $this->get('tab_title');
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginID() {
    return $this->content_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration() {
    return $this->content_config;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'content_config' => $this->getContentPluginCollection(),
    ];
  }

  /**
   * Encapsulates the creation of the content tab plugin collection.
   *
   * @return \Drupal\Component\Plugin\DefaultSingleLazyPluginCollection
   *   The plugin collection.
   */
  protected function getContentPluginCollection() {
    if (!$this->contentPluginCollection) {
      $this->contentPluginCollection = new \Drupal\entity_ui\Plugin\EntityTabLazyPluginCollection(
        \Drupal::service('plugin.manager.entity_ui_tab_content'),
        $this->content_plugin,
        $this->content_config,
        // The entity tab entity gets passed in to the collection, so that it
        // can be set on the plugin.
        $this
      );
    }
    return $this->contentPluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentPlugin() {
    return $this->getContentPluginCollection()->get($this->content_plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Redo the parent class's setting of the plugin's configuration into the
    // entity, as we need to zap the entity_tab that gets added in by
    // EntityTabContentManager::createInstance().
    foreach ($this->getPluginCollections() as $plugin_config_key => $plugin_collection) {
      $configuration = $plugin_collection->getConfiguration();
      unset($configuration['entity_tab']);
      $this->set($plugin_config_key, $configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Rebuild router and local tasks if necessary.
    if ($update) {
      // On an updated entity, check the original value to see if the path or
      // the tab title have been changed.
      // A change in the path component requires a route rebuild.
      if ($this->original->getPathComponent() != $this->getPathComponent()) {
        $this->rebuildRouter();
      }

      // A change in the tab title requires a local task rebuild.
      if ($this->original->getTabTitle() != $this->getTabTitle()) {
        $this->rebuildLocalTasks();
      }
    }
    else {
      // A new entity always needs things rebuilding, as we're adding a route
      // and a task.
      $this->rebuildRouter();
      $this->rebuildLocalTasks();
    }
  }

  /**
   * Rebuilds the router.
   */
  protected function rebuildRouter() {
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * Rebuilds the local tasks.
   */
  protected function rebuildLocalTasks() {
    \Drupal::service('plugin.manager.menu.local_task')->clearCachedDefinitions();
  }

}
