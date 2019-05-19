<?php

namespace Drupal\webform_mass_email\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send mass email to address stored in webform field.
 */
class WebformResultsMassEmailForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_results_mass_email_form';
  }

  /**
   * Constructs a new WebformResultsMassEmailForm instance.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(Connection $database, QueueFactory $queue_factory, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->queueFactory = $queue_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('queue'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL) {
    // Get all email elements for the select list.
    $email_elements = [];
    foreach ($webform->getElementsDecodedAndFlattened() as $id => $element) {
      if ($element['#type'] === 'email') {
        $email_elements[$id] = $element['#title'];
      }
    }

    if (empty($email_elements)) {
      drupal_set_message($this->t('There are not any email elements.'), 'warning');
      $error = TRUE;
    }
    if (!$webform->hasSubmissions()) {
      drupal_set_message($this->t('There are not any submissions yet.'), 'warning');
      $error = TRUE;
    }
    if (!empty($error)) {
      return $form;
    }

    $config = $this->config('webform_mass_email.settings');
    $html = $config->get('html');

    $form['webform_mass_email'] = [
      '#type' => 'details',
      '#title' => $this->t('Webform Mass Email'),
      '#open' => TRUE,
    ];
    $form['webform_mass_email']['webform'] = [
      '#type' => 'value',
      '#value' => [
        'id' => $webform->id(),
        'title' => $webform->get('title'),
      ],
    ];
    $form['webform_mass_email']['element'] = [
      '#type' => 'select',
      '#title' => $this->t('Email field'),
      '#description' => $this->t('Select the email field to be used as the recipient address.'),
      '#default_value' => key($email_elements),
      '#options' => $email_elements,
      '#required' => TRUE,
    ];
    $form['webform_mass_email']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t('Enter the email subject.'),
      '#required' => TRUE,
    ];
    $form['webform_mass_email']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#description' => $this->t('Enter the email body text. Webform submission tokens are supported.'),
      '#required' => TRUE,
    ];
    if ($html) {
      // Switch textarea to formatted text with editor.
      $form['webform_mass_email']['body']['#type'] = 'text_format';
      $description = $this->t('You can use HTML tags in your message.');
      $form['webform_mass_email']['body']['#description'] .= ' ' . $description;
    }
    // Only if token module is enabled.
    if ($this->moduleHandler->moduleExists('token')) {
      $form['webform_mass_email']['body'] += [
        '#element_validate' => ['token_element_validate'],
        '#token_types' => ['webform_submission'],
      ];
      // Add the token tree UI.
      $form['webform_mass_email']['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['webform_submission'],
        '#show_restricted' => TRUE,
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send emails'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $webform = $form_state->getValue('webform');
    $element = $form_state->getValue('element');
    $subject = $form_state->getValue('subject');
    $body = $form_state->getValue('body');
    if (is_array($body) && isset($body['value'])) {
      $body = $body['value'];
    }

    // Get all emails keyed by submissions ID.
    $query = $this->database->select('webform_submission_data', 's');
    $query->fields('s', ['sid', 'value']);
    $query->condition('s.webform_id', $webform['id']);
    $query->condition('s.name', $element);
    $submissions = $query->execute()->fetchAllKeyed();

    // Prepare the values that we're going to put into the queue.
    $queue_values = [
      'webform_id' => $webform['id'],
      'webform_title' => $webform['title'],
      'subject' => $subject,
      'body' => $body,
    ];

    $count = 0;
    // Store all emails here for to prevent any duplicates.
    $emails = [];

    // Loop through the submissions, pick up submission ID + email and
    // enqueue the request for the cron to fetch.
    foreach ($submissions as $id => $email) {

      // There's email for this submission and it's not already queued.
      if (!empty($email) && !in_array($email, $emails)) {
        // Set queue values.
        $queue_values['id'] = $id;
        $queue_values['email'] = $email;

        // Queue the values into the 'queue' table.
        $queue = $this->queueFactory->get('webform_mass_email');
        $queue->createItem($queue_values);

        $count++;
        // Store the email.
        $emails[] = $email;
      }
    }

    // Set message with the count.
    drupal_set_message($this->t('%count items queued for sending.', [
      '%count' => $count,
    ]));
  }

}
