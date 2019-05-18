<?php

namespace Drupal\google_calendar\WizardForm;

use Drupal\Core\Form\FormStateInterface;

class ConfigurationWizard_1 extends ConfigurationWizardBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'google_calendar_configuration_wizard_1';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['head'] = array(
      '#type' => 'markup',
      '#markup' => '<h2>' . t('Google Calendar Integration Wizard') . '</h2>',
    );

    $form['intro'] = array(
      '#type' => 'details',
      '#title' => $this->t('Introduction'),
      '#open' => TRUE,
    );

    $output = '<p>' . t('The module does not (yet) support associating calendars for each Drupal user of a site. Doing so requires storing multiple sets of credentials and interactive OAuth support, which are not currently implemented.') . '</p>';
    $output .= '<p>' . t('Integration of Google services with a website requires the following steps:') . '</p>';
    $output .= '<ol>';
    $output .= '<li><p>' . t('<strong>Create or assign a Google Account</strong> that can be used by the website.') . '</p></li>';
    $output .= '<li><p>' . t('<strong>Create a Google "project" with the account</strong> to represent your website.') . '</p></li>';
    $output .= '<li><p>' . t('<strong>Enable the Calendar Google APIs</strong> for the nominated Google account.') . '</p></li>';
    $output .= '<li><p>' . t('<strong>Create a Google Service Account</strong> to represent the website in sharing calendar details.') . '</p></li>';
    $output .= '<li><p>' . t('<strong>Share the calendars with the service account</strong> for each calendar you wish to use.') . '</p></li>';
    $output .= '<li><p>' . t('<strong>Import the API credentials file</strong> into the website.') . '</p></li>';
    $output .= '<li><p>' . t('<strong>Import the calendar definition</strong> into one or more Drupal Calendars.') . '</p></li>';
    $output .= '<li><p>' . t('<strong>Import the events for each calendar</strong> as Drupal Calendar Events.') . '</p></li>';
    $output .= '<li><p>' . t('<strong>Configure Views or Blocks</strong> to display Calendar Events as required.') . '</p></li>';
    $output .= '</ol>';
    $output .= '<p>' . t('This configuration wizard will guide you through these steps.') . '</p>';

    $form['intro']['outline'] = array(
      '#type' => 'markup',
      '#markup' => $output,
    );

    $form['step'] = array(
      '#type' => 'details',
      '#title' => $this->t('Step 1: Make a Google Account'),
      '#open' => TRUE,
    );

    $output = '<h2>' . t('Step 1: Make a Google Account') . '</h2>';
    $output .= '<ol>';
    $output .= '<li><p>' . t('To create a new account, visit :link, or your country-specific Google site.',[':link' => 'https://www.google.com']) . '</p></li>';
    $output .= '<li><p>If, on this browser, you:</p><ul>';
    $output .= '<li><p>' . t('<b>are not</b> signed-in, click on the a square "Sign-in" button') . '</p></li>';
    $output .= '<li><p>' . t('<b>are</b> signed-in, start a private browsing session. Then click on the account button, and sign out.') . '</p></li>';
    $output .= '</ul></li>';
    $output .= '<li><p>' . t('On the Sign In page, select the "Use another account" option from the dropdown, and then "Create account"') . '</p></li>';
    $output .= '<li><p>' . t('Now complete the account-creation process.') . '</p></li>';
    $output .= '</ol>';

    $form['step']['step1'] = [
      '#type' => 'markup',
      '#markup' => $output,
    ];

    $form['step_1'] = [
      '#type' => 'checkbox',
      '#required' => TRUE,
      '#title' => $this->t('Google Account Email Created'),
      '#default_value' => $this->store->get('step_1') ?: '',
      '#description' => $this->t('Check this when you have a Google account for the website.')
    ];

    $form['actions']['submit']['#value'] = $this->t('Next');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('step_1', $form_state->getValue('step_1'));

    $form_state->setRedirect('google_calendar.config_wizard_two');
  }
}