<?php

namespace Drupal\content_locker\Plugin\field_group\FieldGroupFormatter;

use Drupal\field_group\FieldGroupFormatterBase;
use Drupal\Core\Render\Element;

/**
 * Details element.
 *
 * @FieldGroupFormatter(
 *   id = "content_locker",
 *   label = @Translation("Content locker"),
 *   description = @Translation("Hide content inside group"),
 *   supported_contexts = {
 *     "view"
 *   }
 * )
 */
class ContentLockerGroup extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = [];

    $plugin_types = [];
    $plugin_service = \Drupal::service('plugin.manager.content_locker');
    foreach ($plugin_service->getDefinitions() as $plugin) {
      $plugin_types[$plugin['id']] = $plugin['label'];
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Field group label'),
      '#default_value' => $this->label,
      '#weight' => -5,
    ];

    if (!empty($plugin_types)) {
      $form['plugin_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Plugin type'),
        '#default_value' => $this->getSetting('plugin_type'),
        '#options' => $plugin_types,
        '#empty_value' => 0,
        '#description' => $this->t('Select the skin style.'),
        '#required' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($this->getSetting('plugin_type')) {
      $summary[] = $this->t('Plugin type: @plugin_type', ['@plugin_type' => $this->getSetting('plugin_type')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);

    $locker = \Drupal::service('content_locker');

    // Print locked content if type is empty or request is ajax.
    if (!$locker->isVisibleContent($this->getSetting('plugin_type'))) {
      $element += [
        '#theme_wrappers' => ['content_locker'],
        '#plugin_type' => $this->getSetting('plugin_type'),
        '#entity' => $rendering_object['#' . $rendering_object['#entity_type']],
      ];

      if ($locker->isDelayContent()) {
        foreach (Element::children($element) as $key) {
          $element[$key]['#printed'] = TRUE;
        }
      }
    }
  }

}
