<?php

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\uc_order\OrderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to set the recipient of an invoice, then mails it.
 */
class MailInvoiceForm extends FormBase {

  /**
   * The order to be emailed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   */
  public function __construct(MailManagerInterface $mail_manager) {
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_mail_invoice_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL) {
    $this->order = $uc_order;

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Recipient e-mail address'),
      '#default_value' => $uc_order->getEmail(),
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Mail invoice'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $recipient = $form_state->getValue('email');
    $params = ['order' => $this->order];
    $this->mailManager->mail('uc_order', 'invoice', $recipient, uc_store_mail_recipient_langcode($recipient), $params, uc_store_email_from());

    $message = $this->t('Invoice e-mailed to @email.', ['@email' => $recipient]);
    $this->messenger()->addMessage($message);
    $this->order->logChanges([$message]);
  }

}
