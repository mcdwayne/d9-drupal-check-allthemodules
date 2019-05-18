<?php

namespace Drupal\google_optimize\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements Google Optimize Admin Settings form.
 */
class GoogleOptimizeAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_analytics_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_optimize.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_analytics.settings');
    if (!$config->get('account') ?: '') {
      drupal_set_message($this->t('Unable to get the Google Analytics account. Is Google Analytics installed and configured with your Google Analytics account ID?'), 'error');
    }

    $form['containers'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Optimize'),
    ];

    $form['containers']['container_ids'] = [
      '#title' => $this->t('Container Ids (GTM-XXXXXX), separated by commas'),
      '#type' => 'textfield',
      '#default_value' => implode(',', google_optimize_container_ids()),
      '#description' => $this->t('A list of Optimize container IDs (separated by commas if more than one)'),
      '#maxlength' => 500,
    ];

    $form['hide_page'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Optimize page-hiding snippet'),
    ];

    $form['hide_page']['enabled'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled Google Optimize page-hiding snippet'),
    ];

    $form['hide_page']['enabled']['hide_page_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => google_optimize_hide_page_enabled(),
      '#description' => $this->t('Enable/disable the Google Optimize page-hiding snippet.'),
    ];

    $form['hide_page']['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings for the Google Optimize page-hiding snippet'),
      '#states' => [
        'visible' => [
          ':input[name="hide_page_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['hide_page']['settings']['hide_page_timeout'] = [
      '#title' => $this->t('Timeout'),
      '#type' => 'textfield',
      '#default_value' => google_optimize_hide_page_timeout(),
      '#description' => $this->t('The default amount of time Optimize will wait before removing the .async-hide class from the html element'),
    ];

    $form['hide_page']['settings']['hide_page_class_name'] = [
      '#title' => $this->t('Class name'),
      '#type' => 'textfield',
      '#default_value' => google_optimize_hide_page_class_name(),
      '#description' => $this->t('If the async-hide class name is already defined in your CSS, you can choose a different name.'),
    ];

    $form['hide_page']['settings']['hide_page_pages'] = [
      '#title' => $this->t('Pages to add the snippet (leave blank for all pages)'),
      '#type' => 'textarea',
      '#default_value' => google_optimize_hide_page_pages(),
      '#description' => $this->t('Specify pages by using their paths. Enter one path per line. The \'*\' character is a wildcard. An example path is /user/* for every user page or /node/123. &lt;front&gt; is the front page.'),
    ];

    $form['hide_page']['settings']['hide_page_roles'] = array(
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Add the snippet for specific roles'),
      '#default_value' => google_optimize_hide_page_roles(),
      '#options'       => user_role_names(),
      '#description'   => $this->t('Add the snippet only for the selected role(s). If none of the roles are selected, all users will have the snippet.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('google_optimize.settings')
      ->set('hide_page_enable', $form_state->getValue('hide_page_enable'))
      ->set('container_ids', $form_state->getValue('container_ids'))
      ->set('hide_page_timeout', $form_state->getValue('hide_page_timeout'))
      ->set('hide_page_class_name', $form_state->getValue('hide_page_class_name'))
      ->set('hide_page_pages', $form_state->getValue('hide_page_pages'))
      ->set('hide_page_roles', $form_state->getValue('hide_page_roles'))
      ->save();

    // Add the container id(s) to Google Analytics.
    $config = $this->configFactory->getEditable('google_analytics.settings');
    $codesnippet = $config->get('codesnippet');
    $codesnippet['before'] = "ga('require', '" . $form_state->getValue('container_ids') . "');";
    $config->set('codesnippet', $codesnippet)->save();

    parent::submitForm($form, $form_state);
  }

}
