<?php

/**
 * @file
 * Contains \Drupal\impliedconsent\Form\ImpliedConsentSettingsform.
 */

namespace Drupal\impliedconsent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ImpliedConsentSettingsform
 * @package Drupal\impliedconsent\Form
 */
class ImpliedConsentSettingsform extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'impliedconsent_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['impliedconsent.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('impliedconsent.settings');

    $form['notice_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Notice text'),
      '#default_value' => $config->get('notice_text'),
      '#format' => $config->get('notice_text_format'),
    ];

    $form['confirm_text'] = [
      '#type' => 'textfield',
      '#size' => 30,
      '#maxlength' => 50,
      '#title' => $this->t('Confirmation text'),
      '#default_value' => $config->get('confirm_text'),
      '#rows' => 3,
    ];

    $form['validate_by_click'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Validate by click'),
      '#default_value' => $config->get('validate_by_click'),
    ];

    $form['cookie_expires_in'] = [
      '#type' => 'textfield',
      '#size' => 7,
      '#maxlength' => 7,
      '#title' => $this->t('Cookie expiry value in days'),
      '#default_value' => $config->get('cookie_expires_in'),
    ];

    $form['background_color'] = [
      '#type' => 'textfield',
      '#size' => 7,
      '#maxlength' => 7,
      '#title' => $this->t('CSS color of the background'),
      '#default_value' => $config->get('background_color'),
    ];

    $form['text_color'] = [
      '#type' => 'textfield',
      '#size' => 7,
      '#maxlength' => 7,
      '#title' => $this->t('CSS color of the text'),
      '#default_value' => $config->get('text_color'),
    ];

    $form['link_color'] = [
      '#type' => 'textfield',
      '#size' => 7,
      '#maxlength' => 7,
      '#title' => $this->t('CSS color of the link'),
      '#default_value' => $config->get('link_color'),
    ];

    $form['button_background_color'] = [
      '#type' => 'textfield',
      '#size' => 7,
      '#maxlength' => 7,
      '#title' => $this->t('CSS color of the button background'),
      '#default_value' => $config->get('button_background_color'),
    ];

    $form['button_color'] = [
      '#type' => 'textfield',
      '#size' => 7,
      '#maxlength' => 7,
      '#title' => $this->t('CSS color of the button'),
      '#default_value' => $config->get('button_color'),
    ];

    $form['font_size'] = [
      '#type' => 'textfield',
      '#size' => 7,
      '#maxlength' => 7,
      '#title' => $this->t('CSS font-size'),
      '#default_value' => $config->get('font_size'),
    ];

    $form['font_family'] = [
      '#type' => 'textfield',
      '#size' => 30,
      '#maxlength' => 50,
      '#title' => $this->t('CSS font-family'),
      '#default_value' => $config->get('font_family'),
    ];

    $form['library'] = [
      '#type' => 'textfield',
      '#size' => 30,
      '#maxlength' => 50,
      '#title' => $this->t('CSS Library'),
      '#default_value' => $config->get('library'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo validate hex values, etc
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('impliedconsent.settings')
      ->set('notice_text', $form_state->getValue('notice_text')['value'])
      ->set('notice_text_format', $form_state->getValue('notice_text')['format'])
      ->set('confirm_text', $form_state->getValue('confirm_text'))
      ->set('validate_by_click', $form_state->getValue('validate_by_click'))
      ->set('cookie_expires_in', $form_state->getValue('cookie_expires_in'))
      ->set('background_color', $form_state->getValue('background_color'))
      ->set('text_color', $form_state->getValue('text_color'))
      ->set('link_color', $form_state->getValue('link_color'))
      ->set('button_background_color', $form_state->getValue('button_background_color'))
      ->set('button_color', $form_state->getValue('button_color'))
      ->set('font_size', $form_state->getValue('font_size'))
      ->set('font_family', $form_state->getValue('font_family'))
      ->set('library', $form_state->getValue('library'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
