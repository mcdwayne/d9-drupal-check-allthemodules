<?php

namespace Drupal\freecaster\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UserInfoForm.
 *
 * @package Drupal\freecaster\Form
 */
class UserInfoForm extends ConfigFormBase {

  const FC_CACHE_TTL = 600;
  const FC_API_ENTRYPOINT = 'https://freecaster.tv/api/';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fc_user_info_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('freecaster.settings');

    $form['fc_user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API user ID'),
      '#description' => $this->t('Freecaster User ID'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('fc_user_id'),
    ];
    $form['fc_user_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API user Key'),
      '#description' => $this->t('Freecaster User Key'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('fc_user_key'),
    ];

    $form['fc_user_entrypoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t("Freecaster API entrypoint"),
      '#default_value' => $config->get('fc_user_entrypoint', self::FC_API_ENTRYPOINT),
      '#size'          => 64,
      '#description'   => $this->t("The URL used to contact the Freecaster Platform API."),
      '#required'      => FALSE,
    ];

    $form['fc_cache_ttl'] = [
      '#type'          => 'textfield',
      '#title'         => t("Cache lifetime"),
      '#default_value' => $config->get('fc_cache_ttl', self::FC_CACHE_TTL),
      '#size'          => 2,
      '#description'   => $this->t("The number of seconds to keep Freecaster Platform data in the cache."),
      '#required'      => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }
    $config = \Drupal::service('config.factory')->getEditable('freecaster.settings');
    $config->set('fc_user_id', $form_state->getValue('fc_user_id'))
        ->save();
    $config->set('fc_user_key', $form_state->getValue('fc_user_key'))
        ->save();
    $config->set('fc_user_entrypoint', $form_state->getValue('fc_user_entrypoint'))
        ->save();
    $config->set('fc_cache_ttl', $form_state->getValue('fc_cache_ttl'))
        ->save();
    parent::submitForm($form, $form_state);

  }

  /**
   *
   */
  protected function getEditableConfigNames() {
    return ['freecaster.settings'];
  }

}
