<?php

/**
 * @file
 * Contains \Drupal\promotion_queue\Form\SettingsForm.
 */

namespace Drupal\hashcash\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for Hashcash.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hashcash_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hashcash.settings');

    $form['log'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log failed hashcash'),
      '#description' => t('Report information about failed attempts to the !watchdoglog.', array('!watchdoglog' => l(t('log'), 'admin/reports/dblog'))),
      '#default_value' => $config->get('log')
    );
    $form['addorignore'] = array(
      '#type' => 'radios',
      '#title' => t('Use hashcash on specific forms'),
      '#default_value' => $config->get('addorignore'),
      '#options' => array(
        0 => t('Add to every form except the listed ones'),
        1 => t('Add to only the listed forms')
      )
    );
    $form['form_ids'] = array(
      '#type' => 'textarea',
      '#title' => t('Form IDs'),
      '#default_value' => $config->get('form_ids'),
      '#description' => t('Enter one form ID per line.')
    );
    $form['expire'] = array(
      '#type' => 'select',
      '#title' => t('Expire time (days)'),
      '#default_value' => $config->get('expire', 2),
      '#description' => t('Time after which hashcash values can be repeated'),
      '#options' => array(
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7
      )
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('hashcash.settings')
      ->set('log', $form_state['values']['log'])
      ->set('addorignore', $form_state['values']['addorignore'])
      ->set('form_ids', $form_state['values']['form_ids'])
      ->set('expire', $form_state['values']['expire'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
