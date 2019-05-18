<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\braintree_cashier\BillableUser;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannel;

/**
 * Class InvoiceBillingInformationForm.
 */
class InvoiceBillingInformationForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * The billable user service.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * Constructs a new InvoiceBillingInformationForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannel $logger_channel_braintree_cashier
   *   The braintree_cashier logger channel.
   * @param \Drupal\braintree_cashier\BillableUser $billableUser
   *   The billable user service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannel $logger_channel_braintree_cashier, BillableUser $billableUser) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_channel_braintree_cashier;
    $this->billableUser = $billableUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.channel.braintree_cashier'),
      $container->get('braintree_cashier.billable_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invoice_billing_information_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {

    if (empty($user)) {
      return [];
    }

    $form['billing_information'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Extra billing information'),
      '#description' => $this->t("Need to associate special billing information (address, instructions, etc.) with your invoices? Add it here, and we'll make sure it's included."),
      '#default_value' => $this->billableUser->getRawInvoiceBillingInformation($user),
    ];

    $form['uid'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->entityTypeManager->getStorage('user')->load($form_state->getValue('uid'));
    $this->billableUser->setInvoiceBillingInformation($user, $form_state->getValue('billing_information'));
  }

}
