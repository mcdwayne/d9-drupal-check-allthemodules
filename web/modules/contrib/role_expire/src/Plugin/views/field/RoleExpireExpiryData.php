<?php

/**
 * @file
 * Definition of Drupal\role_expire\Plugin\views\field\RoleExpireExpiryData.
 *
 * References:
 * Class Date from Date.php (core files).
 */

namespace Drupal\role_expire\Plugin\views\field;

use Drupal\role_expire\RoleExpireApiService;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display the role expire data.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("role_expire_expiry_data")
 */
class RoleExpireExpiryData extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['custom_date_format'] = array('default' => 'Y-m-d H:i');
    $options['timezone'] = array('default' => '');

    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    $form['custom_date_format'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#description' => $this->t('See <a href="http://us.php.net/manual/en/function.date.php" target="_blank">the PHP docs</a> for date formats.'),
      '#default_value' => isset($this->options['custom_date_format']) ? $this->options['custom_date_format'] : '',
    );

    $form['timezone'] = array(
      '#type' => 'select',
      '#title' => $this->t('Timezone'),
      '#description' => $this->t('Timezone to be used for date output.'),
      '#options' => array('' => $this->t('- Default site/user timezone -')) + system_time_zones(FALSE),
      '#default_value' => $this->options['timezone'],
    );

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $user = $values->_entity;

    $expirations = \Drupal::service('role_expire.api')->getAllUserRecords($user->id());
    $timezone = !empty($this->options['timezone']) ? $this->options['timezone'] : NULL;
    $format = $this->options['custom_date_format'];

    if (is_array($expirations)) {
      $output = [];
      foreach ($expirations as $role => $timestamp) {
        $date = \Drupal::service('date.formatter')->format($timestamp, 'custom', $format, $timezone);
        $output[] = $this->t('@role (@date)', array('@role' => $role, '@date' => $date));
      }
      return implode(', ', $output);
    }

    return '-';
  }
}
