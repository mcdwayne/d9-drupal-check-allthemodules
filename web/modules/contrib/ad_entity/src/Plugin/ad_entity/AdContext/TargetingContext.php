<?php

namespace Drupal\ad_entity\Plugin\ad_entity\AdContext;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ad_entity\Plugin\AdContextBase;
use Drupal\ad_entity\TargetingCollection;

/**
 * Targeting context plugin.
 *
 * @AdContext(
 *   id = "targeting",
 *   label = "Targeting",
 *   library = "ad_entity/targeting_context"
 * )
 */
class TargetingContext extends AdContextBase {

  /**
   * {@inheritdoc}
   */
  public static function getJsonEncode(array $context_data) {
    if (isset($context_data['settings']['targeting'])) {
      // Encoding via the plugin method usually means that the context
      // data will be displayed at the frontend. Thus, filter the
      // targeting information before it's being displayed.
      $collection = new TargetingCollection($context_data['settings']['targeting']);
      $collection->filter();
      $context_data['settings']['targeting'] = $collection->toArray();
    }
    return parent::getJsonEncode($context_data);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $settings, array $form, FormStateInterface $form_state) {
    $element = [];

    $targeting = !empty($settings['targeting']) ?
      new TargetingCollection($settings['targeting']) : NULL;
    $element['targeting'] = [
      '#type' => 'textfield',
      '#maxlength' => 2048,
      '#title' => $this->stringTranslation->translate("Targeting"),
      '#description' => $this->stringTranslation->translate("Pairs of key-values. Example: <strong>pos: top, category: value1, category: value2, ...</strong>"),
      '#default_value' => !empty($targeting) ? $targeting->toUserOutput() : '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageSettings(array $settings) {
    if (!empty($settings['targeting'])) {
      $targeting = new TargetingCollection();
      $targeting->collectFromUserInput($settings['targeting']);
      $settings['targeting'] = $targeting->toArray();
    }
    return parent::massageSettings($settings);
  }

}
