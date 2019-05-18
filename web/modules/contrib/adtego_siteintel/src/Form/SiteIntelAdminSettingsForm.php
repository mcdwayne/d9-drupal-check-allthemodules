<?php

namespace Drupal\adtego_siteintel\Form;

use Drupal\node\Entity\NodeType;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure siteintel settings for this site.
 */
class SiteIntelAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'siteintel_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adtego_siteintel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('adtego_siteintel.settings');
    // Build form + add defaults from config.
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['account'] = [
      '#default_value' => $config->get('account'),
      '#required' => TRUE,
      '#title' => $this->t('SiteIntel Account Key'),
      '#type' => 'textfield',
    ];

    $form['general']['siteid'] = [
      '#default_value' => $config->get('siteid'),
      '#required' => TRUE,
      '#title' => $this->t('Site ID'),
      '#type' => 'textfield',
    ];

    // Visibility settings.
    $form['integration_scope'] = array(
      '#type' => 'vertical_tabs',
      '#title' => t('SiteIntel scope'),
    );

    $form['scope']['content_types'] = array(
      '#type' => 'details',
      '#title' => 'Content Types',
      '#group' => 'integration_scope',
    );

    $content_types = NodeType::loadMultiple();

    $configured_content_types = $config->get('scope.content_types');

    foreach ($content_types as $type => $value) {
      $form['scope']['content_types']['siteintel_type_' . $type] = array(
        '#type' => 'checkbox',
        '#title' => $value->label(),
        '#default_value' => !empty($configured_content_types[$type]) ? 1 : 0,
      );
      $form['scope']['content_types']['siteintel_ads_type_' . $type] = array(
        '#type' => 'select',
        '#title' => 'Number of Ads',
        '#options' => array(
          1 => 1,
          2 => 2,
          3 => 3,
          4 => 4,
          5 => 5,
          6 => 6,
          7 => 7,
          8 => 8,
          9 => 9,
        ),
        '#default_value' => !empty($configured_content_types[$type]['number_of_ads']) ? $configured_content_types[$type]['number_of_ads'] : 1,
        '#states' => array(
          'visible' => array(
            ':input[name="siteintel_type_' . $type . '"]' => array('checked' => TRUE),
          ),
        ),
      );
    }

    // Render the role overview.
    $scope_user_role_roles = $config->get('scope.user_role_roles');

    $form['scope']['role_scope_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#group' => 'integration_scope',
    ];

    $form['scope']['role_scope_settings']['siteintel_scope_user_role_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add tracking for specific roles'),
      '#options' => [
        t('Add to the selected roles only'),
        t('Add to every role except the selected ones'),
      ],
      '#default_value' => $config->get('scope.user_role_mode'),
    ];
    $form['scope']['role_scope_settings']['siteintel_scope_user_role_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => !empty($scope_user_role_roles) ? $scope_user_role_roles : [],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#description' => $this->t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    ];

    // Standard tracking configurations.
    $scope_user_account_mode = $config->get('scope.user_account_mode');

    $form['scope']['user_scope_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Users'),
      '#group' => 'integration_scope',
    ];
    $t_permission = ['%permission' => $this->t('opt-in or out of siteintel tracking')];
    $form['scope']['user_scope_settings']['siteintel_scope_user_account_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow users to customize tracking on their account page'),
      '#options' => [
        t('No customization allowed'),
        t('Tracking on by default, users with %permission permission can opt out', $t_permission),
        t('Tracking off by default, users with %permission permission can opt in', $t_permission),
      ],
      '#default_value' => !empty($scope_user_account_mode) ? $scope_user_account_mode : 0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Trim some text values.
    $form_state->setValue('account', trim($form_state->getValue('account')));
    $form_state->setValue('siteid', trim($form_state->getValue('siteid')));
    $form_state->setValue('siteintel_scope_user_role_roles', array_filter($form_state->getValue('siteintel_scope_user_role_roles')));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('adtego_siteintel.settings');
    // config->set everything below.
    $config
      ->set('account', $form_state->getValue('account'))
      ->set('siteid', $form_state->getValue('siteid'))
      ->set('scope.user_role_mode', $form_state->getValue('siteintel_scope_user_role_mode'))
      ->set('scope.user_role_roles', $form_state->getValue('siteintel_scope_user_role_roles'))
      ->set('scope.user_account_mode', $form_state->getValue('siteintel_scope_user_account_mode'));

    $content_types = NodeType::loadMultiple();
    $selected_types = array();

    $config->clear('scope.content_types');

    foreach ($content_types as $type => $value) {
      if ($form_state->getValue('siteintel_type_' . $type) !== 0) {
        $selected_types[$type] = array('content_type' => $type, 'number_of_ads' => $form_state->getValue('siteintel_ads_type_' . $type));
      }
    }

    if (!empty($selected_types)) {
      $config->set('scope.content_types', $selected_types);
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
