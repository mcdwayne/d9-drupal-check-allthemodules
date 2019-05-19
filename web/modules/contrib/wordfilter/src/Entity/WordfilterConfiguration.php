<?php

namespace Drupal\wordfilter\Entity;

use \Drupal\Core\Config\Entity\ConfigEntityBase;
use \Drupal\wordfilter\WordfilterItem;
use \Drupal\wordfilter\Plugin\WordfilterProcessInterface;

/**
 * Defines the Wordfilter configuration entity.
 *
 * @ConfigEntityType(
 *   id = "wordfilter_configuration",
 *   label = @Translation("Wordfilter configuration"),
 *   admin_permission = "administer wordfilter configurations",
 *   handlers = {
 *     "list_builder" = "Drupal\wordfilter\WordfilterConfigurationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\wordfilter\Form\WordfilterConfigurationForm",
 *       "edit" = "Drupal\wordfilter\Form\WordfilterConfigurationForm",
 *       "delete" = "Drupal\wordfilter\Form\WordfilterConfigurationDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\wordfilter\WordfilterConfigurationHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\wordfilter\WordfilterConfigurationAccessControlHandler",
 *   },
 *   config_prefix = "wordfilter_configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "process_id",
 *     "items",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/wordfilter_configuration/{wordfilter_configuration}",
 *     "add-form" = "/admin/config/wordfilter_configuration/add",
 *     "edit-form" = "/admin/config/wordfilter_configuration/{wordfilter_configuration}/edit",
 *     "delete-form" = "/admin/config/wordfilter_configuration/{wordfilter_configuration}/delete",
 *     "collection" = "/admin/config/wordfilter_configuration"
 *   }
 * )
 */
class WordfilterConfiguration extends ConfigEntityBase implements WordfilterConfigurationInterface {

  /**
   * The Wordfilter configuration ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Wordfilter configuration label.
   *
   * @var string
   */
  protected $label;

  /**
   * The assigned Wordfilter process id.
   * 
   * @var string
   */
  protected $process_id = 'default';
  
  /**
   * The assigned Wordfilter process object.
   * 
   * @see ::getProcess().
   * 
   * @var \Drupal\wordfilter\Plugin\WordfilterProcessInterface 
   */
   protected $process = NULL;

  /**
   * The filtering items as arrays.
   *
   * @var array
   */
  protected $items = [['delta' => 0, 'substitute' => '', 'filter_words' => '']];

  /**
   * The filtering items as cached objects.
   *
   * @var \Drupal\wordfilter\WordfilterItem[]
   */
  protected $item_objects = NULL;

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (isset($this->process) && ($this->process->getPluginId() == $this->process_id)) {
      return $this->process;
    }

    /**
     * @var \Drupal\wordfilter\Plugin\WordfilterProcessManager
     */
    $manager = \Drupal::service('plugin.manager.wordfilter_process');
    $this->setProcess($manager->createInstance($this->process_id));
    return $this->getProcess();
  }

  /**
   * {@inheritdoc} 
   */
  public function setProcess(WordfilterProcessInterface $process) {
    $this->set('process_id', $process->getPluginId());
    $this->process = $process;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    if (isset($this->item_objects)) {
      return $this->item_objects;
    }
    $item_objects = [];
    foreach ($this->items as &$item) {
      $item_objects[$item['delta']] = new WordfilterItem($this, $item);
    }
    ksort($item_objects);
    $this->item_objects = $item_objects;
    return $this->getItems();
  }

  /**
   * {@inheritdoc}
   */
  public function newItem($delta = NULL) {
    if (!isset($delta)) {
      $delta = 0;
      foreach ($this->items as $item) {
        if ($delta <= $item['delta']) {
          $delta = $item['delta'] + 1;
        }
      }
    }
    $this->items[$delta] = ['delta' => $delta, 'substitute' => '', 'filter_words' => ''];
    $this->item_objects[$delta] = new WordfilterItem($this, $this->items[$delta]);
    return $this->item_objects[$delta];
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem(WordfilterItem $item_object) {
    if ($item_object->getParent()->id() !== $this->id()) {
      return FALSE;
    }
    $delta = $item_object->getDelta();
    foreach ($this->items as $key => $item) {
      if ($item['delta'] === $delta) {
        unset($this->items[$key]);
        unset($this->item_objects[$delta]);
        if (!count($this->items)) {
          // Fallback: At least one empty item must be present.
          $this->newItem(0);
        }
        return TRUE;
      }
    }
    return FALSE;
  }
}
