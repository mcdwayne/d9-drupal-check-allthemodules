<?php

namespace Drupal\mailing_list\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for mailing list forms.
 */
class MailingListForm extends BundleEntityFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs the MailingListForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\mailing_list\MailingListInterface $mailing_list */
    $mailing_list = $this->entity;
    $form['#title'] = $this->operation == 'add' ? $this->t('Add a new mailing list') : $this->t('Edit %label mailing list', ['%label' => $mailing_list->label()]);

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $mailing_list->label(),
      '#description' => $this->t('The human-readable name of this mailing list. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $mailing_list->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\mailing_list\Entity\MailingList', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this mailing list. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#rows' => 2,
      '#default_value' => $mailing_list->getDescription(),
      '#description' => $this->t('This text will be displayed on subscriptions management pages for this mailing list.'),
    ];

    $form['help'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Explanation or submission guidelines'),
      '#rows' => 2,
      '#default_value' => $mailing_list->getHelp(),
      '#description' => $this->t('Help information shown to the user when creating a new subscription on this list.'),
    ];

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['control'] = [
      '#type' => 'details',
      '#title' => $this->t('Subscription control'),
      '#open' => TRUE,
      '#group' => 'additional_settings',
    ];

    $form['control']['cross_access'] = [
      '#title' => $this->t('Allow subscription cross access'),
      '#description' => $this->t('Grant access to non-owned subscriptions through the subscription management hashed URL.'),
      '#type' => 'checkbox',
      '#default_value' => $mailing_list->isCrossAccessAllowed(),
    ];

    $form['control']['form_destination'] = [
      '#type' => 'select',
      '#title' => $this->t('Form destination'),
      '#options' => [
        '' => $this->t('- Default -'),
        'canonical' => $this->t('View subscription'),
        'edit-form' => $this->t('Edit subscription'),
        'manage' => $this->t('Manage subscriptions'),
      ],
      '#description' => $this->t('Where to go after the subscription is created.'),
      '#default_value' => $mailing_list->getFormDestination(),
    ];

    $form['control']['max_per_user'] = [
      '#title' => $this->t('Limit per user'),
      '#description' => $this->t('Max allowed subscriptions per user. Enter 0 for no limit.'),
      '#type' => 'number',
      '#default_value' => $mailing_list->getLimitByUser() ?: 0,
      '#group' => 'additional_settings',
    ];

    $form['control']['max_per_email'] = [
      '#title' => $this->t('Limit per email'),
      '#description' => $this->t('Max allowed subscriptions per email address. Enter 0 for no limit.'),
      '#type' => 'number',
      '#default_value' => $mailing_list->getLimitByEmail() ?: 0,
      '#group' => 'additional_settings',
    ];

    $secs_per_week = 7 * 24 * 60 * 60;
    $options = [
      $secs_per_week => $this->t('1 week'),
    ];
    for ($i = 2; $i <= 7; $i++) {
      $options[$secs_per_week * $i] = $this->t('@count weeks', ['@count' => $i]);
    }
    for ($i = 2; $i <= 11; $i++) {
      $options[round($i * 4.33)] = $this->t('@count months', ['@count' => $i]);
    }
    $options[$secs_per_week * 52] = $this->t('1 year');
    for ($i = 2; $i <= 5; $i++) {
      $options[$secs_per_week * 52 * $i] = $this->t('@count years', ['@count' => $i]);
    }
    $options[0] = $this->t('- Do not purge -');

    $form['control']['inactive_subscriptions_liftime'] = [
      '#type' => 'select',
      '#title' => $this->t('Inactive subscriptions lifetime'),
      '#description' => $this->t('Inactive subscriptions older than this value will be purged from database.'),
      '#options' => $options,
      '#default_value' => $mailing_list->getInactiveLifetime() ?: 0,
      '#group' => 'additional_settings',
    ];

    $form['messages'] = [
      '#type' => 'details',
      '#title' => $this->t('Subscription operation messages'),
      '#group' => 'additional_settings',
    ];

    $form['messages']['subscription_message'] = [
      '#type' => 'textarea',
      '#title' => t('On subscription message'),
      '#description' => $this->t('Message to the subscriber after a new subscription has done.'),
      '#rows' => 2,
      '#default_value' => $mailing_list->getOnSubscriptionMessage(),
    ];

    $form['messages']['cancellation_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('On cancellation message'),
      '#description' => $this->t('Message to the subscriber on subscription cancellation.'),
      '#rows' => 2,
      '#default_value' => $mailing_list->getOnCancellationMessage(),
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save mailing list');
    $actions['delete']['#value'] = t('Delete mailing list');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('id'));
    // '0' is invalid, to safe empty check.
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }

    $max_per_user = intval($form_state->getValue('max_per_user'));
    if ($max_per_user < 0) {
      $form_state->setErrorByName('max_per_user', $this->t("Limit per user must be an number greater or equal than 0."));
    }

    $max_per_email = intval($form_state->getValue('max_per_email'));
    if ($max_per_email < 0) {
      $form_state->setErrorByName('max_per_email', $this->t("Limit per email must be an number greater or equal than 0."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\mailing_list\Entity\MailingList $mailing_list */
    $mailing_list = $this->entity;

    $status = $mailing_list->save();

    $t_args = ['%name' => $mailing_list->label()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The mailing list %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The mailing list %name has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $mailing_list->toLink($this->t('View'), 'collection')->toString()]);
      $this->logger('mailing_list')->notice('Added mailing list %name.', $context);
    }

    $this->entityManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($mailing_list->toUrl('collection'));
  }

}
