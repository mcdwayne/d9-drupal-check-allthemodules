<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Trait ElementListClassTrait.
 *
 * @package Drupal\element_class_formatter\Plugin\Field\FieldFormatter
 */
trait ElementListClassTrait {

  use ElementClassTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = parent::defaultSettings() + [
      'list_type' => 'ul',
    ];

    return ElementClassTrait::elementClassDefaultSettings($default_settings);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $class = $this->getSetting('class');

    $elements['list_type'] = [
      '#title' => $this->t('List type'),
      '#type' => 'select',
      '#options' => [
        'ul' => 'Un-ordered list',
        'ol' => 'Ordered list',
      ],
      '#default_value' => $this->getSetting('list_type'),
    ];

    return $this->elementClassSettingsForm($elements, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $class = $this->getSetting('class');
    if ($tag = $this->getSetting('list_type')) {
      $summary[] = $this->t('List type: @tag', ['@tag' => $tag]);
    }

    return $this->elementClassSettingsSummary($summary, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $class = $this->getSetting('class');

    $attributes = new Attribute();
    if (!empty($class)) {
      $attributes->addClass($class);
    }

    return [
      [
        '#theme' => 'item_list',
        '#items' => $elements,
        '#list_type' => $this->getSetting('list_type'),
        '#attributes' => $attributes->toArray(),
      ],
    ];
  }

}
