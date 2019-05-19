<?php

namespace Drupal\ubercart_funds\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\ubercart_funds\Entity\Transaction;

/**
 * Defines a confirmation form to decline a withdrawal request.
 */
class ConfirmWithdrawalDecline extends ConfirmFormBase {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   * @var \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  protected $mailManager;
  protected $messenger;

  /**
   * Class constructor.
   */
  public function __construct(MailManagerInterface $mail_manager, MessengerInterface $messenger) {
    $this->mailManager = $mail_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('messenger')
    );
  }

  /**
   * ID of the withdrawal request.
   *
   * @var int
   */
  protected $requestId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_withdrawal_decline";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $request_id = NULL) {
    $this->requestId = $request_id;

    $form['reason'] = [
      '#type' => 'textarea',
      '#title' => t('Reason for decline'),
      '#description' => $this->t('The message will be addressed to the requester by email.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.uc_funds_transactions.all_withdrawal_requests');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to decline request: %id?', ['%id' => $this->requestId]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Load the request.
    $transaction = Transaction::load($this->requestId);

    // Update request.
    $transaction->setStatus('Declined');
    $transaction->setNotes($form_state->getValue('reason'));
    $transaction->save();

    // Send an email to the requester.
    $requester = $transaction->getIssuer();
    $langcode = $this->config('system.site')->get('langcode');

    $params = [
      'subject' => $this->t('Withdrawal request declined'),
      'title' => $this->t('Dear @requester,', [
        '@requester' => $requester->getAccountName(),
      ]),
      'body' => $this->t('A site administrator has just declined your withdrawal request of @amount (@currency).<br>
        Reason:<br>@reason<p></p>', [
          '@issuer' => $requester->getAccountName(),
          '@amount' => uc_currency_format($transaction->getBrutAmount() / 100),
          '@currency' => $transaction->getCurrencyCode(),
          '@reason' => !empty($transaction->getNotes()) ? $transaction->getNotes() : $this->t('No reason have been indicated.'),
        ]),
    ];
    $this->mailManager->mail('ubercart_funds', 'uc_funds_transaction', $requester->getEmail(), $langcode, $params, NULL, TRUE);

    // Confirmation message.
    $this->messenger->addMessage($this->t('Request declined. An email with the reason has been sent to @user', [
      '@user' => $requester->getAccountName(),
    ]), 'status');

    // Set redirection.
    $form_state->setRedirect('view.uc_funds_transactions.all_withdrawal_requests');
  }

}
