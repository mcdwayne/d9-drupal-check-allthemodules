<?php

// @todo: rename to VisualNSubdrawer and move to the visualn_subdewers module

namespace Drupal\visualn\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\visualn\DrawerModifierPluginCollection;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\visualn\Plugin\VisualNDrawerModifierInterface;

/**
 * Defines the VisualN Drawer entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_drawer",
 *   label = @Translation("VisualN Drawer"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn\VisualNDrawerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn\Form\VisualNDrawerAddForm",
 *       "default" = "Drupal\visualn\Form\VisualNDrawerAddForm",
 *       "edit" = "Drupal\visualn\Form\VisualNDrawerEditForm",
 *       "delete" = "Drupal\visualn\Form\VisualNDrawerDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn\VisualNDrawerHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_drawer",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "base_drawer_id" = "base_drawer_id",
 *     "drawer_config" = "drawer_config",
 *     "modifiers" = "modifiers"
 *   },
 *   links = {
 *     "canonical" = "/admin/visualn/config/subdrawers/manage/{visualn_drawer}",
 *     "add-form" = "/admin/visualn/config/subdrawers/add",
 *     "edit-form" = "/admin/visualn/config/subdrawers/manage/{visualn_drawer}/edit",
 *     "delete-form" = "/admin/visualn/config/subdrawers/manage/{visualn_drawer}/delete",
 *     "collection" = "/admin/visualn/config/subdrawers"
 *   }
 * )
 */
// @todo: add config_export to the annotation? (see ImageStyle.php)
//    and check the correctness of entity_keys and schema.yml
// @todo: review based on ImageStyle, since it has much in common
class VisualNDrawer extends ConfigEntityBase implements VisualNDrawerInterface, EntityWithPluginCollectionInterface {

  /**
   * The VisualN Drawer ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN Drawer label.
   *
   * @var string
   */
  protected $label;

  /**
   * The VisualN Base Drawer plugin ID.
   *
   * @var string
   */
  protected $base_drawer_id;

  /**
   * The VisualN User Drawer Base Drawer config.
   *
   * @var array
   */
  protected $drawer_config = [];


  /**
   * The array of drawer modifiers for this subdrawer.
   *
   * @var array
   */
  protected $modifiers = [];


  /**
   * Holds the collection of drawer modifiers that are used by this subdrawer.
   *
   * @var \Drupal\visualn\DrawerModifierPluginCollection
   */
  protected $modifiersCollection;

  // @todo: add setDrawerId() method if needed

  /**
   * {@inheritdoc}
   */
  public function getBaseDrawerId() {
    return $this->base_drawer_id ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDrawerConfig() {
    return $this->drawer_config;
  }

  /**
   * Returns the drawer modifier plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The drawer modifier plugin manager.
   */
  protected function getDrawerModifierPluginManager() {
    return \Drupal::service('plugin.manager.visualn.drawer_modifier');
  }

  /**
   * {@inheritdoc}
   */
  public function setDrawerConfig($drawer_config) {
    $this->drawer_config = $drawer_config;
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function deleteDrawerModifier(VisualNDrawerModifierInterface $modifier) {
    $this->getModifiers()->removeInstanceId($modifier->getUuid());
    $this->save();
    return $this;
  }

  /**
   * see ImageEffect::getEffect()
   */
  public function getModifier($modifier) {
    return $this->getModifiers()->get($modifier);
  }

  /**
   * see ImageEffect::getEffects()
   */
  public function getModifiers() {
    if (!$this->modifiersCollection) {
      $this->modifiersCollection = new DrawerModifierPluginCollection($this->getDrawerModifierPluginManager(), $this->modifiers);
      $this->modifiersCollection->sort();
    }
    return $this->modifiersCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['modifiers' => $this->getModifiers()];
  }

  public function addDrawerModifier(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getModifiers()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

}
