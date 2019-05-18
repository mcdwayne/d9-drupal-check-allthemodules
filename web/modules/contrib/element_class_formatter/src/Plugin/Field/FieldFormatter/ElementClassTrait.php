<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Trait ElementClassTrait.
 *
 * @package Drupal\element_class_formatter\Plugin\Field\FieldFormatter
 */
trait ElementClassTrait {

  use StringTranslationTrait;

  /**
   * Default class value.
   *
   * @param array $settings
   *   The original default settings array.
   *
   * @return array
   *   The new default settings array.
   */
  public static function elementClassDefaultSettings(array $settings) {
    return [
      'class' => '',
    ] + $settings;
  }

  /**
   * Setting form to collect class value.
   *
   * @param array $elements
   *   The original elements render array.
   * @param string $class
   *   The class string.
   *
   * @return array
   *   The updated elements render array.
   */
  public function elementClassSettingsForm(array $elements, $class) {
    $elements['class'] = [
      '#type' => 'textfield',
      '#default_value' => $class,
      '#title' => $this->t('Element class'),
      '#description' => 'A space separated set of classes.',
      '#maxlength' => 200,
    ];

    return $elements;
  }

  /**
   * Text for settings summary.
   *
   * @param array $summary
   *   The original summary array.
   * @param string $class
   *   The class string.
   *
   * @return array
   *   The updated summary array.
   */
  public function elementClassSettingsSummary(array $summary, $class) {
    if ($class) {
      $summary[] = $this->t('Element class: @class', ['@class' => $class]);
    }

    return $summary;
  }

  /**
   * Set the class on the element.
   *
   * @param array $elements
   *   The original elements render array.
   * @param string $class
   *   The class string.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The list of field items.
   *
   * @return array
   *   The updated elements render array.
   */
  public function setElementClass(array $elements, $class, FieldItemListInterface $items) {
    foreach ($items as $delta => $item) {
      // Add class.
      if (!empty($class)) {
        $elements[$delta]['#options']['attributes']['class'][] = $class;
      }
    }

    return $elements;
  }

  /**
   * Set the class on the entity.
   *
   * @param array $elements
   *   The original elements render array.
   * @param string $class
   *   The class string.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The referenced entities.
   *
   * @return array
   *   The updated elements render array.
   */
  public function setEntityClass(array $elements, $class, array $entities) {
    foreach ($entities as $delta => $entity) {
      // Add class.
      if (!empty($class)) {
        $elements[$delta]['#item_attributes']['class'][] = $class;
      }
    }

    return $elements;
  }

}
