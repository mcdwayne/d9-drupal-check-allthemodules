<?php

namespace Drupal\zendesk_tickets\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Flood\FloodInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\zendesk_tickets\Zendesk\ZendeskAPI;
use Drupal\zendesk_tickets\ZendeskTicketFormTypeInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * The submit form for a Zendesk ticket.
 */
class ZendeskTicketForm extends FormBase {

  /**
   * The config object being edited.
   *
   * @var Config
   */
  protected $config;

  /**
   * Zendesk API object.
   *
   * @var ZendeskAPI
   */
  protected $api;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The active entity type.
   *
   * @var string
   */
  protected $entityTypeId = 'zendesk_ticket_form_type';

  /**
   * The currently logged-in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Form constructor.
   *
   * @param Config $config
   *   The config object being edited.
   * @param ZendeskAPI $api
   *   The Zendesk API handler.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param FloodInterface $flood
   *   The flood service.
   * @param AccountInterface $current_user
   *   The currently logged-in user.
   * @param TranslationInterface $translator
   *   (optional) The string translation service.
   */
  public function __construct(Config $config, ZendeskAPI $api, EntityTypeManagerInterface $entity_type_manager, FloodInterface $flood, AccountInterface $current_user, TranslationInterface $translator = NULL, DateFormatterInterface $date_formatter) {
    $this->config = $config;
    $this->api = $api;
    $this->entityTypeManager = $entity_type_manager;
    $this->flood = $flood;
    $this->currentUser = $current_user;
    $this->stringTranslation = $translator;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->getEditable('zendesk_tickets.settings'),
      $container->get('zendesk_tickets.zendesk_api'),
      $container->get('entity_type.manager'),
      $container->get('flood'),
      $container->get('current_user'),
      $container->get('string_translation'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->entityTypeId . '_submit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ZendeskTicketFormTypeInterface $zendesk_ticket_form_type = NULL) {
    // Store entity into the form state.
    $form_state->set('zendesk_ticket_form_type', $zendesk_ticket_form_type);
    $builder = NULL;
    if (isset($zendesk_ticket_form_type)) {
      $builder = $zendesk_ticket_form_type->ticketFormBuilder();
    }
    elseif ($this->entityTypeId && $this->entityTypeManager->hasHandler($this->entityTypeId, 'zendesk_ticket_form_builder')) {
      $builder = $this->entityTypeManager->getHandler($this->entityTypeId, 'zendesk_ticket_form_builder');
    }

    if (isset($builder)) {
      $ticket_form = $builder->buildForm($zendesk_ticket_form_type);
      if (!empty($ticket_form)) {
        // Set email to current user's email.
        if (isset($ticket_form['request']['anonymous_requester_email']) &&
            $this->currentUser->isAuthenticated() &&
            ($current_email = $this->currentUser->getEmail())) {
          $ticket_form['request']['anonymous_requester_email']['#default_value'] = $current_email;
        }

        // Merge ticket form.
        $form += $ticket_form;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $zendesk_ticket_form_type = $form_state->get('zendesk_ticket_form_type');

    $limit = $this->config('zendesk_tickets.settings')->get('flood_limit');
    $interval = $this->config('zendesk_tickets.settings')->get('flood_interval');

    if (!$this->flood->isAllowed('zendesk_tickets_support_tickets', $limit, $interval)) {
      $form_state->setErrorByName('', $this->t('You cannot send more than %limit messages in @interval. Try again later.', array(
        '%limit' => $limit,
        '@interval' => $this->dateFormatter->formatInterval($interval),
      )));
    }
    // Form type checks.
    if (!isset($zendesk_ticket_form_type) || !$zendesk_ticket_form_type->id()) {
      // Form type lost.
      $form_state->setErrorByName('ticket_form_id', $this->t("Invalid request type selection. Please try again."));
    }

    // Check submit access.
    // If the form type has been disabled or the user has lost access, then
    // deny form submission.
    if (!$zendesk_ticket_form_type->access('submit')) {
      $form_state->setErrorByName('ticket_form_id', $this->t("Invalid request type selection. Please try again."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $zendesk_ticket_form_type = $form_state->get('zendesk_ticket_form_type');

    // Build with a form.
    $submit_attempted = FALSE;
    $submit_success = FALSE;
    if (isset($zendesk_ticket_form_type) &&
        $zendesk_ticket_form_type->canSubmit() &&
        ($ticket_request = $this->createTicketRequest($form, $form_state))) {
      try {
        $submit_attempted = TRUE;
        $new_ticket_response = $this->api->createTicket($ticket_request);
        $submit_success = isset($new_ticket_response->ticket) && isset($new_ticket_response->ticket->id);
      }
      catch (Exception $e) {
        // Failsafe: Any error should have been logged by the API class.
      }
    }

    if ($submit_success) {
      $this->flood->register('zendesk_tickets_support_tickets', $this->config('zendesk_tickets.settings')->get('flood_interval'));
      // Redirect to custom page.
      $redirect_path = $this->config->get('redirect_page');
      if ($redirect_path) {
        $redirect_url = Url::fromUserInput($redirect_path);
      }
      else {
        $redirect_url = Url::fromRoute('zendesk_ticket.submit.completed');
      }

      if ($redirect_url && $redirect_url->isRouted()) {
        $redirect_url->setOption('query', [
          'type' => $zendesk_ticket_form_type->id(),
        ]);
        $form_state->setRedirectUrl($redirect_url);
      }
      else {
        // Fallback to a simple message.
        drupal_set_message($this->t('Your message has been sent.'));
      }
    }
    elseif ($submit_attempted) {
      // Failed.
      drupal_set_message($this->t('Your request could not be submitted.'));
      $form_state->setRebuild();
    }
    else {
      // Edge case - nothing to submit.
      drupal_set_message($this->t('There was nothing to submit. Please review the form and try again.'));
      $form_state->setRebuild();
    }
  }

  /**
   * Creates a ticket request array based on the provided values.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   A request array compatible with the Zendesk API create ticket.
   */
  protected function createTicketRequest(array &$form, FormStateInterface $form_state) {
    $zendesk_ticket_form_type = $form_state->get('zendesk_ticket_form_type');
    $request_values = $form_state->getValue('request');

    // Exit if nothing to build.
    if (empty($zendesk_ticket_form_type) || empty($request_values)) {
      return [];
    }

    // The form type's fields are used to ensure only allowed fields are
    // submitted.
    $form_object = $zendesk_ticket_form_type->getTicketFormObject();
    if (empty($form_object) || empty($form_object->ticket_fields)) {
      return [];
    }

    // Field processing.
    $ticket_fields = [];
    $ticket_description = NULL;
    foreach ($request_values as $field_key => $request_value) {
      if ($field_key === 'attachments' || $field_key == 'anonymous_requester_email') {
        continue;
      }

      // Skip non-fields or fields with missing ids.
      if (!isset($form['request'][$field_key]['#ticket_field']) ||
          empty($form['request'][$field_key]['#ticket_field']->id)) {
        continue;
      }

      $element_field = $form['request'][$field_key]['#ticket_field'];
      if (!empty($form_object->ticket_fields[$element_field->id])) {
        // Get the defined field from the entity to ensure that is the
        // un-altered version.
        $field = $form_object->ticket_fields[$element_field->id];
        $field_added = FALSE;
        if (empty($field->removable)) {
          // Map to system field.
          if (isset($field->type) && !isset($ticket_fields[$field->type])) {
            if ($field->type == 'subject') {
              $ticket_fields[$field->type] = $request_value;
              $field_added = TRUE;
            }
            elseif ($field->type == 'description' && !isset($ticket_description)) {
              $ticket_description = $request_value;
              $field_added = TRUE;
            }
          }
        }

        // Custom fields and any system field was not mapped.
        if (!$field_added) {
          $ticket_fields['custom_fields'][] = [
            'id' => $field->id,
            'value' => $request_value,
          ];
        }
      }
    }

    // Subject and body are required.
    if (empty($ticket_fields['subject']) || empty($ticket_description)) {
      return [];
    }

    // Set description for the initial ticket comment.
    $ticket_fields['comment'] = [
      'body' => $ticket_description,
    ];
    if ($comment_vis = $this->config->get('comment_visibility')) {
      $ticket_fields['comment']['public'] = $comment_vis == 'public';
    }

    // Build the ticket request.
    // @TODO: Add settings for default 'priority' and 'type'.
    $ticket = [
      'ticket_form_id' => $zendesk_ticket_form_type->id(),
      'priority' => 'normal',
    ];

    // Merge ticket fields.
    $ticket += $ticket_fields;

    // Requester.
    $ticket['requester'] = [];

    // Requester email.
    if (!empty($request_values['anonymous_requester_email'])) {
      $ticket['requester']['email'] = $request_values['anonymous_requester_email'];

      // TODO: Set requested name field, $requester['name'].
      // Need a field property to indicate that it is the requester's name.
      $ticket['requester']['name'] = $ticket['requester']['email'];
    }

    // Edge case: The default Zendesk form requires an email.
    // Any extending class that does not need an email address should
    // override this method.
    if (empty($ticket['requester']['email'])) {
      return [];
    }

    // Attachments.
    if (!empty($request_values['attachments'])) {
      foreach ($request_values['attachments'] as $upload) {
        // Upload to tmp.
        $file = File::create([
          'uid' => $this->currentUser->id(),
          'filename' => $upload['name'],
          'uri' => $upload['tmppath'],
        ]);

        if ($file) {
          $ticket['file_uploads'][] = [
            'file' => $file,
            'name' => $upload['name'],
          ];
        }
      }
    }

    return $ticket;
  }

}
