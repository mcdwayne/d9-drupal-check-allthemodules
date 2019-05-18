<?php

namespace Drupal\google_calendar\WizardForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ConfigurationWizard_2 extends ConfigurationWizardBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'google_calendar_configuration_wizard_2';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $form['intro'] = array(
      '#type' => 'details',
      '#title' => $this->t('Step 2: Create a Google Project ID'),
      '#open' => TRUE,
    );

    $output = '<ol>';
    $output .= '<li><p>' . t('A Google API Project is a container for the permissions and configuration relating to use of the API, it is therefore necessary to create one to gain access to your calendar.') . '<p></li>';
    $output .= '<li><p>' . t('Navigate to the <a href=":link">Google Developer Console</a> .',[':link' => 'https://console.developer.google.com']) . '<p></li>';
    $output .= '<li><p>' . t('At the top of the page, select "API Project" and then ') . '<p></li>';
    $output .= '<li><p>' . t('Projects have a "friendly" Name, used for display purposes and which can be changed, and a Project ID, which cannot be changed.') . '<p></li>';
    $output .= '<li><p>' . t('Complete the project creation process and then optionally fill in the selected Project ID in the field below.') . '<p></li>';
    $output .= '</ol>';

    $form['intro']['para'] = [
      '#type' => 'markup',
      '#markup' => $output,
    ];

    $form['google_project'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Project ID'),
      '#default_value' => $this->store->get('google_project') ?: '',
      '#description' => $this->t('An ID of the form "test-project-285622". The wizard collects the project for clarity in the next steps. It is not stored here.')
    ];

    $form['step_2'] = [
      '#type' => 'checkbox',
      '#required' => TRUE,
      '#title' => $this->t('Google Project Created'),
      '#default_value' => $this->store->get('step_2') ?: '',
      '#description' => $this->t('Check this when you have a Google Project for the website.')
    ];

    $form['actions']['previous'] = [
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => [
        'class' => ['button'],
      ],
      '#weight' => 0,
      '#url' => Url::fromRoute('google_calendar.config_wizard_one'),
    ];
    $form['actions']['submit']['#value'] = $this->t('Next');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('google_account', $form_state->getValue('google_account'));
    $this->store->set('step_2', $form_state->getValue('step_2'));

    $form_state->setRedirect('google_calendar.config_wizard_three');
  }
}