<?php

namespace Drupal\blocktabs\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\blocktabs\BlocktabsInterface;
use Drupal\blocktabs\TabInterface;
use Drupal\blocktabs\TabPluginCollection;

/**
 * Defines a blocktabs configuration entity.
 *
 * @ConfigEntityType(
 *   id = "blocktabs",
 *   label = @Translation("Blocktabs"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\blocktabs\Form\BlocktabsAddForm",
 *       "edit" = "Drupal\blocktabs\Form\BlocktabsEditForm",
 *       "delete" = "Drupal\blocktabs\Form\BlocktabsDeleteForm",
 *     },
 *     "list_builder" = "Drupal\blocktabs\BlocktabsListBuilder",
 *   },
 *   admin_permission = "administer blocktabs",
 *   config_prefix = "blocktabs",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/blocktabs/manage/{blocktabs}",
 *     "delete-form" = "/admin/structure/blocktabs/manage/{blocktabs}/delete",
 *     "collection" = "/admin/structure/blocktabs",
 *   },
 *   config_export = {
 *     "name",
 *     "label",
 *     "tabs",
 *     "event",
 *     "style"
 *   }
 * )
 */
class Blocktabs extends ConfigEntityBase implements BlocktabsInterface, EntityWithPluginCollectionInterface {


  /**
   * The name of the blocktabs.
   *
   * @var string
   */
  protected $name;

  /**
   * The blocktabs label.
   *
   * @var string
   */
  protected $label;

  /**
   * Selected event Hover or Click.
   *
   * @var string
   */
  protected $event;

  /**
   * The blocktabs style, default, vertical.
   *
   * @var string
   */
  protected $style;

  /**
   * The array of tabs for this blocktabs.
   *
   * @var array
   */
  protected $tabs = [];

  /**
   * Holds the collection of tabs that are used by this blocktabs.
   *
   * @var \Drupal\blocktabs\TabPluginCollection
   */
  protected $tabsCollection;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update) {
      if (!empty($this->original) && $this->id() !== $this->original->id()) {
        // Update field settings if necessary.
        if (!$this->isSyncing()) {

        }
      }
      else {

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTab(TabInterface $tab) {
    $this->getTabs()->removeInstanceId($tab->getUuid());
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTab($tab) {
    return $this->getTabs()->get($tab);
  }

  /**
   * {@inheritdoc}
   */
  public function getTabs() {
    if (!$this->tabsCollection) {
      $this->tabsCollection = new TabPluginCollection($this->getTabPluginManager(), $this->tabs);
      $this->tabsCollection->sort();
    }
    return $this->tabsCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['tabs' => $this->getTabs()];
  }

  /**
   * {@inheritdoc}
   */
  public function addTab(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getTabs()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name');
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * Returns the tab plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The tab plugin manager.
   */
  protected function getTabPluginManager() {
    return \Drupal::service('plugin.manager.blocktabs.tab');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    foreach ($this->getTabs() as $tab) {
      $contexts = Cache::mergeContexts($tab->getCacheContexts());
    }
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    foreach ($this->getTabs() as $tab) {
      $tags = Cache::mergeTags($tab->getCacheTags());
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $max_age = parent::getCacheMaxAge();
    foreach ($this->getTabs() as $tab) {
      $max_age = Cache::mergeMaxAges($max_age, $tab->getCacheMaxAge());
    }
    return $max_age;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent() {
    return $this->get('event');
  }

  /**
   * {@inheritdoc}
   */
  public function getStyle() {
    return $this->get('style');
  }

}
