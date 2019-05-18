<?php

namespace Drupal\demo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * This class will return the form demo_admin_settings.
 */
class DemoAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'demo_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    drupal_set_message(t('Snapshot file system path: private://' . \Drupal::state()->get('demo_dump_path', 'demo')), 'warning');
    $intervals = [
    // 0, 5, 10, 15, 30 minutes.
      0, 300, 600, 900, 1800,
    // 1-6, 9, 12 hours.
      3600, 3600 * 2, 3600 * 3, 3600 * 4, 3600 * 5, 3600 * 6, 3600 * 9, 3600 * 12,
    // 1-3 days.
      86400, 86400 * 2, 86400 * 3,
    // 1, 2, 3 weeks.
      604800, 604800 * 2, 604800 * 3,
    // 1, 3 months.
      86400 * 30, 86400 * 90,
    ];

    $options = build_options($intervals);
    $demo_manage_form_url = Url::fromRoute('demo.manage_form');
    $cron_url = Url::fromRoute('system.cron_settings');
    $form['demo_reset_interval'] = [
      '#type' => 'select',
      '#title' => t('Automatic reset interval'),
      '#required' => FALSE,
      '#default_value' => \Drupal::config('demo.settings')->get('demo_reset_interval', 0),
      '#options' => $options,
      '#empty_value' => 0,
      '#description' => t('Requires a ' . \Drupal::l(t('default snapshot'), $demo_manage_form_url) . ' and ' . \Drupal::l(t('cron'), $cron_url) . ' to run in the configured interval.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('demo.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['demo.settings'];
  }

}
