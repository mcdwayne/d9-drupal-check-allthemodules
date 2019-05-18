<?php

namespace Drupal\popup_onload\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Class PopupOnLoadSettingsForm.
 *
 * @package Drupal\popup_onload\Form
 *
 * @ingroup popup_onload
 */
class PopupOnLoadSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'popup_onload_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['popup_onload.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('popup_onload.settings')
      ->set(POPUP_ONLOAD_VAR_SORT_METHOD, $form_state->getValue(POPUP_ONLOAD_VAR_SORT_METHOD))
      ->set(POPUP_ONLOAD_VAR_COOKIE_NAME, $form_state->getValue(POPUP_ONLOAD_VAR_COOKIE_NAME))
      ->set(POPUP_ONLOAD_VAR_COOKIE_LIFETIME, $form_state->getValue(POPUP_ONLOAD_VAR_COOKIE_LIFETIME))
      ->set(POPUP_ONLOAD_VAR_DISPLAY_DELAY, $form_state->getValue(POPUP_ONLOAD_VAR_DISPLAY_DELAY))
      ->set(POPUP_ONLOAD_VAR_INCLUDE_PATHS, $form_state->getValue(POPUP_ONLOAD_VAR_INCLUDE_PATHS))
      ->save();
  }

  /**
   * Defines the settings form for Popup On Load entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Create the list of all sorting methods to be used in the form.
    $methods = popup_onload_sort_methods();

    $form['sort_methods'] = array(
      '#type' => 'fieldset',
      '#title' => t('Popup sort methods'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#description' => t('How to determine the popup, displayed to the user.'),
    );

    $form['sort_methods'][POPUP_ONLOAD_VAR_SORT_METHOD] = array(
      '#type' => 'radios',
      '#options' => $methods,
      '#default_value' => $this->popupOnLoadGetDefaults(POPUP_ONLOAD_VAR_SORT_METHOD, POPUP_ONLOAD_DEFAULT_SORT_METHOD),
    );

    $form['misc'] = array(
      '#type' => 'fieldset',
      '#title' => t('Miscellaneous'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#description' => t('Misc settings.'),
    );

    $form['misc'][POPUP_ONLOAD_VAR_COOKIE_NAME] = array(
      '#type' => 'textfield',
      '#title' => t('Popup cookie name'),
      '#description' => t('Override this only if your server configuration filters out cookies with certain pattern.'),
      '#default_value' => $this->popupOnLoadGetDefaults(POPUP_ONLOAD_VAR_COOKIE_NAME, POPUP_ONLOAD_DEFAULT_COOKIE_NAME),
    );

    $form['misc'][POPUP_ONLOAD_VAR_COOKIE_LIFETIME] = array(
      '#type' => 'number',
      '#title' => t('Popup cookie lifetime'),
      '#description' => t('How many seconds popups will not be displayed to the user after the first display.'),
      '#default_value' => $this->popupOnLoadGetDefaults(POPUP_ONLOAD_VAR_COOKIE_LIFETIME, POPUP_ONLOAD_DEFAULT_COOKIE_LIFETIME),
    );

    $form['misc'][POPUP_ONLOAD_VAR_DISPLAY_DELAY] = array(
      '#type' => 'number',
      '#title' => t('Popup display delay'),
      '#description' => t('Delay in milliseconds before the popup is displayed to the user.'),
      '#default_value' => $this->popupOnLoadGetDefaults(POPUP_ONLOAD_VAR_DISPLAY_DELAY, POPUP_ONLOAD_DEFAULT_DELAY),
    );

    $form['misc'][POPUP_ONLOAD_VAR_INCLUDE_PATHS] = array(
      '#type' => 'textarea',
      '#title' => t('Display only at specified paths'),
      '#description' => t('Specify pages by using their paths. Enter one path per line. Use the "*" character as a wildcard. Leave empty to include all pages, except admin ones. %front is the front page.', array('%front' => '<front>')),
      '#default_value' => $this->popupOnLoadGetDefaults(POPUP_ONLOAD_VAR_INCLUDE_PATHS, ''),
    );

    return $form;
  }

  /**
   * Configs getter with default value.
   */
  public static function popupOnLoadGetDefaults($key, $default) {
    $configs = \Drupal::config('popup_onload.settings');
    $config = $configs->get($key);
    return isset($config) ? $config : $default;
  }

}
