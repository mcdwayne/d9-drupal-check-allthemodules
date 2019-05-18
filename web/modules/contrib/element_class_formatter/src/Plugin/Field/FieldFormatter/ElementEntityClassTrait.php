<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Trait ElementEntityClassTrait.
 *
 * @package Drupal\element_class_formatter\Plugin\Field\FieldFormatter
 */
trait ElementEntityClassTrait {

  use ElementClassTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ElementClassTrait::elementClassDefaultSettings(parent::defaultSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $class = $this->getSetting('class');

    return $this->elementClassSettingsForm($elements, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $class = $this->getSetting('class');

    return $this->elementClassSettingsSummary($summary, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $entities = $this->getEntitiesToView($items, $langcode);
    $class = $this->getSetting('class');

    return $this->setEntityClass($elements, $class, $entities);
  }

}
