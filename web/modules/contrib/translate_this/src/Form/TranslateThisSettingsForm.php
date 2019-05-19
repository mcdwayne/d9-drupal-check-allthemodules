<?php

namespace Drupal\translate_this\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Displays the translate this settings form.
 */
class TranslateThisSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'translate_this_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['translate_this.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('translate_this.settings');
    $settings = $config->get();

    $form['general'] = array(
      '#type' => 'fieldset',
      '#title' => t('General settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['general']['translate_this_scope'] = array(
      '#type' => 'textfield',
      '#title' => t('Translation scope'),
      '#description' => t('Enter a CSS id of the element to translate (eg. header) if you want to translate only part of your website.'),
      '#default_value' => $settings['translate_this_scope'],
    );
    $form['general']['translate_this_use_cookie'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use cookie'),
      '#description' => t('Whether to use the cookie to automatically translate all the pages on your site after a user has translated one. Set to false to only translate single page.'),
      '#default_value' => $settings['translate_this_use_cookie'],
    );

    $form['language_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Language settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $available_languages = array("unknown" => t('Unknown')) + translate_this_languages();
    $form['language_settings']['translate_this_from_language'] = array(
      '#type' => 'select',
      '#title' => t('From language'),
      '#description' => t('The language your blog is written in. Set to <em>Unknown</em> if using multiple languages (and the Google Language API will determine the from language automatically). However if your site is in a single language you can get a slight performance gain by setting this.'),
      '#options' => $available_languages,
      '#default_value' => t($settings['translate_this_from_language']),
    );
    $form['language_settings']['translate_this_main_panel_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Main panel text'),
      '#description' => t('The descriptive text above the flag dropdown and overlay.'),
      '#default_value' => t($settings['translate_this_main_panel_text']),
      '#required' => TRUE,
    );
    $form['language_settings']['translate_this_more_languages_text'] = array(
      '#type' => 'textfield',
      '#title' => t('More languages text'),
      '#description' => t("Text for the 'More Languages' link that calls up the overlay."),
      '#default_value' => t($settings['translate_this_more_languages_text']),
      '#required' => TRUE,
    );
    $form['language_settings']['translate_this_busy_translating_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Busy translating text'),
      '#description' => t('Text shown in the overlay while the translation is processing.'),
      '#default_value' => t($settings['translate_this_busy_translating_text']),
      '#required' => TRUE,
    );
    $form['language_settings']['translate_this_cancel_translating_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Cancel translating text'),
      '#description' => t('The text for the cancel link in the overlay that shows while the translation is processing.'),
      '#default_value' => t($settings['translate_this_cancel_translating_text']),
      '#required' => TRUE,
    );
    $form['language_settings']['translate_this_undo_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Undo text'),
      '#description' => t('The text shown in the undo translation bar that shows for a couple seconds after the translation completes.'),
      '#default_value' => t($settings['translate_this_undo_text']),
      '#required' => TRUE,
    );
    $form['language_settings']['translate_this_undo_time'] = array(
      '#type' => 'select',
      '#title' => t('Undo time'),
      '#description' => t('How long should this undo text be visible? The default is 4 seconds.'),
      '#default_value' => $settings['translate_this_undo_time'],
      '#options' => array(
        1000 => t('1 second'),
        2000 => t('2 seconds'),
        3000 => t('3 seconds'),
        4000 => t('4 seconds'),
        5000 => t('5 seconds'),
        10000 => t('10 seconds'),
        0 => t('Always visible'),
      ),
      '#required' => TRUE,
    );

    $form['language_dropdown'] = array(
      '#type' => 'fieldset',
      '#title' => t('Language in dropdown'),
      '#collapsible' => TRUE,
      '#collapsed' => $settings['translate_this_use_default_languages'],
    );
    $form['language_dropdown']['translate_this_use_default_languages'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use default languages in dropdown'),
      '#default_value' => $settings['translate_this_use_default_languages'],
      '#description' => t('Whether to use the default set of languages in the dropdown. Unchecking this brings up checkboxes you can use to select any of the 52 languages supported by the TranslateThis Button.'),
    );
    $form['language_dropdown']['translate_this_available_languages'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Available languages in dropdown'),
      '#default_value' => !empty($settings['translate_this_available_languages']) ? $settings['translate_this_available_languages'] : translate_this_languages(),
      '#options' => translate_this_languages(),
      '#description' => t('Select the languages you want to enable for the language dropdown.'),
      '#states' => array(
        'visible' => array(
          ':input[name="translate_this_use_default_languages"]' => array('checked' => FALSE),
        ),
        'required' => array(
          ':input[name="translate_this_use_default_languages"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['display_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Display Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => $settings['translate_this_disable_button_image'] ? FALSE : TRUE,
    );
    $form['display_settings']['translate_this_disable_button_image'] = array(
      '#type' => 'checkbox',
      '#title' => t('Disable the default button image'),
      '#description' => t('Set this to use another button  instead of the default TranslateThis Button image.'),
      '#default_value' => $settings['translate_this_disable_button_image'],
    );
    $form['display_settings']['translate_this_alternate_button_image_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Alternate button image path'),
      '#description' => t('Use a custom image for the translation button by putting the path here. Leaving this blank displays the string <em>Translate</em> instead.'),
      '#default_value' => $settings['translate_this_alternate_button_image_path'],
      '#states' => array(
        'invisible' => array(
          ':input[name="translate_this_disable_button_image"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['display_settings']['translate_this_button_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Button width'),
      '#description' => t('The width of your custom button image.'),
      '#default_value' => $settings['translate_this_button_width'],
      '#size' => 5,
      '#maxlength' => 4,
      '#states' => array(
        'invisible' => array(
          ':input[name="translate_this_disable_button_image"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['display_settings']['translate_this_button_height'] = array(
      '#type' => 'textfield',
      '#title' => t('Button height'),
      '#description' => t('The height of your custom button image.'),
      '#default_value' => $settings['translate_this_button_height'],
      '#size' => 5,
      '#maxlength' => 4,
      '#states' => array(
        'invisible' => array(
          ':input[name="translate_this_disable_button_image"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['display_settings']['translate_this_disable_flag_thumbnails'] = array(
      '#type' => 'checkbox',
      '#title' => t('Disable flag thumbnails'),
      '#descripion' => t('Use text-only links for the various languages instead of the default flag icons with text.'),
      '#default_value' => $settings['translate_this_disable_flag_thumbnails'],
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $button_image = $form_state->getValue('translate_this_disable_button_image');
    if ($button_image) {
      $alt_image = $form_state->getValue('translate_this_alternate_button_image_path');
      if (!empty($alt_image)) {
        if (is_file($alt_image)) {
          if (getimagesize($alt_image)) {
            $width = $form_state->getValue('translate_this_button_width');
            if (empty($width) || !is_numeric($width)) {
              $form_state->setErrorByName('translate_this_button_width', t('If you specify an alternate image path, please also specify a width.'));
            }
            $height = $form_state->getValue('translate_this_button_height');
            if (empty($height) || !is_numeric($height)) {
              $form_state->setErrorByName('translate_this_button_height', t('If you specify an alternate image path, please also specify a height.'));
            }
          }
          else {
            $form_state->setErrorByName('translate_this_alternate_button_image_path', t('This is not a valid image. Please make sure you have entered the correct URL.'));
          }
        }
        else {
          $form_state->setErrorByName('translate_this_alternate_button_image_path', t('The file could not be found. Please make sure you have entered the correct URL.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('translate_this.settings');
    foreach (Element::children($form['general']) as $variable) {
      $config->set($variable, $form_state->getValue($form['general'][$variable]['#parents']));
    }
    foreach (Element::children($form['language_settings']) as $variable) {
      $config->set($variable, $form_state->getValue($form['language_settings'][$variable]['#parents']));
    }
    foreach (Element::children($form['language_dropdown']) as $variable) {
      $config->set($variable, $form_state->getValue($form['language_dropdown'][$variable]['#parents']));
    }
    foreach (Element::children($form['display_settings']) as $variable) {
      $config->set($variable, $form_state->getValue($form['display_settings'][$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
