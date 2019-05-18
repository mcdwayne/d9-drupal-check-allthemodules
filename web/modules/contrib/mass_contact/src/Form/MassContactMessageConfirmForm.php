<?php

namespace Drupal\mass_contact\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mass_contact\MassContactInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\mass_contact\Entity\MassContactMessageInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\user\PrivateTempStoreFactory;

/**
 * Form object for the Mass Contact Confirm form.
 */
class MassContactMessageConfirmForm extends ConfirmFormBase {

  /**
   * The mass contact message being sent.
   *
   * @var \Drupal\mass_contact\Entity\MassContactMessage
   */
  protected $massContactMessage;

  /**
   * The email configurations for the mass contact message being sent.
   *
   * @var \Drupal\mass_contact\Entity\MassContactMessage
   */
  protected $messageConfigs;

  /**
   * The mass contact configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The mass contact service.
   *
   * @var \Drupal\mass_contact\MassContactInterface
   */
  protected $massContact;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a MassContactMessageConfirmForm object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\mass_contact\MassContactInterface $mass_contact
   *   The mass contact service.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, MassContactInterface $mass_contact, PrivateTempStoreFactory $temp_store_factory) {
    $this->massContactMessage = $route_match->getParameter('mass_contact_confirm_info')['mass_contact_message'];
    $this->messageConfigs = $route_match->getParameter('mass_contact_confirm_info')['configuration'];
    $this->config = $this->configFactory()->get('mass_contact.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->massContact = $mass_contact;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('mass_contact'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mass_contact_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $recipients = $this->massContact->getrecipients($this->massContactMessage->getCategories(), $this->messageConfigs['respect_opt_out']);
    $recipient_count = count($recipients);

    if ($this->messageConfigs['send_me_copy_user']) {
      if ($recipient_count == 0 || !in_array($this->currentUser()
        ->id(), $recipients)
      ) {
        $recipient_count += 1;
      }
    }
    return $this->t('Are you sure you want to send this message to %user_count user(s)?', ['%user_count' => $recipient_count]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.mass_contact_message.add_form');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build a summary of chosen settings for sending the message.
    // Category list.
    $categories = [];
    // Categories selected, if any.
    $cids = $this->massContactMessage->getCategories();
    if (!empty($cids)) {
      $items = [];
      foreach ($cids as $cid) {
        $items[] = $cid->label();
      }
      sort($items);
      $categories = [
        '#type' => 'ul',
        '#title' => $this->t('Categories'),
        '#items' => $items,
        '#theme' => 'item_list',
      ];
    }

    // Only admins can override optout settings.
    $optout = [];
    if ($this->currentUser()->hasPermission('mass contact administer')) {
      // 'Optout' settings.
      if ($this->messageConfigs['respect_opt_out']) {
        $optout_message = $this->t('You have selected to respect user opt-outs. If a user has opted out of emails they will not receive this mass contact message.');
      }
      else {
        $optout_message = $this->t('You have selected to @not respect user opt-outs. Emails will be sent to all users even if they have elected not to receive a mass contact message.', ['@not' => 'NOT']);
      }
      $optout = [
        '#type' => 'ul',
        '#title' => $this->t('Respect user opt-outs'),
        '#items' => [$optout_message],
        '#theme' => 'item_list',
      ];
    }

    $bcc = [];
    // Check if the user is allowed to override the BCC setting.
    if ($this->currentUser()->hasPermission('mass contact override bcc')) {
      // 'Send as BCC' settings.
      if ($this->messageConfigs['use_bcc']) {
        $bcc_message = $this->t("Recipients of this message will be HIDDEN on the email.");
      }
      else {
        $bcc_message = $this->t("Recipients of this message will @not be HIDDEN on the email.", ['@not' => 'NOT']);
      }
      $bcc = [
        '#type' => 'ul',
        '#title' => $this->t('Send as BCC (hide recipients)'),
        '#items' => [$bcc_message],
        '#theme' => 'item_list',
      ];
    }

    // 'Send me a copy' settings.
    if ($this->messageConfigs['send_me_copy_user']) {
      $copy_message = $this->t("A copy of this message will be sent to you.");
    }
    else {
      $copy_message = $this->t("A copy of this message will @not be sent to you.", ['@not' => 'NOT']);
    }
    $copy = [
      '#type' => 'ul',
      '#title' => $this->t('Send yourself a copy'),
      '#items' => [$copy_message],
      '#theme' => 'item_list',
    ];

    $archive = [];
    // Check if the user is allowed to override the node copy setting.
    if ($this->currentUser()->hasPermission('mass contact override archiving')) {
      // 'Archive a copy of this message' settings.
      if ($this->messageConfigs['create_archive_copy']) {
        $archive_message = $this->t("A copy of this message will be archived on this site.");
      }
      else {
        $archive_message = $this->t("A copy of this message will @not be archived on this site.", ['@not' => 'NOT']);
      }
      $archive = [
        '#type' => 'ul',
        '#title' => $this->t('Archive a copy of this message on this website'),
        '#items' => [$archive_message],
        '#theme' => 'item_list',
      ];
    }

    // Summary of all selections.
    $form['settings_summary'] = [
      '#theme' => 'item_list',
      '#type' => 'ol',
      '#title' => $this->t('You selected the following settings:'),
      '#prefix' => '<div id="settings-summary">',
      '#suffix' => '</div>',
      '#items' => [$categories, $optout, $bcc, $copy, $archive],
    ];

    return $form + parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Process via cron if configured.
    if ($this->config->get('send_with_cron')) {
      // Utilize cron/job queue system.
      $this->massContact->processMassContactMessage(
        $this->massContactMessage,
        $this->messageConfigs
      );
    }
    else {
      // Process immediately via the batch system.
      $all_recipients = $this->massContact->getRecipients($this->massContactMessage->getCategories(), $this->messageConfigs['respect_opt_out']);
      // Add the sender's email to the recipient list if 'Send yourself a copy'
      // option has been chosen AND the email is not already in the recipient
      // list.
      // Add this user as the first user in the list. If the user exists in the
      // recipient list, remove the user and add the user again as first in the
      // list.
      if ($this->messageConfigs['send_me_copy_user']) {
        if (!empty($all_recipients)) {
          $send_me_copy_user_key = array_search($this->messageConfigs['send_me_copy_user'], $all_recipients);
          if ($send_me_copy_user_key) {
            unset($all_recipients[$send_me_copy_user_key]);
          }
        }

        $all_recipients = [
          $this->currentUser()->id() => $this->currentUser()
            ->id(),
        ] + $all_recipients;
      }

      $batch = [
        'title' => $this->t('Sending message'),
        'operations' => [],
      ];
      foreach ($this->massContact->getGroupedRecipients($all_recipients) as $recipients) {
        $data = [
          'recipients' => $recipients,
          'message' => $this->massContactMessage,
          'configuration' => $this->messageConfigs,
        ];
        $batch['operations'][] = [[static::class, 'processRecipients'], $data];
      }
      batch_set($batch);
    }

    // Create a copy of a message if requested.
    if ($this->messageConfigs['create_archive_copy']) {
      $this->massContactMessage->save();

      if ($this->massContactMessage->id()) {
        drupal_set_message($this->t('Mass Contact message sent successfully. A copy has been archived <a href="@url">here</a>.', [
          '@url' => $this->massContactMessage->toUrl()
            ->toString(),
        ]));
      }
    }
    else {
      drupal_set_message($this->t('Mass Contact message sent successfully.'));
    }

    // Delete the entry from the user's tempstore.
    $store = $this->tempStoreFactory->get('mass_contact_confirm_info');
    if (!empty($store)) {
      $store->delete($this->massContactMessage->uuid());
    }

    // Redirect to the add mass contact message form.
    $form_state->setRedirect('entity.mass_contact_message.add_form');
  }

  /**
   * Batch processor for sending the message to recipients.
   *
   * @param array $recipients
   *   An array of recipient user IDs.
   * @param \Drupal\mass_contact\Entity\MassContactMessageInterface $message
   *   The mass contact message.
   * @param array $configuration
   *   The configuration.
   */
  public static function processRecipients(array $recipients, MassContactMessageInterface $message, array $configuration) {
    /** @var \Drupal\mass_contact\MassContactInterface $mass_contact */
    $mass_contact = \Drupal::service('mass_contact');
    $mass_contact->sendMessage($recipients, $message, $configuration);
  }

}
