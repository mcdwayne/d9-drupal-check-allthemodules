<?php

namespace Drupal\rocketship_core\Plugin\DsField;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * DsField.
 *
 * Outputs the time an entity was created as "X minutes/hours/etc ago"
 * Updates with AJAX, has fallback normal date format.
 *
 * @DsField(
 *   id = "ds_time_ago",
 *   title = @Translation("Created: time ago"),
 *   entity_type = {
 *    "node",
 *    "paragraph"
 *   },
 *   provider = "rocketship_core"
 * )
 */
class DsTimeAgoField extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fallback_format' => 'd/m/Y',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $config = $this->getConfiguration();

    $summary = [];
    $summary[] = 'fallback format: ' . $config['fallback_format'];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['fallback_format'] = [
      '#title' => t('Fallback format'),
      '#description' => t('Enter a PHP date format to use as the fallback for when javascript is not available'),
      '#type' => 'textfield',
      '#default_value' => $config['fallback_format'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $fallback = $config['fallback_format'];
    $iso = format_date($this->entity()->getCreatedTime(), 'custom', 'c');
    $human_readable = format_date($this->entity()->getCreatedTime(), 'custom', $fallback);
    $build['job_created_time_ago'] = [
      '#markup' => '<div class="created-time-ago" data-datetime="' . $iso . '">' . $human_readable . '</div>',
      '#attached' => [
        'library' => [
          'rocketship_core/posted_days_ago',
        ],
      ],
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];

    return $build;
  }

}
