<?php

namespace Drupal\fancy_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Creates the settings form for the fancy login module.
 */
class FancyLoginSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fancy_login_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fancy_login.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fancy_login.settings');

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display'),
      '#open' => FALSE,
    ];

    $form['display']['explanation'] = [
      '#value' => '<p>' . $this->t('All settings on this page must be valid CSS settings for the item that they will modify. For information on what types of values are valid, check the links included in the descriptions underneath the inputs.') . '</p>',
    ];

    $form['display']['screen_fade_color'] = [
      '#title' => $this->t('Screen Fade Color'),
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 8,
      '#default_value' => $config->get('screen_fade_color'),
      '#description' => $this->t('This is the color that the screen fades to when the login box is activated. This should generally be black, white, or the same color as the background of your site. CSS property: <a href="@url.">background-color</a>', ['@url' => 'http://www.devguru.com/technologies/css2/8047']),
    ];

    $form['display']['screen_fade_z_index'] = [
      '#title' => $this->t('Screen Fade z-index'),
      '#type' => 'textfield',
      '#maxlength' => 4,
      '#size' => 8,
      '#default_value' => $config->get('screen_fade_z_index'),
      '#description' => $this->t('This is the z-index of the faded screen. If you find elements on your layout are appearing over top of the faded out part of your screen, you can increase this number, but you should probably not touch it otherwise. CSS propery <a href="@url">z-index</a>.', ['@url' => 'http://www.devguru.com/technologies/css2/8139']),
    ];

    $form['display']['login_box_background_color'] = [
      '#title' => $this->t('Login Box Background Color'),
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 8,
      '#default_value' => $config->get('login_box_background_color'),
      '#description' => $this->t('This is the background color of the login box. CSS property: <a href="@url">background-color</a>.', ['@url' => 'http://www.devguru.com/technologies/css2/8047']),
    ];

    $form['display']['login_box_text_color'] = [
      '#title' => $this->t('Login Box Text Color'),
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 8,
      '#default_value' => $config->get('login_box_text_color'),
      '#description' => $this->t('This is the color of the text inside the login box. CSS property: <a href="@url">color</a>.', ['@url' => 'http://www.devguru.com/technologies/css2/8077']),
    ];

    $form['display']['login_box_border_color'] = [
      '#title' => $this->t('Login Box Border Color'),
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 8,
      '#default_value' => $config->get('login_box_border_color'),
      '#description' => $this->t('This is the color of the border around the login box. CSS property: <a href="@url">border-color</a>.', ['@url' => 'http://www.devguru.com/technologies/css2/8057']),
    ];

    $form['display']['login_box_border_width'] = [
      '#title' => $this->t('Login Box Border Width'),
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 8,
      '#default_value' => $config->get('login_box_border_width'),
      '#description' => $this->t('This is the width of the border around the login box. CSS property: <a href="@url">border-width</a>.', ['@url' => 'http://www.devguru.com/technologies/css2/8072']),
    ];

    $form['display']['login_box_border_style'] = [
      '#title' => $this->t('Login Box Border Style'),
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 8,
      '#default_value' => $config->get('login_box_border_style'),
      '#description' => $this->t('This is the style (eg: solid, dotted) of the border around the login box. CSS property: <a href="@url">border-style</a>.', ['@url' => 'http://www.devguru.com/technologies/css2/8067']),
    ];

    $form['display']['hide_objects'] = [
      '#title' => $this->t('Hide Objects'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('hide_objects'),
      '#description' => $this->t('If you are having issues where the fancy login box is being hidden behind videos or other flash objects, check this box to hide the objects while the login box is being shown'),
    ];

    $form['display']['dim_fade_time'] = [
      '#title' => $this->t('Background Fade Time'),
      '#type' => 'textfield',
      '#default_value' => $config->get('dim_fade_time'),
      '#maxlength' => 4,
      '#size' => 8,
      '#description' => $this->t('This is the number of milliseconds it will take for the fullscreen background color to fade in/out. The higher the number, the slower the fade process will be. The lower the number, the faster the fade.'),
    ];

    $form['display']['login_box_fade_time'] = [
      '#title' => $this->t('Login Box Fade Time'),
      '#type' => 'textfield',
      '#default_value' => $config->get('login_box_fade_time'),
      '#maxlength' => 4,
      '#size' => 8,
      '#description' => $this->t('This is the number of milliseconds it will take for the login box to fade in/out. The higher the number, the slower the fade process will be. The lower the number, the faster the fade.'),
    ];

    $form['no_redirect'] = [
      '#title' => $this->t('Keep User on Same Page'),
      '#type' => 'checkbox',
      '#description' => $this->t('If this box is checked, the user will not be redirected upon login, and will stay on the page from which the login link was clicked. If this box is unchecked, the user will be redirected according to the Drupal system settings'),
      '#default_value' => $config->get('no_redirect'),
    ];

    $form['ssl'] = [
      '#type' => 'details',
      '#title' => $this->t('SSL (Secure Login)'),
      '#open' => FALSE,
    ];

    $form['ssl']['https'] = [
      '#title' => $this->t('Enable SSL (HTTPS)'),
      '#type' => 'checkbox',
      '#description' => $this->t('If this box is checked, the form will be posted as encrypted data (HTTPS/SSL). Only use this if you have already set up an SSL certificate on your site, and have set up the login page as an encrypted page.'),
      '#default_value' => $config->get('https'),
    ];

    $form['ssl']['icon_position'] = [
      '#type' => 'radios',
      '#title' => $this->t('Secure login icon position'),
      '#options' => [
        $this->t("Don't show icon"),
        $this->t('Above the form'),
        $this->t('Below the form'),
      ],
      '#default_value' => $config->get('icon_position'),
      '#description' => $this->t("If SSL is turned on, turning this option on will display an icon indicating that the login is secure"),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $test_values = [
      'screen_fade_color',
      'screen_fade_z_index',
      'login_box_background_color',
      'login_box_text_color',
      'login_box_border_color',
      'login_box_border_width',
      'login_box_border_style',
    ];

    foreach ($test_values as $machine_name) {
      if (!strlen($form_state->getValue($machine_name))) {
        $form_state->setError($form[$machine_name], $this->t('@field must contain a value', ['@field' => $form[$machien_name]['#title']]));
      }
    }

    if (!is_numeric(trim($form_state->getValue('dim_fade_time')))) {
      $form_state->setError($form['display']['dim_fade_time'], $this->t('Background Fade Speed must contain a numeric value'));
    }

    if (!is_numeric(trim($form_state->getValue('login_box_fade_time')))) {
      $form_state->setError($form['display']['login_box_fade_time'], $this->t('Login Box Fade Speed must contain a numeric value'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('fancy_login.settings')
      ->set('screen_fade_color', $form_state->getValue('screen_fade_color'))
      ->set('screen_fade_z_index', $form_state->getValue('screen_fade_z_index'))
      ->set('login_box_background_color', $form_state->getValue('login_box_background_color'))
      ->set('login_box_text_color', $form_state->getValue('login_box_text_color'))
      ->set('login_box_border_color', $form_state->getValue('login_box_border_color'))
      ->set('login_box_border_width', $form_state->getValue('login_box_border_width'))
      ->set('login_box_border_style', $form_state->getValue('login_box_border_style'))
      ->set('hide_objects', $form_state->getValue('hide_objects'))
      ->set('dim_fade_time', $form_state->getValue('dim_fade_time'))
      ->set('login_box_fade_time', $form_state->getValue('login_box_fade_time'))
      ->set('no_redirect', $form_state->getValue('no_redirect'))
      ->set('https', $form_state->getValue('https'))
      ->set('icon_position', $form_state->getValue('icon_position'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
