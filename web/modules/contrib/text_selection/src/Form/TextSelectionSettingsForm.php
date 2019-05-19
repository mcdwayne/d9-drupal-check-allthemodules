<?php
/**
 * @file
 * Contains \Drupal\text_selection\Form\TextSelectionSettingsForm
 */
namespace Drupal\text_selection\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure text_selection settings for this site.
 */
class TextSelectionSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'text_selection_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'text_selection.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('text_selection.settings');

    $form['text_selection_bg_color'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Background Color'),
      '#description' => $this->t('Enter color in hex code.'),
      '#default_value' => $config->get('text_selection_bg_color'),
      '#required' => TRUE,
    );

    $form['text_selection_font_color'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Font Color'),
      '#description' => $this->t('Enter color in hex code.'),
      '#default_value' => $config->get('text_selection_font_color'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Regex to validate user entered hex color code.
    $regex_hex_color_code = "/^#([0-9a-fA-F]{3})([0-9a-fA-F]{3})?$/";
    // Fetch bg and font colors using form_state.
    $text_selection_bg_color = $form_state->getValue('text_selection_bg_color');
    $text_selection_font_color = $form_state->getValue('text_selection_font_color');

    if (!empty($text_selection_bg_color)) {
      if (!preg_match($regex_hex_color_code, $text_selection_bg_color)) {
        $form_state->setErrorByName('text_selection_bg_color', $this->t('The background color must fit the hexadecimal HTML color default format.'));
      }
    }
    if (!empty($text_selection_font_color)) {
      if (!preg_match($regex_hex_color_code, $text_selection_font_color)) {
        $form_state->setErrorByName('text_selection_font_color', $this->t('The font color must fit the hexadecimal HTML color default format.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('text_selection.settings');
    $config->set('text_selection_bg_color', $form_state->getValue('text_selection_bg_color'))
      ->save();
    $config->set('text_selection_font_color', $form_state->getValue('text_selection_font_color'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
