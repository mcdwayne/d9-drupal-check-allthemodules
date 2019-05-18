<?php

namespace Drupal\ops\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class onPageSearchSetting extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ops_color_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ops.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ops.settings');

    $form['ops_bk_color_textbox'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Background Color'),
      '#default_value' => $config->get('ops_bk_color'),
      '#description' => $this->t("Please enter color code in hash example: #cccccc.")
    );
    
    $form['ops_text_color_textbox'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Search Text Color'),
      '#default_value' => $config->get('ops_text_color'),
      '#description' => $this->t("Please enter color code in hash example: #000000.")
    );
    
    $form['ops_placeholder_textbox'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Search Box Placeholder Value'),
      '#default_value' => $config->get('ops_placeholder'),
      '#description' => $this->t("Please enter placeholder value.")
    );  

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      // Retrieve the configuration
      \Drupal::configFactory()->getEditable('ops.settings')
      // Set the submitted configuration setting
      ->set('ops_bk_color', $form_state->getValue('ops_bk_color_textbox'))
      ->set('ops_text_color', $form_state->getValue('ops_text_color_textbox'))
      ->set('ops_placeholder', $form_state->getValue('ops_placeholder_textbox'))
      ->save();

    parent::submitForm($form, $form_state);
  }
  
  /** 
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $background_color = $form_state->getValue('ops_bk_color_textbox');
    $text_color = $form_state->getValue('ops_text_color_textbox');
    if(!empty($background_color) && !preg_match('/^#[a-f0-9]{6}$/i', $background_color)) {
      $form_state->setErrorByName('ops_bk_color_textbox', $this->t("Please enter color code in hash example: #cccccc"));
    }
    if(!empty($text_color) && !preg_match('/^#[a-f0-9]{6}$/i', $text_color)) {
      $form_state->setErrorByName('ops_text_color_textbox', $this->t("Please enter color code in hash example: #000000"));
    }
  }
}