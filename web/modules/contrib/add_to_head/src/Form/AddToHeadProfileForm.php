<?php

namespace Drupal\add_to_head\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AddToHeadProfileForm extends FormBase {

  public function getFormId() {
    return 'add_to_head_profile_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, array $profile = array()) {
    // Fill in profile defaults to ensure all keys exist.
    $profile += [
      'name' => '',
      'code' => '',
      'paths' => ['visibility' => 0, 'paths' => ''],
      'scope' => '',
      'roles' => [
        'visibility' => 0,
        'list' => [],
      ],
    ];
    $profile['roles']['visibility'] = empty($profile['roles']['visibility']) ? 0 : $profile['roles']['visibility'];
    $profile['roles']['list'] = is_array($profile['roles']['list']) ? $profile['roles']['list'] : array();

    $form['name_orig'] = array(
      '#type' => 'value',
      '#value' => $profile['name'],
    );

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('This is the unique name for this profile'),
      '#required' => TRUE,
      '#default_value' => $profile['name'],
    );

    $form['code'] = array(
      '#type' => 'textarea',
      '#title' => t('Code'),
      '#description' => t('Enter the code you would like to insert into the head of the page'),
      '#required' => TRUE,
      '#default_value' => $profile['code'],
      '#wysiwyg' => FALSE,
    );

    $form['paths'] = array(
      '#type' => 'fieldset',
      '#title' => t('Paths'),
      '#tree' => TRUE,
    );

    $form['paths']['visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Embed code on specific pages'),
      '#options' => array(
        'exclude' => t('Show on every page except the listed pages.'),
        'include' => t('Show on only the listed pages.'),
      ),
      '#default_value' => $profile['paths']['visibility'],
    );

    $form['paths']['paths'] = array(
      '#type' => 'textarea',
      '#title' => t('Paths'),
      '#description' => t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>')),
      '#default_value' => $profile['paths']['paths'],
      '#wysiwyg' => FALSE,
    );

    // Render the Roles overview.
    $form['roles'] = array(
      '#type' => 'fieldset',
      '#title' => t('Roles'),
      '#tree' => TRUE,
    );

    $form['roles']['visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Embed code for specific roles'),
      '#options' => array(
        'include' => t('Add for the selected roles only'),
        'exclude' => t('Add for every role except the selected ones'),
      ),
      '#default_value' => $profile['roles']['visibility'],
    );

    $form['roles']['list'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Selected roles'),
      '#default_value' => $profile['roles']['list'],
      '#options' => user_role_names(),
      '#description' => $this->t('If none of the roles are selected, all roles will have the code displayed. If a user has any of the roles checked, that user will be have the code displayed (or not, depending on the setting above).'),
    );

    $form['scope'] = array(
      '#type' => 'radios',
      '#title' => t('Scope of addition'),
      '#description' => t('Which section of the head would you like this snippet appended to?'),
      '#options' => array(
        'head' => t('Head - This appears early on in the head (before any CSS and JS are included)'),
        'styles' => t('Styles - It will be appended to the CSS files section. This is usually before any other JS is included.'),
        'scripts' => t('Scripts - It will be appended to the Javascripts section. This can, sometimes, be in the footer of the document depending on the theme.'),
      ),
      '#default_value' => $profile['scope'],
    );


    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $name = $form_state->getValue('name');
    if (preg_match('/[^a-z0-9\-]/', $name)) {
      $form_state->setErrorByName('name', t('The name should only contain lower case letters, numbers and hyphens.'));
      return;
    }

    if ( ($name != $form_state->getValue('orig_name')) && isset($settings[$name]) ) {
      $form_state->setErrorByName('name', t('This name has already been used. Please try another.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = \Drupal::config('add_to_head.settings')->get('add_to_head_profiles');

    if ($form_state->getValue('name') != $form_state->getValue('name_orig')) {
      unset($settings[$form_state->getValue('name_orig')]);
    }

    $settings[$form_state->getValue('name')] = array(
      'name'  => $form_state->getValue('name'),
      'code'  => trim($form_state->getValue('code')),
      'scope' => $form_state->getValue('scope'),
      'paths' => [
        'visibility' => $form_state->getValue('paths')['visibility'],
        'paths' => trim($form_state->getValue('paths')['paths']),
      ],
      'roles' => [
        'visibility' => $form_state->getValue('roles')['visibility'],
        'list' => array_filter($form_state->getValue('roles')['list']),
      ],
    );

    add_to_head_set_settings($settings);

    $form_state->setRedirect('add_to_head.admin');
  }
}

