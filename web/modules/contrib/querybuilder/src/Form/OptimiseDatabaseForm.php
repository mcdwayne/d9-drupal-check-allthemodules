<?php

namespace Drupal\querybuilder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class OptimiseDatabaseForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'querybuilder_optimise';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'querybuilder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['clear_cache_by_cron'] = array(
      '#type' => 'checkbox',
      '#title' => t('Optimise database'),
      '#default_value' => $this->config('querybuilder.settings')->get('clear_cache_by_cron'),
      '#description' => $this->t('When checked, clear cache every time when cron run.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $clear_cache_by_cron = $form_state->getValue('clear_cache_by_cron');
    $this->config('querybuilder.settings')
      ->set('clear_cache_by_cron', $clear_cache_by_cron)
      ->save();
    parent::submitForm($form, $form_state);
  }

}