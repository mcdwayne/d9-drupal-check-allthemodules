<?php
/**
* @file
* Contains \Drupal\persona\Form\AdminForm.
*/

namespace Drupal\persona\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
* Defines a form to configure maintenance settings for this site.
*/
class AdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'persona_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $persona_config = \Drupal::config('persona.settings');
    // General settings.
    $form['general'] = array(
      '#type' => 'fieldset',
      '#title' => t("General"),
    );
    $form['general']['take_over'] = array(
      '#type' => 'checkbox',
      '#title' => t("Set Persona as the only sign in method"),
      '#default_value' => $persona_config->get('take_over'),
      '#description' => t("It will only be possible to register or sign in with !link.<br />Email and password fields will not be present on account edit pages.",
        array('!link' => l("Mozilla Persona", 'https://www.mozilla.org/persona/',
          array('attributes' => array('target' => '_blank'))))),
    );
    $form['general']['email_usernames'] = array(
      '#type' => 'checkbox',
      '#title' => t("Use email addresses as usernames"),
      '#default_value' => $persona_config->get('email_usernames'),
      '#description' => t("When Persona is used to register a new account or change an existing account's email, the username will be set to the email address used.<br />If this option is not enabled, the username part of the email address will be used when registering.<br />When using this option with Persona as the only sign in method, it is useful to disable permission \"change own username\"."),
    );
    $form['general']['new_account_edit'] = array(
      '#type' => 'checkbox',
      '#title' => t("Redirect to account edit page when user signs in for the first time"),
      '#default_value' => $persona_config->get('new_account_edit'),
      '#description' => t("When the form is submitted the user will be redirected back to the page where they signed in."),
    );
    $form['general']['improve_frontend'] = array(
      '#type' => 'checkbox',
      '#title' => t("Improve frontend performance when not signed in"),
      '#default_value' => $persona_config->get('improve_frontend'),
      '#description' => t("For anonymous users, the login.persona.org JavaScript shiv will not be added to pages where there is no sign in button.<br />This means that for browsers that don't support navigator.id natively, automatic sign in cannot occur on these pages."),
    );
    $form['general']['fade_out'] = array(
      '#type' => 'checkbox',
      '#title' => t("Fade out page during sign in and sign out"),
      '#default_value' => $persona_config->get('fade_out'),
      '#description' => t("Slow fade for sign in, fast fade for sign out.<br />Background is left intact."),
    );
    // Button settings.
    $form['buttons'] = array(
      '#type' => 'fieldset',
      '#title' => t("Buttons"),
    );
    $form['buttons']['button_style'] = array(
      '#type' => 'select',
      '#title' => t("Style"),
      '#default_value' => $persona_config->get('button_style'),
      '#options' => array(
        'button' => t("Button"),
        'persona' => t("Persona"),
        'form' => t("Form"),
      ),
      '#ajax' => array(
        'callback' => 'Drupal\persona\Form\persona_admin_form_button_preview_callback',
        'wrapper' => 'button-preview',
        'effect' => 'fade',
        'progress' => array('type' => 'none'),
      ),
    );
    $form['buttons']['style_preview'] = array(
      '#type' => 'item',
      '#title' => t("Preview"),
      'button' => persona_admin_form_button_preview(),
    );
    // Sign In Dialog settings.
    $form['sign_in_dialog'] = array(
      '#type' => 'fieldset',
      '#title' => t("Sign in dialog"),
    );
    $form['sign_in_dialog']['logo'] = array(
      '#type' => 'textfield',
      '#title' => t("Site Logo"),
      '#default_value' => $persona_config->get('logo'),
      '#description' => t("Must be an absolute path, for example <em>/sites/default/files/logo.svg</em> .<br />Defaults to the theme's logo setting.<br />Only appears when the site is delivered over HTTPS."),
    );
    $form['sign_in_dialog']['background_color'] = array(
      '#title' => t("Background Color"),
      '#description' => t("Color to use as the dialog's background."),
    );
    if (module_exists('jquery_colorpicker')) {
      $form['sign_in_dialog']['background_color'] += array(
        '#type' => 'jquery_colorpicker',
        '#default_value' => substr($persona_config->get('background_color'), 1),
      );
    }
    else {
      $form['sign_in_dialog']['background_color'] += array(
        '#type' => 'textfield',
        '#default_value' => $persona_config->get('background_color'),
      );
      $form['sign_in_dialog']['background_color']['#description'] .= '<br />' . t("Format: <em>#rgb</em> or <em>#rrggbb</em>.");
    }
    $form['sign_in_dialog']['terms_link'] = array(
      '#type' => 'textfield',
      '#title' => t("Terms Of Service"),
      '#default_value' => $persona_config->get('terms_link'),
      '#description' => t("Web page link in the form of a local path or an absolute URL.<br />Only appears when Privacy Policy is also provided."),
    );
    $form['sign_in_dialog']['privacy_link'] = array(
      '#type' => 'textfield',
      '#title' => t("Privacy Policy"),
      '#default_value' => $persona_config->get('privacy_link'),
      '#description' => t("Web page link in the form of a local path or an absolute URL.<br />Only appears when Terms Of Service is also provided."),
    );
    $form['#attached']['css'][] = drupal_get_path('module', 'persona') . '/css/persona.theme.css';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $background_color = &$form_state['values']['persona_background_color'];
    if (strlen($background_color) > 0 && $background_color[0] != '#') {
      // Add a # to the start of the color.
      $background_color = '#' . $background_color;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $persona_config = \Drupal::config('persona.settings');
    $settings = array('take_over', 'email_usernames', 'new_account_edit', 'improve_frontend', 'fade_out', 'button_style', 'logo', 'background_color', 'terms_link', 'privacy_link');
    foreach ($settings as $setting) {
      $persona_config->set($setting, $form_state['values'][$setting]);
    }
    $persona_config->save();

    parent::submitForm($form, $form_state);
  }

}

/**
 * Generates preview button for admin form.
 */
function persona_admin_form_button_preview($style = NULL) {
  return array(
    '#theme' => 'persona_button',
    '#type' => 'preview',
    '#style' => $style,
    '#attributes' => array('id' => 'button-preview'),
  );
}

/**
 * AJAX callback to generate preview button for admin form.
 */
function persona_admin_form_button_preview_callback(array $form, array $form_state) {
  return persona_admin_form_button_preview($form_state['values']['button_style']);
}
