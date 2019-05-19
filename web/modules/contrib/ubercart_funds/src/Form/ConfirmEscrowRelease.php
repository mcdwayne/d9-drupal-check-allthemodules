<?php

namespace Drupal\ubercart_funds\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\ubercart_funds\Entity\Transaction;

/**
 * Defines a confirmation form to release an escrow payment.
 */
class ConfirmEscrowRelease extends ConfirmFormBase {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Session\AccountProxy
   * @var \Drupal\Core\Mail\MailManagerInterface $mailManager
   * @var \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  protected $currentUser;
  protected $mailManager;
  protected $messenger;

  /**
   * Class constructor.
   */
  public function __construct(AccountProxy $current_user, MailManagerInterface $mail_manager, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->mailManager = $mail_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('plugin.manager.mail'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_escrow_release";
  }

  /**
   * Check if the user is allowed to perform an escrow operation.
   *
   * @param \Drupal\ubercart_funds\Entity\Transaction $transaction
   *   The transaction id to check permissions on.
   *
   * @return bool
   *   User is allowed or not.
   */
  protected function isUserAllowed(Transaction $transaction) {
    $uid = $this->currentUser->id();
    $query = \Drupal::request()->get('action');

    if ($transaction->getStatus() !== "Completed") {
      if ($query == "cancel-escrow") {
        if ($uid == $transaction->getIssuerId() || $uid == $transaction->getRecipientId()) {
          return TRUE;
        }
      }
      if ($query == "release-escrow" && $uid == $transaction->getIssuerId()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $transaction_id = NULL) {
    // Check if the user is allowed to perform the operation.
    $transaction = Transaction::load(\Drupal::request()->get('transaction_id'));
    if ($this->isUserAllowed($transaction)) {
      return parent::buildForm($form, $form_state);
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.uc_funds_user_transactions.incoming_escrow_payments');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to release that escrow payment?');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, string $transaction_id = NULL) {
    $transaction = Transaction::load(\Drupal::request()->get('transaction_id'));
    $issuer = $transaction->getIssuer();
    $recipient = $transaction->getRecipient();
    $fee = $transaction->getFee();

    // Release escrow payment.
    \Drupal::service('ubercart_funds.transaction_manager')->addFundsToBalance($transaction, $recipient);

    // Update site balance.
    \Drupal::service('ubercart_funds.transaction_manager')->updateSiteBalance($transaction);

    // Update transaction.
    $transaction->setStatus('Completed');
    $transaction->save();

    // Send an HTML email to the recipient of the escrow payment.
    $langcode = $this->config('system.site')->get('langcode');
    $params = [
      'subject' => $this->t('Escrow payment released'),
      'title' => $this->t('Dear @recipient,', [
        '@recipient' => $recipient->getAccountName(),
      ]),
      'body' => $this->t('@issuer has released an escrow payment of @amount (@currency) to your account. Your balance has been updated accordingly.', [
        '@issuer' => $issuer->getAccountName(),
        '@amount' => uc_currency_format($transaction->getBrutAmount() / 100),
        '@currency' => $transaction->getCurrencyCode(),
      ]),
    ];
    $this->mailManager->mail('ubercart_funds', 'uc_funds_transaction', $recipient->getEmail(), $langcode, $params, NULL, TRUE);

    // Set a confirmation message to user.
    if (!$fee) {
      $this->messenger->addMessage($this->t('You have transfered @amount (@currency) to @user.', [
        '@amount' => uc_currency_format($transaction->getBrutAmount() / 100),
        '@currency' => $transaction->getCurrencyCode(),
        '@user' => $recipient->getAccountName(),
      ]), 'status');
    }
    if ($fee) {
      $this->messenger->addMessage($this->t('You have transfered @amount (@currency) to @user with a commission of %commission.', [
        '@amount' => uc_currency_format($transaction->getBrutAmount() / 100),
        '@currency' => $transaction->getCurrencyCode(),
        '@user' => $recipient->getAccountName(),
        '%commission' => uc_currency_format($fee / 100),
      ]), 'status');
    }

    // Set redirection.
    $form_state->setRedirect('view.uc_funds_user_transactions.incoming_escrow_payments');
  }

}
