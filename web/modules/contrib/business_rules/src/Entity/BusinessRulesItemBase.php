<?php

namespace Drupal\business_rules\Entity;

use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Class Item.
 *
 * @package Drupal\business_rules\Entity
 */
abstract class BusinessRulesItemBase extends ConfigEntityBase implements ItemInterface {

  /**
   * The Item description.
   *
   * @var string
   */
  protected $description;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The Item ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Item plugin manager.
   *
   * @var \Drupal\Core\Plugin\DefaultPluginManager
   */
  protected $itemManager;

  /**
   * The Item label.
   *
   * @var string
   */
  protected $label;

  /**
   * The item settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The tags to mark this entity.
   *
   * @var array
   */
  protected $tags = [];

  /**
   * The target entity bundle id which this item is applicable.
   *
   * @var string
   */
  protected $target_bundle;

  /**
   * The entity type id which this item is applicable.
   *
   * @var string
   */
  protected $target_entity_type;

  /**
   * The item type.
   *
   * @var string
   */
  protected $type;

  /**
   * The Business Rules Util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  protected $util;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->itemManager     = $this->getItemManager();
    $this->eventDispatcher = \Drupal::getContainer()->get('event_dispatcher');
    $this->util            = \Drupal::getContainer()
      ->get('business_rules.util');
  }

  /**
   * Get the plugin manager.
   *
   * @return \Drupal\Core\Plugin\DefaultPluginManager
   *   The plugin manager to be used.
   */
  abstract public function getItemManager();

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getReactOnEvents() {
    $definition = $this->itemManager->getDefinition($this->getType());
    if (array_key_exists('reactsOnIds', $definition)) {
      return $definition['reactsOnIds'];
    }
    else {
      return [];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($settingId = '') {
    if ($settingId == '') {
      return $this->settings;
    }
    elseif (empty($this->settings[$settingId])) {
      if (array_key_exists($settingId, $this->settings)) {
        if ($this->settings[$settingId] === 0 || $this->settings[$settingId] === "0") {
          $value = 0;
        }
        else {
          $value = NULL;
        }
      }
      else {
        $value = NULL;
      }
    }
    else {
      $value = $this->settings[$settingId];
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($settingId, $value) {
    if (!empty($settingId)) {
      $this->settings[$settingId] = $value;
    }
    else {
      throw new \Exception('You must enter a value to the settingId');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTags() {
    return $this->tags;
  }

  /**
   * {@inheritdoc}
   */
  public function setTags(array $tags) {
    $formatted_tags = [];
    foreach ($tags as $tag) {
      if ($tag != '') {
        $this->util->toSafeLowerString($tag);
        $formatted_tags[$tag] = $tag;
      }
    }
    ksort($formatted_tags);
    $this->tags = $formatted_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return $this->target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType() {
    return $this->target_entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    $types = $this->getTypes();

    foreach ($types as $type) {
      foreach ($type as $key => $value) {
        if ($key == $this->getType()) {
          return $value;
        }
      }
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    $types = [];
    $items = $this->itemManager->getDefinitions();

    uasort($items, function ($a, $b) {
      return ($a['label']->render() > $b['label']->render()) ? 1 : -1;
    });

    foreach ($items as $item) {
      if (isset($types[$item['group']->render()])) {
        $types[$item['group']->render()] += [$item['id'] => $item['label']];
      }
      else {
        $types[$item['group']->render()] = [$item['id'] => $item['label']];
      }
    }

    ksort($types);

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function isContextDependent() {
    $type       = $this->getType();
    $definition = $this->getItemManager()->getDefinition($type);

    return $definition['isContextDependent'];
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultipleByType($type, array $ids = NULL) {
    $items = self::loadMultiple($ids);
    $result = [];
    /** @var \Drupal\business_rules\ItemInterface $item */
    foreach ($items as $item) {
      if ($item->getType() == $type) {
        $result[] = $item;
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariables() {
    $item_type    = $this->itemManager->getDefinition($this->getType());
    $reflection   = new \ReflectionClass($item_type['class']);
    $defined_item = $reflection->newInstance($item_type, $item_type['id'], $item_type);
    $variables    = $defined_item->getVariables($this);

    return $variables;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadAllTags() {
    $business_rules = self::loadMultiple();
    $tags           = [];
    /** @var \Drupal\business_rules\Entity\BusinessRule $business_rule */
    foreach ($business_rules as $business_rule) {
      if (count($business_rule->getTags())) {
        foreach ($business_rule->getTags() as $key => $value) {
          if ($key != '' || $value != '') {
            $tags[$key] = $value;
          }
        }
      }
    }
    ksort($tags);

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();

    if (!$this->isNew()) {
      // Dispatch an event about the item deletion.
      $event = new BusinessRulesEvent($this);
      $this->eventDispatcher->dispatch('business_rules.item_pos_delete', $event);
    }
  }

}
