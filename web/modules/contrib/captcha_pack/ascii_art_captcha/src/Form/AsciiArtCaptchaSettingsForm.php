<?php

namespace Drupal\ascii_art_captcha\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the pants settings form.
 */
class AsciiArtCaptchaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ascii_art_captcha.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ascii_art_captcha_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ascii_art_captcha.settings');

    $available_fonts = _ascii_art_captcha_available_fonts();
    $code_length_options = [4, 5, 6, 7, 8, 9, 10];
    $form['ascii_art_captcha_code_length'] = [
      '#type' => 'select',
      '#title' => $this->t('Code length'),
      '#options' => array_combine($code_length_options, $code_length_options),
      '#default_value' => $config->get('ascii_art_captcha_code_length'),
    ];

    $form['ascii_art_captcha_font'] = [
      '#type' => 'select',
      '#title' => $this->t('Font'),
      '#options' => $available_fonts,
      '#default_value' => $config->get('ascii_art_captcha_font'),
      '#description' => $this->t('Define the ASCII art font to use. Note that some characters are not very recognizable in some (small/weird) fonts. Make sure to disable the right character sets in these cases.'),
    ];

    // Font size.
    $font_sizes = [0 => $this->t('default')];
    foreach ([4, 6, 8, 9, 10, 11, 12] as $pt) {
      $font_sizes[$pt] = $pt . 'pt';
    }
    $form['ascii_art_captcha_font_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Font size'),
      '#options' => $font_sizes,
      '#default_value' => $config->get('ascii_art_captcha_font_size'),
      '#description' => $this->t('Set the font size for the ASCII art.'),
    ];

    $form['ascii_art_captcha_allowed_characters'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Character sets to use'),
      '#options' => [
        'upper' => $this->t('upper case characters'),
        'lower' => $this->t('lower case characters'),
        'digit' => $this->t('digits'),
      ],
      '#default_value' => $config->get('ascii_art_captcha_allowed_characters'),
      '#description' => $this->t('Enable the character sets to use in the code. Choose wisely by taking the recognizability of the used font into account.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (count(array_filter($form_state->getValue('ascii_art_captcha_allowed_characters'))) < 1) {
      $form_state->setErrorByName('ascii_art_captcha_allowed_characters', $this->t('You should select at least one type of characters to use.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ascii_art_captcha.settings');
    $config->set('ascii_art_captcha_code_length', $form_state->getValue('ascii_art_captcha_code_length'));
    $config->set('ascii_art_captcha_font', $form_state->getValue('ascii_art_captcha_font'));
    $config->set('ascii_art_captcha_font_size', $form_state->getValue('ascii_art_captcha_font_size'));
    $config->set('ascii_art_captcha_allowed_characters', $form_state->getValue('ascii_art_captcha_allowed_characters'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
