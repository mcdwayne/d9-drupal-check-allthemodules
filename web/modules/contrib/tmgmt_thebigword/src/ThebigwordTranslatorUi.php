<?php

namespace Drupal\tmgmt_thebigword;

use Drupal\Component\Utility\NestedArray;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\JobInterface;

/**
 * Thebigword translator UI.
 */
class ThebigwordTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    $settings = NestedArray::mergeDeep($translator->getPlugin()->defaultSettings(), $translator->getSettings());

    $form['service_url'] = [
      '#type' => 'textfield',
      '#title' => t('thebigword Web API endpoint'),
      '#default_value' => $translator->getSetting('service_url'),
      '#description' => t('Please enter the web API endpoint.'),
      '#required' => TRUE,
      '#placeholder' => 'https://example.thebigword.com/example/cms/api/1.0',
    ];
    $form['client_contact_key'] = [
      '#type' => 'textfield',
      '#title' => t('thebigword client contact key'),
      '#default_value' => $translator->getSetting('client_contact_key'),
      '#description' => t('Please enter your client contact key.'),
      '#required' => TRUE,
      '#placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ];
    $form += parent::addConnectButton();

    $form['review_tool'] = [
      '#type' => 'details',
      '#title' => t('thebigword Review Tool Configuration'),
      '#description' => t('These settings define how thebigword’s Review Tool is used within the translation workflow at thebigword. The provider instance can pre-set the option for the Translation Job checkout settings stage to selected or deselected. This setting can also be made read-only for the Translation Job checkout user. Additionally, Drupal users with appropriate role permission settings can be given access to Review Tool tasks for translation jobs created using this provider configuration.'),
    ];

    $form['review_tool']['checkout'] = [
      '#type' => 'fieldset',
      '#title' => t('Translation Job Checkout Settings for Review Tool'),
    ];
    $form['review_tool']['checkout']['default'] = [
      '#type' => 'checkbox',
      '#title' => t('Pre-select the Review Tool checkout option'),
      '#description' => t('This option can pre-enable the "Review with thebigword Review Tool" option in the Translation Job > Checkout Settings screen.'),
      '#default_value' => !empty($settings['review_tool']['checkout']['default']),
    ];
    $form['review_tool']['checkout']['disable'] = [
      '#type' => 'checkbox',
      '#title' => t('Disable user change of the Review Tool checkout option'),
      '#description' => t('This option can remove the checkout user\'s ability to change the pre-set Review Tool setting defined above. i.e. provider configurations can be created where the Review Tool usage is mandatory or excluded.'),
      '#default_value' => !empty($settings['review_tool']['checkout']['disable']),
    ];

    $form['review_tool']['access'] = [
      '#type' => 'fieldset',
      '#title' => t('thebigword Review Tool Access for Authorised Drupal Users'),
    ];
    $form['review_tool']['access']['primary'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable access to Primary review phase Review Tool tasks'),
      '#description' => t('This option enables the Primary review phase Review Tool tasks to be displayed to authorised Drupal users with permission to perform Primary Review tasks in their role’s permissions.'),
      '#default_value' => !empty($settings['review_tool']['access']['primary']),
    ];
    $form['review_tool']['access']['secondary'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable access to Secondary review phase Review Tool tasks'),
      '#description' => t('This option enables the Secondary review phase Review Tool tasks to be displayed to authorised Drupal users with permission to perform Secondary Review tasks in their role’s permissions.'),
      '#default_value' => !empty($settings['review_tool']['access']['secondary']),
    ];

    $form['user_information_control'] = [
      '#type' => 'details',
      '#title' => t('User Information Control: Transfer of user details to thebigword'),
      '#description' => t('These settings control the transfer of user information to thebigword when translation jobs are submitted or Review Tool tasks are accessed from the Drupal system. This information is used by thebigword for job management and access history purposes. Selecting an option will direct the system to send the Drupal user’s name and email address. When an option is deselected the user name will be replaced by their Drupal ID and no email information is sent.'),
    ];
    $form['user_information_control']['create'] = [
      '#type' => 'checkbox',
      '#title' => t('Send User Name and Email address when translation projects are created at thebigword'),
      '#default_value' => !empty($settings['user_information_control']['create']),
    ];
    $form['user_information_control']['review'] = [
      '#type' => 'checkbox',
      '#title' => t('Send User Name and Email address when a user accesses a Review Tool task via the Drupal system'),
      '#default_value' => !empty($settings['user_information_control']['review']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if ($form_state->hasAnyErrors()) {
      return;
    }
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\tmgmt_thebigword\Plugin\tmgmt\Translator\ThebigwordTranslator $plugin */
    $plugin = $translator->getPlugin();
    $plugin->setTranslator($translator);
    $result = $plugin->request('states', 'GET', [], FALSE, TRUE);
    if ($result == 401) {
      $form_state->setErrorByName('settings][client_contact_key', t('The client contact key is not correct.'));
    }
    elseif ($result != 200) {
      $form_state->setErrorByName('settings][service_url', t('The Web API endpoint is not correct.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    /** @var \Drupal\tmgmt_thebigword\Plugin\tmgmt\Translator\ThebigwordTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->setTranslator($job->getTranslator());
    $settings['required_by'] = [
      '#type' => 'number',
      '#title' => t('Required By (Workdays days)'),
      '#description' => t('Enter the number of working days before the translation is required.'),
      '#default_value' => $job->getSetting('required_by') ? $job->getSetting('required_by') : 5,
      '#min' => 1,
    ];
    $settings['quote_required'] = [
      '#type' => 'checkbox',
      '#title' => t('Quotation required before translation.'),
      '#description' => t('If this is selected a quote will be provided for acceptance before translation work begins.'),
      '#default_value' => $job->getSetting('quote_required') ? $job->getSetting('quote_required') : FALSE,
    ];
    $settings['category'] = [
      '#type' => 'select',
      '#title' => t('Category'),
      '#description' => t('Select the content category type. This is used to help select linguists with appropriate subject matter knowledge. Translation of specialist content (other than Generic/Universal) can affect the overall translation costs.'),
      '#options' => $translator_plugin->getCategory($job),
      '#default_value' => $job->getSetting('category'),
    ];

    $settings['po_number'] = [
      '#type' => 'textfield',
      '#title' => t('Purchase Order Number'),
      '#description' => t('You can provide a Purchase Order Number for this job at thebigword, or leave it empty if a PO Number is not required.'),
      '#default_value' => $job->getSetting('po_number'),
      '#maxlength' => 50,
    ];

    $settings['project_reference'] = [
      '#type' => 'textfield',
      '#title' => t('Project reference'),
      '#description' => t('You can provide a Project Reference for this job to help thebigword identify it easily later on, or leave it empty to use the default one'),
      '#default_value' => $job->getSetting('project_reference'),
      '#maxlength' => 100,
    ];

    $translator_settings = NestedArray::mergeDeep($job->getTranslatorPlugin()->defaultSettings(), $job->getTranslator()->getSettings());
    $settings['review'] = [
      '#type' => 'checkbox',
      '#title' => t('Review with thebigword Review Tool'),
      '#description' => t('Indicate that the project is to be reviewed by nominated reviewers* before return to Drupal.<br><br>*Nominated Reviewers are defined by prior discussion with thebigword.'),
      '#default_value' => $job->getSetting('review') !== NULL ? $job->getSetting('review') : !empty($translator_settings['review_tool']['checkout']['default']),
      '#disabled' => !empty($translator_settings['review_tool']['checkout']['disable']),
    ];

    if (!empty($translator_settings['review_tool']['checkout']['disable'])) {
      $settings['review']['#disabled'] = TRUE;
      $settings['review']['#description'] = t('The Review Tool setting is locked for this provider configuration.');
    }


    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    $form = [];

    if ($job->isActive()) {
      $form['actions']['pull'] = [
        '#type' => 'submit',
        '#value' => t('Pull translations'),
        '#submit' => [[$this, 'submitPullTranslations']],
        '#weight' => -10,
      ];
    }

    return $form;
  }

  /**
   * Submit callback to pull translations form thebigword.
   */
  public function submitPullTranslations(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = $form_state->getFormObject()->getEntity();

    // Remove the destination key so that the redirect works as expected.
    if (\Drupal::request()->query->has('destination')) {
      \Drupal::request()->query->remove('destination');
    }
    $form_state->setRedirect('tmgmt_thebigword.pull_all_remote_translations', [], ['query' => ['job_id' => $job->id()] + \Drupal::destination()->getAsArray()]);
  }

}
