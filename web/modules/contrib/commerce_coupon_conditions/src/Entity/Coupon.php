<?php

namespace Drupal\commerce_coupon_conditions\Entity;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_promotion\Entity\Coupon as CommerceCoupon;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface;
use Drupal\commerce\Plugin\Commerce\Condition\ParentEntityAwareInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\commerce\ConditionGroup;

/**
 * Class Coupon.
 *
 * @package Drupal\commerce_coupon_conditions\Entity
 */
class Coupon extends CommerceCoupon implements CouponInterface {

  /**
   * {@inheritdoc}
   */
  public function setConditions(array $conditions) {
    $this->set('conditions', []);
    foreach ($conditions as $condition) {
      if ($condition instanceof ConditionInterface) {
        $this->get('conditions')->appendItem([
          'target_plugin_id' => $condition->getPluginId(),
          'target_plugin_configuration' => $condition->getConfiguration(),
        ]);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    $conditions = [];
    foreach ($this->get('conditions') as $field_item) {
      /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItemInterface $field_item */
      $condition = $field_item->getTargetInstance();
      if ($condition instanceof ParentEntityAwareInterface) {
        $condition->setParentEntity($this);
      }
      $conditions[] = $condition;
    }
    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionOperator() {
    return $this->get('condition_operator')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setConditionOperator($condition_operator) {
    $this->set('condition_operator', $condition_operator);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    if (!$this->get('start_date')->isEmpty()) {
      return new DrupalDateTime($this->get('start_date')->value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setStartDate(DrupalDateTime $start_date) {
    $this->get('start_date')->value = $start_date->format('Y-m-d');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    if (!$this->get('end_date')->isEmpty()) {
      return new DrupalDateTime($this->get('end_date')->value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setEndDate(DrupalDateTime $end_date = NULL) {
    $this->get('end_date')->value = $end_date ? $end_date->format('Y-m-d') : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function available(OrderInterface $order) {
    // Some defaults from entity.
    // @see Drupal\commerce_coupon\Entity\Coupon.
    if (!$this->isEnabled()) {
      return FALSE;
    }
    if (!$this->getPromotion()->available($order)) {
      return FALSE;
    }

    // Check coupon dates.
    $time = \Drupal::time()->getRequestTime();
    $start_date = $this->getStartDate();
    if ($start_date && $start_date->format('U') > $time) {
      return FALSE;
    }
    $end_date = $this->getEndDate();
    if ($end_date && $end_date->format('U') <= $time) {
      return FALSE;
    }

    // Run conditions check here.
    // It's used on applying coupons to check if they are valid,
    // but also cover existing coupons which are already assigned on the order
    // \Drupal\commerce_promotion\PromotionOrderProcessor::process is running
    // this check.
    if (!$this->applies($order)) {
      return FALSE;
    }

    // Check usage limits and the end.
    // We load coupon only if all conditions before had been passed.
    if ($usage_limit = $this->getUsageLimit()) {
      /** @var \Drupal\commerce_promotion\PromotionUsageInterface $usage */
      $usage = \Drupal::service('commerce_promotion.usage');
      if ($usage_limit <= $usage->loadByCoupon($this)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderInterface $order) {

    $conditions = $this->getConditions();
    if (!$conditions) {
      // Promotions without conditions always apply.
      return TRUE;
    }
    // Filter the conditions just in case there are leftover order item
    // conditions (which have been moved to offer conditions).
    $conditions = array_filter($conditions, function ($condition) {
      /** @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface $condition */
      return $condition->getEntityTypeId() == 'commerce_order';
    });
    $condition_group = new ConditionGroup($conditions, $this->getConditionOperator());

    return $condition_group->evaluate($order);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['conditions'] = BaseFieldDefinition::create('commerce_plugin_item:commerce_condition')
      ->setLabel(t('Conditions'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_conditions',
        'weight' => 6,
        'settings' => [
          'entity_types' => ['commerce_order'],
        ],
      ]);

    $fields['condition_operator'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Condition operator'))
      ->setDescription(t('The condition operator.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'AND' => t('All conditions must pass'),
        'OR' => t('Only one condition must pass'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDefaultValue('AND');

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start date'))
      ->setDescription(t('The date the promotion becomes valid.'))
      ->setRequired(FALSE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 8,
      ]);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date'))
      ->setDescription(t('The date after which the promotion is invalid.'))
      ->setRequired(FALSE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'commerce_end_date',
        'weight' => 9,
      ]);

    return $fields;
  }

}
