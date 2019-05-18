<?php

/**
 * @file
 * Contains \Drupal\revenue_sharing_basic\Form\RevenueSharingBasicSettings.
 */

namespace Drupal\revenue_sharing_basic\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class RevenueSharingBasicSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'revenue_sharing_basic_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('revenue_sharing_basic.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['revenue_sharing_basic.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'revenue_sharing_basic', 'help/revenue_sharing_basic.help');

    $config = $this->config('revenue_sharing_basic.settings');

    $form['help'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => t('Help and instructions'),
    ];

    $form['help']['help'] = [
      '#type' => 'markup',
      '#markup' => revenue_sharing_basic_help_text(),
    ];

    $form['required'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => t('Required parameters'),
    ];

    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/revenue_sharing_basic.settings.yml and config/schema/revenue_sharing_basic.schema.yml.
    $form['required']['revenue_sharing_basic_client_id_profile_field'] = [
      '#type' => 'select',
      '#title' => t('Google AdSense client ID profile field'),
      '#default_value' => $config->get('revenue_sharing_basic_client_id_profile_field'),
      '#options' => revenue_sharing_basic_get_profile_fields(),
      '#required' => TRUE,
      '#description' => t('This is the profile field that holds the AdSense Client ID for the site owner as well as (optionally) for site users who participate in revenue sharing. You must enabled the profile module and create a new field for this.'),
    ];

    $form['percentage'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => t('Revenue sharing percentage'),
    ];

    $options = array_combine(range(0, 100, 5), range(0, 100, 5));

    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/revenue_sharing_basic.settings.yml and config/schema/revenue_sharing_basic.schema.yml.
    $form['percentage']['revenue_sharing_basic_percentage_author'] = [
      '#type' => 'select',
      '#title' => t('Percentage of node views going to author'),
      '#default_value' => $config->get('revenue_sharing_basic_percentage_author'),
      '#options' => $options,
    ];

    $form['percentage']['role'] = [
      '#type' => 'fieldset',
      '#title' => t('Percentage of node views going to author with the following roles'),
      '#description' => t('When the author belongs to one or more roles, the percentage of node views using his AdSense Client ID will be the maximum between the author value and the following settings for each role.'),
      '#theme' => 'revenue_sharing_basic_author_percentage_role',
    ];

    $roles = user_roles(TRUE);
    unset($roles[array_search('authenticated user', $roles)]);
    foreach ($roles as $role => $role_desc) {
      // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// $form['percentage']['role']['revenue_sharing_basic_percentage_role_' . $role] = array(
//       '#type' => 'select',
//       '#title' => t($role_desc),
//       '#default_value' => variable_get('revenue_sharing_basic_percentage_role_' . $role, REVENUE_SHARING_BASIC_PERCENTAGE_ROLE_DEFAULT),
//       '#options' => $options,
//     );

    }

    if (\Drupal::moduleHandler()->moduleExists('referral')) {
      // @FIXME
// Could not extract the default value because it is either indeterminate, or
// not scalar. You'll need to provide a default value in
// config/install/revenue_sharing_basic.settings.yml and config/schema/revenue_sharing_basic.schema.yml.
      $form['percentage']['revenue_sharing_basic_percentage_refer'] = [
        '#type' => 'select',
        '#title' => t('Percentage of node views going to user who referred the author'),
        '#default_value' => $config->get('revenue_sharing_basic_percentage_refer'),
        '#options' => $options,
      ];
    }

    $form['content_types'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => t('Content types'),
    ];

    $types = node_type_get_types();
    foreach ($types as $type => $name) {
      // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// $form['content_types']['revenue_sharing_basic_node_type_' . $type] = array(
//       '#type' => 'checkbox',
//       '#title' => $name->name,
//       '#default_value' => variable_get('revenue_sharing_basic_node_type_' . $type, REVENUE_SHARING_BASIC_NODE_TYPE_DEFAULT),
//     );

    }

    $form_state->set(['#redirect'], 'admin/config/services/adsense/publisher');

    return parent::buildForm($form, $form_state);
  }

}
