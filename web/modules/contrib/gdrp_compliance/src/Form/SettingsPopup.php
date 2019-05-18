<?php

namespace Drupal\gdrp_compliance\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the form controller.
 */
class SettingsPopup extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gdrp_compliance';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gdrp_compliance.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gdrp_compliance.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('Display popup'),
      '#open' => TRUE,
    ];
    $form["general"]['popup-guests'] = [
      '#title' => $this->t('Display for guests'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('popup-guests'),
    ];
    $form["general"]['popup-users'] = [
      '#title' => $this->t('Display for auntificated users'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('popup-users'),
    ];
    $form["general"]['popup-position'] = [
      '#title' => $this->t('Popup position'),
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#default_value' => $config->get('popup-position'),
    ];
    $form["general"]['popup-morelink'] = [
      '#title' => $this->t('Url for [More information] button.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config->get('popup-morelink'),
      '#description' => $this->t('Relative path starts with "/", or absolute start with http/https.'),
    ];

    $form['color'] = [
      '#type' => 'details',
      '#title' => $this->t('Color'),
      '#open' => TRUE,
    ];
    $form['color']['info'] = [
      '#markup' => $this->t('Set color by color-widget of use hex value.'),
    ];
    $form['color']['popup-info'] = [
      '#markup' => '<h3>' . $this->t('Popup background color:') . '</h3>',
    ];
    $form["color"]['popup-color'] = [
      '#title' => $this->t('Color'),
      '#type' => 'color',
      '#default_value' => $config->get('popup-color'),
      '#description' => $this->t(
        'Text will be inversed. Now text is @color.',
        ['@color' => $config->get('popup-text')]
      ),
    ];
    $form["color"]['popup-hex'] = [
      '#title' => $this->t('Color HEX'),
      '#type' => 'textfield',
      '#placeholder' => $config->get('popup-color'),
      '#description' => $this->t('Background has opacity 0.9'),
    ];
    $form['color']['button-info'] = [
      '#markup' => '<h3>' . $this->t('Button color:') . '</h3>',
    ];
    $form["color"]['button-color'] = [
      '#title' => $this->t('Color'),
      '#type' => 'color',
      '#default_value' => $config->get('button-color'),
    ];
    $form["color"]['button-hex'] = [
      '#title' => $this->t('Color HEX'),
      '#type' => 'textfield',
      '#placeholder' => $config->get('button-color'),
      '#description' => $this->t(
        'Text will be inversed. Now text is @color.',
        ['@color' => $config->get('button-text')]
      ),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements a form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('gdrp_compliance.settings');
    $popup_color = $form_state->getValue('popup-color');
    if ($popup_hex = $this->tryHex($form_state->getValue('popup-hex'))) {
      $popup_color = $popup_hex;
    }
    $button_color = $form_state->getValue('button-color');
    if ($button_hex = $this->tryHex($form_state->getValue('button-hex'))) {
      $button_color = $button_hex;
    }
    $config
      ->set('popup-users', $form_state->getValue('popup-users'))
      ->set('popup-guests', $form_state->getValue('popup-guests'))
      ->set('popup-position', $form_state->getValue('popup-position'))
      ->set('popup-morelink', $form_state->getValue('popup-morelink'))
      ->set('popup-color', $popup_color)
      ->set('popup-text', $this->getColorContrastInverse($popup_color))
      ->set('button-color', $button_color)
      ->set('button-text', $this->getColorContrastInverse($button_color))
      ->save();
  }

  /**
   * Try hex.
   */
  private function tryHex($color) {
    $result = FALSE;
    $hex = str_replace('#', '', trim($color));
    if (strlen($hex) == 6 && ctype_xdigit($hex)) {
      $result = "#$hex";
    }
    return $result;
  }

  /**
   * Inverse check.
   */
  private function getColorContrastInverse($color) {
    if ($color == 'none') {
      $color = '#306133';
    }
    $hexcolor = substr($color, 1);
    $r = hexdec(substr($hexcolor, 0, 2));
    $g = hexdec(substr($hexcolor, 2, 2));
    $b = hexdec(substr($hexcolor, 4, 2));
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? 'black' : 'white';
  }

}
