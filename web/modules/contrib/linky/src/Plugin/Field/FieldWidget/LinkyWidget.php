<?php

namespace Drupal\linky\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldWidget\DynamicEntityReferenceWidget;
use Drupal\linky\Element\LinkyEntityAutocomplete;

/**
 * Plugin implementation of the linky widget.
 *
 * @FieldWidget(
 *   id = "linky",
 *   label = @Translation("Managed link autocomplete"),
 *   description = @Translation("The managed link widget."),
 *   field_types = {
 *     "dynamic_entity_reference"
 *   }
 * )
 */
class LinkyWidget extends DynamicEntityReferenceWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'allow_duplicate_urls' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['allow_duplicate_urls'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow duplicate urls'),
      '#default_value' => $this->getSetting('allow_duplicate_urls'),
      '#description' => t('Uncheck this if you do not want users to be able to create Managed link entities with the same url when using the "Create referenced entities if they don\'t already exist" field setting.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('allow_duplicate_urls')) {
      $summary[] = t('Duplicate urls allowed');
    }
    else {
      $summary[] = t('Duplicate urls not allowed');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $build = parent::formElement($items, $delta, $element, $form, $form_state);
    $settings = $this->getFieldSettings();
    $available = DynamicEntityReferenceItem::getTargetTypes($settings);
    $target_type = $items->get($delta)->target_type ?: reset($available);
    $entity = $items->get($delta)->entity;
    if ($default = $form_state->getValue([$items->getName(), $delta])) {
      if (isset($default['target_type'])) {
        $target_type = $default['target_type'];
      }
      if (isset($default['target_id']['entity'])) {
        $entity = $default['target_id']['entity'];
      }
    }
    $js_class = Html::cleanCssIdentifier("dynamic-entity-reference-{$items->getName()}[$delta][linky_title]");
    $classes = ['linky__title', 'container-inline', $js_class];
    if ($target_type !== 'linky' || ($items->get($delta)->target_type && $entity && !$entity->isNew())) {
      // We need to distinguish between a default value and a default target
      // type.
      $classes[] = 'invisible';
    }
    $build['target_id']['#type'] = 'linky_entity_autocomplete';
    $build['target_id']['#element_validate'][1][0] = LinkyEntityAutocomplete::class;
    if ($target_type === 'linky') {
      $build['target_id']['#attributes']['placeholder'] = $this->t('Link URL');
    }
    $build['target_id']['#allow_duplicate_urls'] = $this->getSetting('allow_duplicate_urls');

    $build['linky'] = [
      '#type' => 'container',
      '#attributes' => ['class' => $classes],
      '#weight' => $delta + 1,
    ];
    $build['linky']['linky_title'] = [
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => '',
      '#title' => $this->t('Link title'),
    ];
    $build['#attached']['library'][] = 'linky/linky_widget';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAutocreateBundle($target_type = NULL) {
    if ($target_type === 'linky') {
      return 'linky';
    }
    return parent::getAutocreateBundle($target_type);
  }

}
