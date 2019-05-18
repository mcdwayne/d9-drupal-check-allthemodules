<?php

namespace Drupal\pagarme_marketplace\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\pagarme\Helpers\PagarmeUtility;
use Drupal\pagarme\Pagarme\PagarmeSdk;
use Drupal\pagarme_marketplace\Helpers\PagarmeMarketplaceUtility;
use PagarMe\Sdk\BankAccount\BankAccount;
use PagarMe\Sdk\Recipient\Recipient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RecipientDeleteForm.
 *
 * @package Drupal\pagarme_marketplace\Form
 */
class RecipientTransferForm extends FormBase {

  const PAGARME_RECIPIENT_ARCHIVED = 1;

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $route_match;

  protected $pagarme_sdk;

  public function __construct(Connection $database, CurrentRouteMatch $route_match) {
    $this->database = $database;
    $this->route_match = $route_match;
    $this->pagarme_sdk = new PagarmeSdk($this->route_match->getParameter('company'));
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recipients_transfer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $company = NULL, $recipient_id = NULL) {
    
    $company_info = $this->pagarme_sdk->getCompanyInfo();

    $recipient = $this->pagarme_sdk->pagarme->recipient()->get($recipient_id);

    $balance = $this->pagarme_sdk->pagarme->recipient()->balance($recipient);
    $available = $balance->getAvailable()->amount;
    $available = PagarmeMarketplaceUtility::currencyAmountFormat($available, 'integer');

    $options = array();
    $bank_account_id = $recipient->getBankAccount()->getId();
    $legal_name = $recipient->getBankAccount()->getLegalName();
    $options[$bank_account_id] = $legal_name;

    $form['bank_account_id'] = array(
      '#type' => 'select',
      '#title' => 'Selecione a conta',
      '#description' => 'Selecione a conta para a qual deseja efetuar o saque.',
      '#options' => $options,
    );

    $form['recipient_id'] = array(
      '#type' => 'hidden',
      '#value' => $recipient_id,
    );

    $form['transfer'] = array(
      '#type' => 'fieldset',
      '#title' => t('Perform service'),
    );

    $amount = $balance->getAvailable()->amount;
    $form['transfer']['amount'] = array(
      '#type' => 'textfield',
      '#title' => t('Choose the amount'),
      '#description' => 'Valor máximo a ser transferido ' . $available,
      '#default_value' => PagarmeUtility::amountIntToDecimal($amount),
      '#required' => TRUE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Confirm service'),
    );

    $form['recipient_account'] = array(
      '#type' => 'fieldset', 
      '#title' => t("Recipient's account information"),
      '#collapsible' => TRUE, 
      '#collapsed' => FALSE,
    );

    $rows = array();
    $rows[] = array(t('NAME/COMPANY NAME'), $recipient->getBankAccount()->getLegalName());
    $rows[] = array(t('BANK'), $recipient->getBankAccount()->getBankCode());
    $rows[] = array(t('CPF/CNPJ'), $recipient->getBankAccount()->getDocumentNumber());
    $rows[] = array(t('AGÊNCIA'), $recipient->getBankAccount()->getAgencia());
    $rows[] = array(t('CONTA BANCÁRIA'), $recipient->getBankAccount()->getConta());

    $form['recipient_account']['info'] = [
      '#type' => 'table',
      '#rows' => $rows,
    ];

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    $amount = PagarmeUtility::amountDecimalToInt($values['amount']);

    $recipient_id = $values['recipient_id'];
    $balance = $this->pagarme_sdk->pagarme->recipient()->balance(
        new Recipient(array('id' => $recipient_id))
    );

    if ($amount > $balance->getAvailable()->amount) {
      $form_state->setErrorByName('amount', $this->t('Insufficient funds.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $amount = PagarmeUtility::amountDecimalToInt($values['amount']);

    $recipient_id = $values['recipient_id'];
    $bank_account_id = $values['bank_account_id'];

    try {
      $transfer = $this->pagarme_sdk->pagarme->transfer()->create(
          $amount,
          new Recipient(array('id' => $recipient_id)),
          new BankAccount(array('id' => $bank_account_id))
      );
      drupal_set_message(t('Successful withdrawal'));
    } catch (\Exception $e) {
      \Drupal::logger('pagarme_marketplace')->error($e->getMessage());
      drupal_set_message(t('Failed to serve.'), 'error');
    }
  }
}