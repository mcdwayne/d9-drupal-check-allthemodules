<?php

namespace Drupal\business_rules\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides BusinessRules settings form.
 *
 * @package Drupal\business_rules\Form
 */
class BusinessRulesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'business_rules_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['business_rules.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('business_rules.settings');

    $form['enable_scheduler'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable schedule on cron'),
      '#description'   => $this->t('Enable execution of scheduled items when cron runs.'),
      '#default_value' => $config->get('enable_scheduler'),
    ];

    $form['enable_safemode'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable safe mode'),
      '#description'   => $this->t('Enable Business Rules safe mode, include query string: <code>brmode=safe</code>.'),
      '#default_value' => $config->get('enable_safemode'),
    ];

    $form['debug_screen'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show debug information on screen'),
      '#description'   => $this->t('You will need to add the Business rules debug block on Structure / Block layout (visible only when this option is checked).'),
      '#default_value' => $config->get('debug_screen'),
    ];

    $form['clear_render_cache'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Clear rendered cache on each request'),
      '#description'   => $this->t("Disable the Drupal render HTML cache. IT WILL REDUCE THE SITE PERFORMANCE, use with wisdom. It makes sense if you have rules triggered by the event 'Entity is viewed' and you need to evaluate this rule every time the entity is rendered."),
      '#default_value' => $config->get('clear_render_cache'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enable_scheduler = $form_state->getValue('enable_scheduler');
    $debug_screen = $form_state->getValue('debug_screen');
    $clear_render_cache = $form_state->getValue('clear_render_cache');
    $enable_safemode = $form_state->getValue('enable_safemode');

    $this->config('business_rules.settings')
      ->set('enable_scheduler', $enable_scheduler)
      ->set('debug_screen', $debug_screen)
      ->set('clear_render_cache', $clear_render_cache)
      ->set('enable_safemode', $enable_safemode)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
