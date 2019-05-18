<?php

namespace Drupal\google_calendar\WizardForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ConfigurationWizard_4 extends ConfigurationWizardBase {

  public const DEVELOPER_CONSOLE_CREDS = 'https://console.developers.google.com/apis/credentials';

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'google_calendar_configuration_wizard_4';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['intro'] = array(
      '#type' => 'details',
      '#title' => $this->t('Step 4: Create a Service Account for the website'),
      '#open' => TRUE,
    );
    $project_id = $this->store->get('google_project') ? 'project=' . $this->store->get('google_project') : '';

    $output = '<h2>' . t('Step 4: Create a Service Account for the website.') . '</h2>';
    $output .= '<p>' . t('It is necessary to create a Service Account for the website to access the calendar non-interactively. Neither an "API Key" nor an "OAuth client ID" can be used for this purpose: An "API key" is not considered sufficiently strong by Google for Calendar use, and using an "OAuth Client ID" requires human interaction.') . '</p>';
    $output .= '<ol>';
    $output .= '<li><p>' . t('Navigate to the <a href=":link">Credentials page</a>.',
                          [':link' => self::DEVELOPER_CONSOLE_CREDS . '?' . $project_id]) . '<p></li>';
    $output .= '<li><p>' . t('In the Create Credentials dropdown, select "Service account key"') . '<p></li>';
    $output .= '<li><p>' . t('Click on the dropdown "New service account". The page will refresh to show additional fields.') . '<p></li>';
    $output .= '<li><p>' . t('Enter a name for the service account reflecting the website name. This will not normally be seen.') . '<p></li>';
    $output .= '<li><p>' . t('Make a note of the "Service Account ID" in the field below, for cross-checking later.') . '<p></li>';
    $output .= '<li><p>' . t('Click on the Create button (leaving the key type as JSON).') . '<p></li>';
    $output .= '<li><p>' . t('<strong>Store the downloaded JSON file in a safe place</strong> - it cannot be re-downloaded, only replaced.') . '<p></li>';
    $output .= '</ol>';
    $output .= '<p>' . t('The downloaded JSON file contains various properties, including the Service Account ID created earlier, which will be used by the Drupal code to authenticate with Google. A copy of this file must be accessible from PHP.') . '</p>';

    $form['intro']['para'] = [
      '#type' => 'markup',
      '#markup' => $output,
    ];

    $form['service_account'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Service Account ID'),
      '#default_value' => $this->store->get('service_account') ?: '',
      '#description' => $this->t('An ID of the form "test-97@test-project-285622.iam.gserviceaccount.com". The wizard collects the project for clarity in the next steps. This value is not stored thereafter.')
    );

    $form['step_4'] = [
      '#type' => 'checkbox',
      '#required' => TRUE,
      '#title' => $this->t('Create the Service Account for the website'),
      '#default_value' => $this->store->get('step_4') ?: '',
      '#description' => $this->t('Check this when you have enabled the Google Service Account.')
    ];

    $form['actions']['previous'] = array(
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => array(
        'class' => array('button'),
      ),
      '#weight' => 0,
      '#url' => Url::fromRoute('google_calendar.config_wizard_three'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('service_account', $form_state->getValue('service_account'));
    $this->store->set('step_4', $form_state->getValue('step_4'));

    $form_state->setRedirect('google_calendar.config_wizard_five');
  }
}