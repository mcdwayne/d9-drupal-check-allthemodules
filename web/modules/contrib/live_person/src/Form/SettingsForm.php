<?php

/**
 * @file
 * Contains \Drupal\live_person\Form\SettingsForm.
 */

namespace Drupal\live_person\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures liveperson settings.
 */
class SettingsForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'live_person_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'live_person.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $lp_config = $this->config('live_person.settings');

    $registration_url = 'https://register.liveperson.com/drupal?utm_source=drupal&utm_medium=partnerships&utm_campaign=module';
    $form['account']['account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LivePerson account number'),
      '#description' => $lp_config->get('account') == '' ? $this->t("Don't have an account yet? <a href=\":url\" target=\"_blank\">Register here</a> to get started with a special promotion for Drupal users.", [':url' => $registration_url]) : '',
      '#default_value' => $lp_config->get('account'),
      '#size' => 15,
      '#maxlength' => 20,
      '#required' => TRUE,
    ];

    // Enable/disable switch.
    $enabled = $lp_config->get('enabled');
    if (is_null($enabled)) {
      $enabled = TRUE;
    }
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('When disabled the LiveChat will be hidden regardless of all other visibility settings.'),
      '#default_value' => $enabled,
    ];

    // Allow users to restrict LiveChat by page path.
    $form['page_visibility'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Page based LiveChat visibility'),
    ];
    $form['page_visibility']['visibility'] = [
      '#type' => 'radios',
      '#title' => $this->t('Restrict the LiveChat widget to specific pages'),
      '#options' => [
        0 => t('Add LiveChat to every page except the pages listed below.'),
        1 => t('Add LiveChat to only the pages listed below.'),
      ],
      '#default_value' => $lp_config->get('visibility'),
    ];
    $form['page_visibility']['pages'] = [
      '#type' => 'textarea',
      '#title' => t('Pages'),
      '#default_value' => $lp_config->get('pages'),
      '#description' => $this->t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", ['%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>']),
      '#wysiwyg' => FALSE,
    ];

    $form['role_visibility'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Role based LiveChat visibility'),
    ];
    $form['role_visibility']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Do not show the LiveChat widget to users with any of these roles'),
      '#default_value' => $lp_config->get('roles'),
      '#options' => user_role_names(),
      '#description' => $this->t('If no roles are selected, all users will see the LiveChat widget.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('live_person.settings')
      ->set('account', $values['account'])
      ->set('enabled', $values['enabled'])
      ->set('visibility', $values['visibility'])
      ->set('pages', $values['pages'])
      ->set('roles', $values['roles'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
