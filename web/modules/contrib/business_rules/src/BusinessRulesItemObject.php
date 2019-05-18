<?php

namespace Drupal\business_rules;

use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\Condition;

/**
 * Class BusinessRulesItemObject.
 *
 * The Business Rule Item object.
 *
 * @package Drupal\business_rules
 */
class BusinessRulesItemObject {

  const CONDITION = 'condition';
  const ACTION    = 'action';

  /**
   * The item id.
   *
   * @var string
   */
  private $id;

  /**
   * The item type.
   *
   * @var string
   */
  private $type;

  /**
   * The item weight.
   *
   * @var int
   */
  private $weight;

  /**
   * BusinessRulesItemObject constructor.
   *
   * @param string $id
   *   The Business Rule Item id.
   * @param string $type
   *   The Business Rule Item type.
   * @param int $weight
   *   The Business Rule Item weight.
   */
  public function __construct($id, $type, $weight) {
    $this->setId($id);
    $this->setType($type);
    $this->setWeight($weight);
  }

  /**
   * Transform the items array in a array of BusinessRulesItemObject.
   *
   * @param array $items
   *   The items array.
   *
   * @return array
   *   Array of BusinessRulesItemObject.
   */
  public static function itemsArrayToItemsObject(array $items) {
    $obj_items = [];
    if (is_array($items)) {

      uasort($items, function ($a, $b) {
        return $a['weight'] < $b['weight'] ? -1 : 1;
      });

      foreach ($items as $item) {
        $itemObj                = new BusinessRulesItemObject($item['id'], $item['type'], $item['weight']);
        $obj_items[$item['id']] = $itemObj;
      }
    }

    return $obj_items;
  }

  /**
   * Load the Item object. Action or Condition.
   *
   * @return \Drupal\business_rules\ItemInterface|null
   *   The loaded item. Action or Condition.
   */
  public function loadEntity() {
    if ($this->getType() == self::ACTION) {
      return Action::load($this->getId());
    }
    elseif ($this->getType() == self::CONDITION) {
      return Condition::load($this->getId());
    }

    return NULL;
  }

  /**
   * Get the item type.
   *
   * @return string
   *   The item type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Set the item type.
   *
   * @param string $type
   *   The item type.
   *
   * @throws \Exception
   */
  public function setType($type) {
    if (in_array($type, [self::ACTION, self::CONDITION])) {
      $this->type = $type;
    }
    else {
      throw new \Exception("The only Business Rule item type available are 'action' and 'condition'. $type given.");
    }
  }

  /**
   * Get the item id.
   *
   * @return string
   *   The item id.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Set the item id.
   *
   * @param string $id
   *   The item id.
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * Transform the object into array.
   *
   * @return array
   *   The business rule item array representation:
   *    - id
   *    - type
   *    weight.
   */
  public function toArray() {
    return [
      $this->getId() => [
        'id'     => $this->getId(),
        'type'   => $this->getType(),
        'weight' => $this->getWeight(),
      ],
    ];
  }

  /**
   * Get the item weight.
   *
   * @return int
   *   The item weight.
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * Set the item weight.
   *
   * @param int $weight
   *   The item weight.
   *
   * @throws \Exception
   */
  public function setWeight($weight) {
    if (is_numeric($weight)) {
      $this->weight = $weight;
    }
    else {
      throw new \Exception("Only integers are permitted for weight. $weight given. ");
    }
  }

}
