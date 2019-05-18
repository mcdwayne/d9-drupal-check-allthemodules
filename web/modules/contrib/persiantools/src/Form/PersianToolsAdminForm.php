<?php

namespace Drupal\persiantools\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PersianToolsAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'persiantools_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['digit_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Digits and Numbers Settings'),
    );

    $form['digit_settings']['digit_method'] = array(
      '#type' => 'radios',
      '#title' => t('Conversion Method'),
      '#options' => array(
        'none' => t('None'),
        'smart' => t('Smart'),
        'full' => t('Full'),
      ),
      '#description' => t('Select method for converting english numbers to persian.'),
      '#default_value' => $this->config('persiantools.settings')->get('digit_method'),
    );

    $form['rtlmaker'] = array(
      '#type' => 'checkbox',
      '#title' => t('Fix multi-directional texts'),
      '#description' => t('Fix mess in mixed english and persian texts.'),
      '#default_value' => $this->config('persiantools.settings')->get('rtlmaker'),
    );

    $form['sort_fix'] = array(
      '#type' => 'fieldset',
      '#title' => t('Persian Sort'),
    );
    $form['sort_fix']['submit_btn'] = array(
      '#type' => 'submit',
      '#value' => t('Fix persian sort in all tables'),
      '#submit' => array('persiantools_sort_fix_submit'),
    );

    return $form;
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
    $this->config('persiantools.settings')
      ->set('digit_method', $form_state->getValue('digit_method'))
      ->set('rtlmaker', $form_state->getValue('rtlmaker'))
    ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function getEditableConfigNames() {
    return ['persiantools.settings'];
  }
}
