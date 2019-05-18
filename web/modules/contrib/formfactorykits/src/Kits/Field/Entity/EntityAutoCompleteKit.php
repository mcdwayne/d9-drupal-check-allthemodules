<?php

namespace Drupal\formfactorykits\Kits\Field\Entity;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\QueryConditionTrait;
use Drupal\kits\Services\KitsInterface;
use Drupal\query\Common\Condition;
use Drupal\query\Common\Operator;

/**
 * Class EntityAutoCompleteKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Entity
 */
abstract class EntityAutoCompleteKit extends FormFactoryKit {
  const TYPE = 'entity_autocomplete';
  const TARGET_TYPE_KEY = 'target_type';
  const TARGET_TYPE = NULL;
  const TAGS_KEY = 'tags';
  const SELECTION_HANDLER_KEY = 'selection_handler';
  const SELECTION_HANDLER = 'default:entity_by_field';
  const SELECTION_SETTINGS_KEY = 'selection_settings';

  /**
   * @inheritdoc
   */
  public function __construct(KitsInterface $kitsService,
                              $id = NULL,
                              array $parameters = [],
                              array $context = []) {
    if (!array_key_exists(self::TARGET_TYPE_KEY, $parameters)) {
      $parameters[self::TARGET_TYPE_KEY] = static::TARGET_TYPE;
    }
    parent::__construct($kitsService, $id, $parameters, $context);
  }

  /**
   * @param string $type
   *
   * @return static
   */
  public function setTargetType($type) {
    return $this->set(static::TARGET_TYPE_KEY, $type);
  }

  /**
   * @return string|null
   */
  public function getSelectionHandler() {
    return $this->get(static::SELECTION_HANDLER_KEY);
  }

  /**
   * @param string $pluginID
   *
   * @return static
   */
  public function setSelectionHandler($pluginID) {
    return $this->set(static::SELECTION_HANDLER_KEY, $pluginID);
  }

  /**
   * @param string $key
   * @param mixed $default
   *
   * @return mixed
   */
  public function getFilter($key, $default = NULL) {
    $filters = $this->getSelectionSetting('filter', []);
    return isset($filters[$key]) ? $filters[$key] : $default;
  }

  /**
   * @param string $key
   * @param mixed $value
   *
   * @return static
   */
  public function setFilter($key, $value) {
    if (NULL !== static::SELECTION_HANDLER && !$this->getSelectionHandler()) {
      $this->setSelectionHandler(static::SELECTION_HANDLER);
    }
    $filters = $this->getSelectionSetting('filter', []);
    $filters[$key] = $value;
    return $this->setSelectionSetting('filter', $filters);
  }

  /**
   * @param \Drupal\query\Common\Condition $condition
   *
   * @return static
   */
  public function setCondition(Condition $condition) {
    return $this->setFilter($condition->getKey(), $this->getSelectionSettingArray($condition));
  }

  /**
   * @return array
   */
  public function getSelectionSettings() {
    return $this->get(self::SELECTION_SETTINGS_KEY, []);
  }

  /**
   * @param array $settings
   *
   * @return static
   */
  public function setSelectionSettings(array $settings) {
    return $this->set(self::SELECTION_SETTINGS_KEY, $settings);
  }

  /**
   * @param string $key
   * @param mixed $default
   *
   * @return mixed
   */
  public function getSelectionSetting($key, $default = NULL) {
    $settings = $this->getSelectionSettings();
    return isset($settings[$key]) ? $settings[$key] : $default;
  }

  /**
   * @param string $key
   * @param mixed $value
   *
   * @return static
   */
  public function setSelectionSetting($key, $value) {
    $settings = $this->getSelectionSettings();
    $settings[$key] = $value;
    return $this->setSelectionSettings($settings);
  }

  /**
   * Creates a selection setting array from the given Condition object.
   *
   * @param Condition $condition
   *
   * @return array
   */
  public function getSelectionSettingArray(Condition $condition) {
    foreach ($condition->getRequirementGroups() as $group) {
      foreach ($group->getRequirements() as $expression) {
        $operator = $expression->getOperator();
        switch ($operator) {
          case Operator::TYPE_EQUIVALENT:
          case Operator::TYPE_EQUALS:
            return $expression->getValue();

          case Operator::TYPE_NOT_EQUIVALENT:
          case Operator::TYPE_NOT_EQUALS:
            return [
              'operator' => '!=',
              'value' => $expression->getValue(),
            ];

          case Operator::TYPE_LESS_THAN:
            return [
              'operator' => '<',
              'value' => $expression->getValue(),
            ];

          case Operator::TYPE_LESS_THAN_EQUAL_TO:
            return [
              'operator' => '<=',
              'value' => $expression->getValue(),
            ];

          case Operator::TYPE_GREATER_THAN:
            return [
              'operator' => '>',
              'value' => $expression->getValue(),
            ];

          case Operator::TYPE_GREATER_THAN_EQUAL_TO:
            return [
              'operator' => '>=',
              'value' => $expression->getValue(),
            ];

          case Operator::TYPE_IN:
            return [
              'operator' => 'IN',
              'value' => $expression->getValues(),
            ];

          case Operator::TYPE_NOT_IN:
            return [
              'operator' => 'NOT IN',
              'value' => $expression->getValues(),
            ];

          default:
            throw new \DomainException(vsprintf('Unsupported operator: %s', [
              $operator,
            ]));
        }
      }
    }
    return [];
  }
}
