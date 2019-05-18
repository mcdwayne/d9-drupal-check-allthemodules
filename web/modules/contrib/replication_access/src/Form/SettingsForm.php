<?php

namespace Drupal\replication_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\replication_access\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'replication.replication_settings.access',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'replication_access_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('replication.replication_settings.access');

    $form['uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UID'),
      '#default_value' => $config->get('mapping_type') === 'uid' ? $config->get('uid') : '',
      '#maxlength' => 60,
      '#size' => 30
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $uid = trim($form_state->getValue('uid'));
    if (is_numeric($uid)) {
      if (!$storage->load($uid)) {
        $form_state->setErrorByName('uid', "Provided UID doesn't exist.");
      }
    }
    elseif ($form_state->getValue('mapping_type') === 'uid' && !is_numeric($uid)) {
      $form_state->setErrorByName('uid', 'Empty or wrong format for the UID field.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('replication.replication_settings.access');
    $mapping_type = $form_state->getValue('mapping_type');
    
    $uid = $form_state->getValue('uid');

    $config
      ->set('parameters.uid', trim($uid))
      ->save();
  }

}
