<?php

namespace Drupal\mass_contact\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\mass_contact\MassContactInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Main form for sending Mass Contact emails.
 */
class MassContactForm extends ContentEntityForm {

  /**
   * The mass contact configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The mass contact service.
   *
   * @var \Drupal\mass_contact\MassContactInterface
   */
  protected $massContact;

  /**
   * Constructs the Mass Contact form.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\mass_contact\MassContactInterface $mass_contact
   *   The mass contact service.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, MassContactInterface $mass_contact, PrivateTempStoreFactory $temp_store_factory) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->config = $this->configFactory()->get('mass_contact.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->massContact = $mass_contact;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('mass_contact'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $categories = [];
    $default_category = [];
    $default_category_name = '';

    /** @var \Drupal\mass_contact\Entity\MassContactCategoryInterface $category */
    foreach ($this->entityTypeManager->getStorage('mass_contact_category')->loadMultiple() as $category) {
      if ($category->access('view')) {
        $categories[$category->id()] = $category->label();

        if ($category->getSelected()) {
          $default_category[] = $category->id();
          $default_category_name = $category->label();
        }
      }
    }

    $form['contact_information'] = [
      '#markup' => Xss::filterAdmin($this->config->get('form_information')),
    ];

    // Add the field for specifying the sender's name.
    $default_sender_name = $this->config->get('default_sender_name');
    $sender_name_title = $this->t('Sender name');
    if ($default_sender_name) {
      if ($this->currentUser()->hasPermission('mass contact change default sender information')) {
        $form['sender_name'] = [
          '#type' => 'textfield',
          '#title' => $sender_name_title,
          '#maxlength' => 255,
          '#default_value' => $default_sender_name,
          '#required' => TRUE,
        ];
      }
      else {
        $form['sender_name'] = [
          '#type' => 'item',
          '#title' => $sender_name_title,
          '#value' => $default_sender_name,
        ];
      }
    }
    else {
      $form['sender_name'] = [
        '#type' => 'textfield',
        '#title' => $sender_name_title,
        '#maxlength' => 255,
        '#default_value' => $this->currentUser()->getDisplayName(),
        '#required' => TRUE,
      ];
    }

    // Add the field for specifying the sender's email address.
    $default_sender_email = $this->config->get('default_sender_email');
    $sender_name_title = $this->t('Sender email');
    if ($default_sender_email) {
      if ($this->currentUser()->hasPermission('mass contact change default sender information')) {
        $form['sender_mail'] = [
          '#type' => 'email',
          '#title' => $sender_name_title,
          '#default_value' => $default_sender_email,
          '#required' => TRUE,
        ];
      }
      else {
        $form['sender_mail'] = [
          '#type' => 'item',
          '#title' => $sender_name_title,
          '#value' => $default_sender_email,
        ];
      }
    }
    else {
      $form['sender_mail'] = [
        '#type' => 'email',
        '#title' => $sender_name_title,
        '#default_value' => $this->currentUser()->getEmail(),
        '#required' => TRUE,
      ];
    }

    // Add the field for specifying the category(ies).
    // Categories are optional. This means that if there is no category
    // configured, the user can send a copy to him/herself.
    // If there are categories configured and one of them has been configured as
    // 'selected' by default, present a list of categories to choose from,
    // defaulted to the one configured as one.
    if (count($categories) > 0) {
      // Display a choice when one is needed.
      $form['categories'] = [
        '#type' => $this->config->get('category_display'),
        '#title' => $this->t('Category'),
        '#default_value' => $default_category,
        '#options' => $categories,
        '#multiple' => TRUE,
      ];
    }
    else {
      // There is a default category selected and it is only one category
      // configured.
      if ($default_category) {
        // Otherwise, just use the default category.
        $form['categories'] = [
          '#type' => 'value',
          '#value' => $default_category,
        ];
        $form['cid-info'] = [
          '#type' => 'item',
          '#title' => $this->t('Category'),
          '#markup' => $this->t('This message will be sent to all users in the %category category.', ['%category' => $default_category_name]),
        ];
      }
      else {
        // There are no categories configured.
        $form['cid-info'] = [
          '#type' => 'item',
          '#title' => $this->t('Category'),
          '#markup' => $this->t('No categories have been configured.'),
        ];
      }
    }

    // Add the field for specifying whether opt-outs are respected or not.
    $optout_setting = $this->config->get('optout_enabled');

    // Allow users to opt-out of mass emails:
    // 'disabled' => 'No', 'global' == 'Yes', 'category' == 'Selected
    // categories'.
    if ($optout_setting !== MassContactInterface::OPT_OUT_DISABLED) {
      // @todo https://www.drupal.org/node/2867177
      // Allow to override or respect opt-outs if admin, otherwise use
      // default.
      if ($this->currentUser()->hasPermission('mass contact administer')) {
        $form['optout'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Respect user opt-outs.'),
          '#default_value' => 1,
        ];
      }
      else {
        $form['optout'] = [
          '#type' => 'hidden',
          '#default_value' => 1,
        ];
      }
    }
    else {
      $form['optout'] = [
        '#type' => 'hidden',
        '#default_value' => 0,
      ];
    }

    // Add the field for specifying whether the recipients are in the To or
    // BCC field of the message.
    // Check if the user is allowed to override the BCC setting.
    if ($this->currentUser()->hasPermission('mass contact override bcc')) {
      $form['use_bcc'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Send as BCC (hide recipients).'),
        '#default_value' => $this->config->get('use_bcc'),
      ];
    }
    // If not, then just display the BCC info.
    else {
      $form['use_bcc'] = [
        '#type' => 'value',
        '#value' => $this->config->get('use_bcc'),
      ];
      $form['bcc-info'] = [
        '#type' => 'item',
        '#title' => $this->t('Send as BCC (hide recipients)'),
        '#markup' => $this->config->get('use_bcc')
        ? $this->t('Recipients will be hidden from each other.')
        : $this->t('Recipients will NOT be hidden from each other.'),
      ];
    }

    // Add the field for specifying the subject of the message.
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    // Add the field for specifying the body and text format of the message.
    // Get the HTML input format setting and the corresponding name.
    // Get the admin specified default text format.
    $default_filter_format = $this->config->get('message_format');

    // Check if the user is allowed to override the text format.
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message'),
      '#format' => $default_filter_format ?: filter_default_format(),
      '#rows' => 12,
      '#required' => TRUE,
    ];
    if (!$this->currentUser()->hasPermission('mass contact override text format')) {
      // The user is not allowed to override the text format, so lock it down
      // to the default one.
      $form['body']['#allowed_formats'] = [$default_filter_format ?: filter_default_format()];
    }

    if (!$this->moduleHandler->moduleExists('mimemail') && !$this->moduleHandler->moduleExists('swiftmailer')) {
      // No HTML email handling, lock down to plain text.
      $form['body']['#allowed_formats'] = ['plain_text'];
      $form['body']['#format'] = 'plain_text';
    }

    // If the user has access, add the field for specifying the attachment.
    if (FALSE && ($this->moduleHandler->moduleExists('mimemail') || $this->moduleHandler->moduleExists('swiftmailer'))) {
      // @todo Port email attachments.
      // @see https://www.drupal.org/node/2867544
      if ($this->currentUser()->hasPermission('mass contact include attachments')) {
        for ($i = 1; $i <= $this->config->get('number_of_attachments'); $i++) {
          $form['attachment_' . $i] = [
            '#type' => 'file',
            '#title' => $this->t('Attachment #!number', ['!number' => $i]),
          ];
        }
      }
    }

    // We do not allow anonymous users to send themselves a copy because it
    // can be abused to spam people.
    // @todo Why are anonymous users allowed to hit this form at all?!
    if ($this->currentUser()->id()) {
      $form['copy'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Send yourself a copy.'),
      ];
    }

    // Check if the user is allowed to override the node copy setting.
    if ($this->currentUser()->hasPermission('mass contact override archiving')) {
      $form['create_archive_copy'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Archive a copy of this message on this website.'),
        '#default_value' => $this->config->get('create_archive_copy'),
      ];
    }
    // If not, then do it or not based on the administrative setting.
    else {
      $form['create_archive_copy'] = [
        '#type' => 'value',
        '#value' => $this->config->get('create_archive_copy'),
      ];
      $form['archive_notice'] = [
        '#type' => 'item',
        '#title' => $this->t('Archive a copy of this message on this website'),
        '#markup' => $this->config->get('create_archive_copy') ? $this->t('A copy of this message will be archived on this website.')
        : $this->t('A copy of this message will NOT be archived on this website.'),
      ];
    }

    // Add the submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send email'),
    ];

    $this->buildTaskList($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    // @todo Potentially refactor to add the 'Send email' button here.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Get the list of recipients for all chosen categories if any have been
    // chosen.
    if ($form_state->getValue('categories')) {
      $categories = $this->entityTypeManager->getStorage('mass_contact_category')
        ->loadMultiple($form_state->getValue('categories'));
      $all_recipients = $this->massContact->getRecipients($categories, $form_state->getValue('optout'));
    }
    // If the 'Send yourself a copy' option has been chosen. count it as a
    // recipient.
    if ($form_state->getValue('copy')) {
      $all_recipients[] = $this->currentUser()->id();
    }
    // Either a category should be chosen, or send yourself a copy option should
    // be checked.
    if (empty($all_recipients)) {
      $form_state->setErrorByName('categories', $this->t('There are no recipients chosen for this mass contact message.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $configuration = [
      'use_bcc' => $form_state->getValue('use_bcc'),
      'sender_name' => $form_state->getValue('sender_name'),
      'sender_mail' => $form_state->getValue('sender_mail'),
      'create_archive_copy' => $form_state->getValue('create_archive_copy'),
      'respect_opt_out' => $form_state->getValue('optout'),
    ];

    // Add the sender's email to the configs, if the 'Send yourself a copy'
    // option has been chosen.
    if ($form_state->getValue('copy')) {
      $configuration['send_me_copy_user'] = $this->currentUser()->id();
    }
    else {
      $configuration['send_me_copy_user'] = FALSE;
    }

    $message = $this->entityTypeManager->getStorage('mass_contact_message')->create([
      'subject' => $form_state->getValue('subject'),
      'body' => $form_state->getValue('body'),
    ]);
    $categories = [];
    if (!empty($form_state->getValue('categories'))) {
      foreach ($form_state->getValue('categories') as $id) {
        $categories[] = ['target_id' => $id];
      }
      $message->categories = $categories;
    }

    // Store data needed for the confirmation form in the user's private temp
    // storage.
    $store = \Drupal::service("user.private_tempstore")->get('mass_contact_confirm_info');
    $store->set($message->uuid(),
      [
        'mass_contact_message' => $message,
        'configuration' => $configuration,
      ]);

    // Redirect to the confirmation form.
    $form_state->setRedirect('entity.mass_contact.confirm_before_send', [
      'mass_contact_confirm_info' => $message->uuid(),
    ]);

  }

  /**
   * Builds the task list at the bottom of the mass contact form.
   *
   * @param array $form
   *   The mass contact form definition.
   */
  protected function buildTaskList(array &$form) {
    if ($this->currentUser()->hasPermission('mass contact administer')) {
      $tasks = [];
      if ($this->currentUser()->hasPermission('administer permissions')) {
        $tasks[] = Link::createFromRoute($this->t('Set Mass Contact permissions'), 'user.admin_permissions', [], ['fragment' => 'module-mass_contact'])->toRenderable();
      }
      $tasks[] = Link::createFromRoute($this->t('List current categories'), 'entity.mass_contact_category.collection')->toRenderable();
      $tasks[] = Link::createFromRoute($this->t('Add new category'), 'entity.mass_contact_category.add_form')->toRenderable();
      $tasks[] = Link::createFromRoute($this->t('Configure Mass Contact settings'), 'mass_contact.settings')->toRenderable();
      $tasks[] = Link::createFromRoute($this->t('Help'), 'help.page', ['name' => 'mass_contact'])->toRenderable();

      $form['tasklist'] = [
        '#type' => 'details',
        // Open if there are no categories.
        '#open' => empty($categories),
        '#title' => $this->t('Related tasks'),
      ];
      $form['tasklist']['tasks'] = [
        '#theme' => 'item_list',
        '#items' => $tasks,
      ];
    }
  }

}
