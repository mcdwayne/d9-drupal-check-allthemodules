<?php

namespace Drupal\whatsnew\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for performing a 1-click site backup.
 */
class WhatsnewKeyForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'whatsnew_ui_key';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'whatsnew.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('whatsnew.settings');

    $form['whatsnew_sitekey'] = [
      '#type' => 'textfield',
      '#title' => t('Site key'),
      '#default_value' => $config->get('key'),
      '#size' => 50,
      '#maxlength' => 40,
      '#description' => t("Authentication key required by What's New Dashboard."),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  /* public function validateForm(array &$form,FormStateInterface $form_state) {
  parent::validateForm($form, $form_state);
  } */

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('whatsnew.settings')
      ->set('key', $form_state->getValue('whatsnew_sitekey'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
