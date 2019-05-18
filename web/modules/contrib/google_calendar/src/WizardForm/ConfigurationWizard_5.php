<?php

namespace Drupal\google_calendar\WizardForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ConfigurationWizard_5 extends ConfigurationWizardBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'google_calendar_configuration_wizard_5';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['intro'] = array(
      '#type' => 'details',
      '#title' => $this->t('Step 5: Share calendar(s) with the service account'),
      '#open' => TRUE,
    );

    $account_id = $this->store->get('service_account') ?: t('- not supplied -');

    $output = '<p>' . t('To be usable, the desired calendar or calendars must be shared with the service account. More than one user\'s calendar can be shared, if this is desired.') . '</p>';
    $output .= '<ol>';
    $output .= '<li><p>' . t('In a new private window, sign in to the Google Account that owns the calendar(s). This can be, but does not have to be, the Google Account created for the website.') . '<p></li>';
    $output .= '<li><p>' . t('Navigate to the account Calendar display.') . '<p></li>';
    $output .= '<li><p>' . t('On the left side there is a list "My calendars". Hover over the name of the on the calendar to share, click on the menu icon at the side, and select "Settings and sharing".') . '<p></li>';
    $output .= '<li><p>' . t('Locate the "Share with specific people" in the settings for this calendar.') . '<p></li>';
    $output .= '<li><p>' . t('Click on Add People, and enter the Service Account ID: @account_id', ['@account_id' => $account_id]) . '<p></li>';
    $output .= '<li><p>' . t('<strong>Repeat</strong> the above for all calendars you wish to share.') . '<p></li>';
    $output .= '</ol>';

    $form['intro']['para'] = [
      '#type' => 'markup',
      '#markup' => $output,
    ];

    $form['step_5'] = [
      '#type' => 'checkbox',
      '#required' => TRUE,
      '#title' => $this->t('Share Calendars with service account'),
      '#default_value' => $this->store->get('google_account') ?: '',
      '#description' => $this->t('Check this when you have Shared some calendars with the service account.')
    ];

    $form['actions']['previous'] = array(
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => array(
        'class' => array('button'),
      ),
      '#weight' => 0,
      '#url' => Url::fromRoute('google_calendar.config_wizard_four'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('step_5', $form_state->getValue('step_5'));

    $form_state->setRedirect('google_calendar.config_wizard_six');
  }
}