<?php

namespace Drupal\pagarme_marketplace\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\pagarme\Helpers\PagarmeCpfCnpj;
use Drupal\pagarme\Helpers\PagarmeUtility;
use Drupal\pagarme\Pagarme\PagarmeSdk;
use PagarMe\Sdk\BankAccount\BankAccount;
use PagarMe\Sdk\Recipient\Recipient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RecipientForm.
 *
 * @package Drupal\pagarme_marketplace\Form
 */
class RecipientForm extends FormBase {

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $route_match;

  public function __construct(Connection $database, CurrentRouteMatch $route_match) {
    $this->database = $database;
    $this->route_match = $route_match;
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
    return 'recipient_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $op = 'add', $recipient_id = NULL) {
    $company = $this->route_match->getParameter('company');
    $pagarme_sdk = new PagarmeSdk($company);

    $form['recipient_id'] = array(
      '#type' => 'hidden',
      '#value' => $recipient_id
    );

    $form['transfer_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatic withdrawal'),
      '#description' => t('Indicates whether the recipient can receive payments automatically.'),
    );

    $form['transfer_interval'] = array(
      '#type' => 'select',
      '#title' => t('Frequency at which the recipient will be paid'),
      '#description' => t('Frequency at which the recipient will be paid'),
      '#options' => PagarmeUtility::transferInterval(),
      '#default_value' => 'weekly',
      '#ajax' => [
        'callback' => [$this, 'transfer_day_ajax_callback'],
        'effect' => 'fade',
      ],
      '#states' => array(
        'visible' => array(
          'input[name="transfer_enabled"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['transfer_day'] = array(
      '#type' => 'select',
      '#title' => t('Day on which the recipient will be paid'),
      '#description' => t('Day on which the recipient will be paid'),
      '#prefix' => '<div id="transfer-day-options-replace">',
      '#suffix' => '</div>',
      '#states' => array(
        'visible' => array(
          'input[name="transfer_enabled"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['bank_account'] = array(
      '#type' => 'fieldset',
      '#title' => t("Recipient's bank details"),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['bank_account']['bank_id'] = array('#type' => 'hidden');

    $form['bank_account']['bank_code'] = array(
      '#type' => 'select',
      '#title' => t('Bank Code'),
      '#description' => t("Recipient's bank code."),
      '#options' => PagarmeUtility::banks(),
      '#required' => TRUE,
    );

    $form['bank_account']['type'] = array(
      '#type' => 'select',
      '#title' => t('Account type'),
      '#description' => t('Type of bank account.'),
      '#options' => PagarmeUtility::accountTypes(),
      '#required' => TRUE,
    );

    $form['bank_account']['legal_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Full name or company name'),
      '#description' => t('Full name or business name of the recipient.'),
      '#required' => TRUE,
    );

    $form['bank_account']['document_number'] = array(
      '#type' => 'number',
      '#title' => t('CPF or CNPJ'),
      '#description' => t('CPF or CNPJ of the recipient.'),
      '#maxlength' => 14,
      '#size' => 14,
      '#required' => TRUE,
    );

    $form['bank_account']['agencia'] = array(
      '#type' => 'number',
      '#title' => t('Agency Number'),
      '#description' => t('Recipient account agency.'),
      '#maxlength' => 5,
      '#size' => 5,
      '#required' => TRUE,
    );

    $form['bank_account']['agencia_dv'] = array(
      '#type' => 'textfield',
      '#title' => t('Agency Verifier Digit'),
      '#description' => t("Checker's agency check digit."),
      '#maxlength' => 2,
      '#size' => 2,
    );

    $form['bank_account']['conta'] = array(
      '#type' => 'number',
      '#title' => t('Account number'),
      '#description' => t("Recipient's bank account number."),
      '#maxlength' => 13,
      '#size' => 13,
      '#required' => TRUE,
    );

    $form['bank_account']['conta_dv'] = array(
      '#type' => 'textfield',
      '#title' => t('Account Verifier Digit'),
      '#description' => t('Recipient account verifier digit.'),
      '#maxlength' => 2,
      '#size' => 2,
      '#required' => TRUE,
    );

    if ($op == 'edit' && !empty($recipient_id)) {
      $recipient = $pagarme_sdk->pagarme->recipient()->get($recipient_id);

      $form['transfer_enabled']['#default_value'] = $recipient->getTransferEnabled();
      $form['transfer_interval']['#default_value'] = $recipient->getTransferInterval();

      $form['transfer_day']['#default_value'] = $recipient->getTransferDay();
      $bank_account = $recipient->getBankAccount();

      $form['bank_account']['bank_id']['#value'] = $bank_account->getId();
      $form['bank_account']['bank_code']['#default_value'] = $bank_account->getBankCode();
      $form['bank_account']['type']['#default_value'] = $bank_account->getType();
      $form['bank_account']['legal_name']['#default_value'] = $bank_account->getLegalName();
      $form['bank_account']['document_number']['#default_value'] = $bank_account->getDocumentNumber();
      $form['bank_account']['document_number']['#attributes'] = array('readonly' => 'readonly');
      $form['bank_account']['agencia']['#default_value'] = $bank_account->getAgencia();
      $form['bank_account']['agencia_dv']['#default_value'] = $bank_account->getAgenciaDv();
      $form['bank_account']['conta']['#default_value'] = $bank_account->getConta();
      $form['bank_account']['conta_dv']['#default_value'] = $bank_account->getContaDv();
    }

    $form['transfer_day']['#options'] = $this->transfer_day_options($form, $form_state);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save recipient'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values =  $form_state->getValues();
    if (!PagarmeCpfCnpj::valid($values['document_number'])) {
      $form_state->setErrorByName('document_number', $this->t('The entered cpf / cnpj is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $company = $this->route_match->getParameter('company');
    $pagarme_sdk = new PagarmeSdk($company);

    $values = $form_state->getValues();
    $transfer_interval = $values["transfer_interval"];
    $transfer_day = $values["transfer_day"];
    if ($transfer_interval == 'daily') {
      $transfer_day = 0;
    }

    $transfer_enabled = TRUE;
    if (!$values["transfer_enabled"]) {
      $transfer_enabled = FALSE;
    }

    $bank_account = array(
      'bankCode' => $values["bank_code"],
      'type' => $values["type"],
      'legalName' => $values["legal_name"],
      'documentNumber' => $values["document_number"],
      'agencia' => $values["agencia"],
      'agenciaDv' => $values["agencia_dv"],
      'conta' => $values["conta"],
      'contaDv' => $values["conta_dv"],
    );

    try {
      if (empty($values["recipient_id"])) {
        /** @var \PagarMe\Sdk\Recipient\Recipient $recipient */
        $recipient = $pagarme_sdk->pagarme->recipient()->create(
            new BankAccount($bank_account),
            $transfer_interval,
            $transfer_day,
            $transfer_enabled,
            FALSE,
            0
        );
        $fields = $this->mapFieldsToRegiste($recipient);
        $fields['created'] = \Drupal::time()->getRequestTime();
        $fields['company'] = $company;
        $this->database->insert('pagarme_recipients')
          ->fields($fields)
          ->execute();
      }
      else {
        $recipient_data = array(
          'id' => $values['recipient_id'],
          'bankAccount' =>  new BankAccount($bank_account),
          'transferInterval' => $transfer_interval,
          'transferDay' => $transfer_day,
          'transferEnabled' => $transfer_enabled,
        );
        /** @var \PagarMe\Sdk\Recipient\Recipient $recipient */
        $recipient = $pagarme_sdk->pagarme->recipient()->update(
          new Recipient($recipient_data)
        );
        $fields = $this->mapFieldsToRegiste($recipient);
        $this->database->update('pagarme_recipients')
          ->fields($fields)
          ->condition('pagarme_id', $recipient->getId())
          ->execute();
      }
      drupal_set_message(t('Recipient saved successfully.'));
      $form_state->setRedirect(
          'pagarme_marketplace.company_recipients', 
          ['company' => $company]
      );
    } 
    catch (\PagarMe\Sdk\ClientException $e) {
      $response = json_decode(json_decode($e->getMessage()));
      $errors = array();
      if (is_object($response)) {
        if (!empty($response->errors)) {
          foreach ($response->errors as $key => $error) {
            $errors[] = t($error->parameter_name) . ': ' . t($error->message);
          }
        }

        $message = [
          '#theme' => 'item_list',
          '#items' => $errors
        ];

        drupal_set_message($message, 'error');
      }
    } 
    catch (\Exception $e) {
      \Drupal::logger('pagarme_marketplace')->error($e->getMessage());
    }
  }

  /**
   * @param \PagarMe\Sdk\Recipient\Recipient $recipient
   * @return array
   */
  public function mapFieldsToRegiste(Recipient $recipient) {
    return array(
      'pagarme_id' => $recipient->getId(),
      'transfer_enabled' => (int) $recipient->getTransferEnabled(),
      'transfer_interval' => $recipient->getTransferInterval(),
      'transfer_day' => (int) $recipient->getTransferDay(),
      'bank_id' => $recipient->getBankAccount()->getId(),
      'bank_code' => $recipient->getBankAccount()->getBankCode(),
      'type' => $recipient->getBankAccount()->getType(),
      'type' => '',
      'legal_name' => $recipient->getBankAccount()->getLegalName(),
      'document_number' => $recipient->getBankAccount()->getDocumentNumber(),
      'agencia' => $recipient->getBankAccount()->getAgencia(),
      'agencia_dv' => $recipient->getBankAccount()->getAgenciaDv(),
      'conta' => $recipient->getBankAccount()->getConta(),
      'conta_dv' => $recipient->getBankAccount()->getContaDv(),
      'changed' => \Drupal::time()->getRequestTime(),
      'archived' => 0,
    );
  }

  /**
   * Ajax callback.
   */
  public static function transfer_day_ajax_callback(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(
        '#transfer-day-options-replace',
        render($form['transfer_day'])
    ));
    $values = $form_state->getValues();
    if ($values['transfer_interval'] == 'daily') {
      $response->addCommand(new InvokeCommand(
          '#transfer-day-options-replace',
          'hide'
      ));
    }
    return $response;
  }

  private function transfer_day_options(&$form, $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['transfer_interval'])) {
      $transfer_interval = $values['transfer_interval'];
    } 
    elseif (!empty($form['transfer_interval']['#default_value'])) {
      $transfer_interval = $form['transfer_interval']['#default_value'];
    }
    $options = array();
    if (!empty($transfer_interval)) {
      switch ($transfer_interval) {
        case 'daily':
          $form['transfer_day']['#attributes']['style'][] = 'display:none;';
          break;
        case 'weekly':
          $options = PagarmeUtility::weekdays();
          break;
        case 'monthly':
          $options = PagarmeUtility::daysMonth();
          break;
      }
    }
    return $options;
  }
}
