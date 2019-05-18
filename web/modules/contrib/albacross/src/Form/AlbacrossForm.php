<?php

namespace Drupal\albacross_drupal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form class.
 */
class AlbacrossForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'albacross_drupal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('albacross_drupal.settings');

    $form['trackID'] = [
      '#type' => 'number',
      '#title' => $this->t('Albacross tracking ID'),
      '#default_value' => $config->get('albacross_drupal.trackID'),
      '#description' => $this->t('Albacross tracking ID which you can find in your personal area'),
    ];

    return $form;
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
    $config = $this->config('albacross_drupal.settings');
    $config->set('albacross_drupal.trackID', $form_state->getValue('trackID'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'albacross_drupal.settings',
    ];
  }

}
