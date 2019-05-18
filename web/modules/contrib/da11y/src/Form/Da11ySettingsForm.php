<?php

namespace Drupal\da11y\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure da11y settings for this site.
 */
class Da11ySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'da11y_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['da11y.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('da11y.settings');
    $da11ySetting = $config->get('settings');

    $fields = array(
        'da11y_resize_font_plus' => 'Increase',
        'da11y_resize_font_minus' => 'Decrease',
        'da11y_bg_grayscale' => 'Grayscale',
        'da11y_bg_high_contrast' => 'High Contrast',
        'da11y_bg_negative_contrast' => 'Negative Contrast',
        'da11y_bg_light' => 'Light Background',
        'da11y_links_underline' => 'Links Underline',
        'da11y_readable_font' => 'Readable Font',
        'da11y_reset' => 'Reset',
    );

    $links = array(
        'da11yLink_sitemap' => 'Sitemap',
        'da11yLink_help' => 'Help',
    );

    $form["da11y_labels"] = array(
        '#type' => 'fieldset',
        '#title' => t("Da11y label's"),
    );

    foreach ($fields as $key => $value){
      $form["da11y_labels"][$key] = array(
          '#type' => 'textfield',
          '#title' => t($value),
          '#default_value' => isset($da11ySetting[$key]) ? $da11ySetting[$key] : $value,
      );
    }


    foreach ($links as $key => $value){
      $form["da11y_links_fieldset".$key] = array(
          '#type' => 'fieldset',
          '#title' => t("Da11y links: ". $value),
      );
      $form["da11y_links_fieldset".$key][$key.'_title'] = array(
          '#type' => 'textfield',
          '#title' => t('Title'),
          '#default_value' => isset($da11ySetting[$key.'_title']) ? $da11ySetting[$key.'_title'] : $value,
      );
      $form["da11y_links_fieldset".$key][$key.'_url'] = array(
          '#type' => 'textfield',
          '#title' => t('Url'),
          '#default_value' => isset($da11ySetting[$key.'_url']) ? $da11ySetting[$key.'_url'] : $value,
      );
    }


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $da11ySetting = \Drupal::service('config.factory')->getEditable('da11y.settings');
    $da11ySetting->set('settings', $form_state->getValues())
        ->save();

    parent::submitForm($form, $form_state);
  }

}
