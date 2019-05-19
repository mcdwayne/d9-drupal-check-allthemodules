<?php

/**
 * @file
 * Contains \Drupal\usersnap\Form\UsersnapSettingsForm.
 */

namespace Drupal\usersnap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class UsersnapSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usersnap_admin_configuration';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   * An array of configuration object names that are editable if called in
   * conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['usersnap.admin_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $admin_configurations = $this->config('usersnap.admin_settings');
    $form['usersnap_apikey'] = array(
      '#type' => 'textfield',
      '#title' => t('Enter the Usersnap API Key'),
      '#default_value' => $admin_configurations->get('usersnap_apikey') ? $admin_configurations->get('usersnap_apikey') : '',
      '#size' => 60,
      '#maxlength' => 60,
      '#description' => t("Enter the Usersnap API Key. You can get a Key at http://www.usersnap.com."),
      '#required' => TRUE,
    );
    $form['usersnap_button_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Widget button text'),
      '#description' => t('Button text displayed on the Usersnap feedback widget'),
      '#default_value' => $admin_configurations->get('usersnap_button_text') ? $admin_configurations->get('usersnap_button_text') : 'Feedback',
      '#required' => TRUE,
    );
    $form['usersnap_visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Show widget on specific pages'),
      '#default_value' => $admin_configurations->get('usersnap_visibility') ? $admin_configurations->get('usersnap_visibility') : USERSNAP_VISIBILITY_NOTLISTED,
      '#options' => array(
        USERSNAP_VISIBILITY_NOTLISTED => t('All pages except those listed'),
        USERSNAP_VISIBILITY_LISTED => t('Only the listed pages'),
      ),
    );
    $form['usersnap_paths'] = array(
      '#type' => 'textarea',
      '#title' => '',
      '#description' => t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array(
        '%blog' => 'blog',
        '%blog-wildcard' => 'blog/*',
        '%front' => '<front>')
      ),
      '#default_value' => $admin_configurations->get('usersnap_paths') ? $admin_configurations->get('usersnap_paths') : '',
    );
    $form['usersnap_position'] = array(
      '#type' => 'select',
      '#title' => t('Widget position'),
      '#description' => t('The feedback widget will be placed in this part of the screen.'),
      '#default_value' => $admin_configurations->get('usersnap_position') ? $admin_configurations->get('usersnap_position') : 'bottom_right',
      '#options' => array(
        'bottom_right' => t('Bottom right corner'),
        'bottom_left' => t('Bottom left corner'),
        'middle_right' => t('Middle right'),
        'middle_left' => t('Middle left'),
      ),
    );
    $form['usersnap_language'] = array(
      '#type' => 'select',
      '#title' => t('Widget language'),
      '#description' => t('Preferred interface language for feedback widget.'),
      '#default_value' => $admin_configurations->get('usersnap_apikey') ? $admin_configurations->get('usersnap_apikey') : 'en',
      '#options' => usersnap_supported_languages(),
    );
    $form['usersnap_show_email_field'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show e-mail field'),
      '#default_value' => $admin_configurations->get('usersnap_show_email_field') ? $admin_configurations->get('usersnap_show_email_field') : FALSE,
    );
    $form['usersnap_email_field_placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('E-mail field placeholder text'),
      '#description' => t('Placeholder text shown to users in the e-mail field'),
      '#default_value' => $admin_configurations->get('usersnap_email_field_placeholder') ? $admin_configurations->get('usersnap_email_field_placeholder') : '',
      '#states' => array(
        'visible' => array(
          ':input[name="usersnap_show_email_field"]' => array(
            'checked' => TRUE,
          ),
        ),
      )
    );
    $form['usersnap_email_field_required'] = array(
      '#type' => 'checkbox',
      '#title' => t('Require e-mail'),
      '#default_value' => $admin_configurations->get('usersnap_email_field_required') ? $admin_configurations->get('usersnap_email_field_required') : '',
      '#states' => array(
        'visible' => array(
          ':input[name="usersnap_show_email_field"]' => array(
            'checked' => TRUE,
          ),
        ),
      )
    );
    $form['usersnap_show_comment_box'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show comment box'),
      '#default_value' => $admin_configurations->get('usersnap_show_comment_box') ? $admin_configurations->get('usersnap_show_comment_box') : FALSE,
    );
    $form['usersnap_comment_box_placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Comment box placeholder text'),
      '#description' => t('Text shown to users in the comment box'),
      '#default_value' => $admin_configurations->get('usersnap_comment_box_placeholder') ? $admin_configurations->get('usersnap_comment_box_placeholder') : '',
      '#states' => array(
        'visible' => array(
          ':input[name="usersnap_show_comment_box"]' => array(
            'checked' => TRUE,
          ),
        ),
      )
    );
    $form['usersnap_comment_box_required'] = array(
      '#type' => 'checkbox',
      '#title' => t('Require comment'),
      '#default_value' => $admin_configurations->get('usersnap_comment_box_required') ? $admin_configurations->get('usersnap_comment_box_required') : '',
      '#states' => array(
        'visible' => array(
          ':input[name="usersnap_show_comment_box"]' => array(
            'checked' => TRUE,
          ),
        ),
      )
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_values = $form_state->getValues();
    $config_fields = array(
      'usersnap_apikey',
      'usersnap_button_text',
      'usersnap_visibility',
      'usersnap_paths',
      'usersnap_position',
      'usersnap_language',
      'usersnap_show_email_field',
      'usersnap_email_field_placeholder',
      'usersnap_email_field_required',
      'usersnap_show_comment_box',
      'usersnap_comment_box_placeholder',
      'usersnap_comment_box_required',
    );
    $usersnap_config = $this->config('usersnap.admin_settings');
    foreach ($config_fields as $config_field) {
      $usersnap_config->set($config_field, $config_values[$config_field])
          ->save();
    }
    parent::submitForm($form, $form_state);
  }

}
