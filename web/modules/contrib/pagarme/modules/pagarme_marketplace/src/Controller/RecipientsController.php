<?php

namespace Drupal\pagarme_marketplace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\pagarme\Helpers\PagarmeUtility;
use Drupal\pagarme\Pagarme\PagarmeSdk;
use Drupal\pagarme_marketplace\Helpers\PagarmeMarketplaceUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RecipientsController.
 *
 * @package Drupal\pagarme_marketplace\Controller
 */
class RecipientsController extends ControllerBase {

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
   * Public Render Method recipient list.
   *
   * @return Return an array for markup render. Example: ['#markup' => $yourMarkup]
   */
  public function recipientList() {
    $destination = $this->getDestinationArray();
    $company = $this->route_match->getParameter('company');
    $config = \Drupal::config('pagarme_marketplace.settings');
    $num_per_page = $config->get('number_items_per_page');

    $query = $this->database->select('pagarme_recipients', 'recipients')->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('recipients')
      ->condition('company', $company)
      ->limit($num_per_page)
      ->orderBy('changed', 'DESC');
    $recipients = $query->execute()->fetchAll();

    $header = [
      'legal_name' => t('Full name / corporate name'),
      'document_number' => t('CPF / CNPJ'),
      'transfer_enabled' => t('Automatic withdrawal'),
      'transfer_interval' => t('Frequency'),
      'transfer_day' => t('Day'),
      'operations' => t('Operations'),
    ];

    $rows = array();
    foreach ($recipients as $recipient) {

      $transfer_enabled = $recipient->transfer_enabled;
      $readable_transfer_interval = '';
      $readable_transfer_day = '';
      if ($transfer_enabled) {
        $transfer_interval = $recipient->transfer_interval;

        $transfer_interval_list = PagarmeUtility::transferInterval();
        $readable_transfer_interval = $transfer_interval_list[$transfer_interval];

        $readable_transfer_day = $transfer_day = $recipient->transfer_day;
        if ($transfer_interval == 'weekly') {
          $weekdays = PagarmeUtility::weekdays();
          $readable_transfer_day = $weekdays[$transfer_day];
        } 
      }
      $rows[$recipient->recipient_id] = [
        'legal_name' => $recipient->legal_name,
        'document_number' => $recipient->document_number,
        'transfer_enabled' => ($transfer_enabled) ? t('SIM') : t('Não'),
        'transfer_interval' => $readable_transfer_interval,
        'transfer_day' => $readable_transfer_day,
      ];

      $links = [];

      $links['edit'] = [
        'title' => t('Edit'),
        'url' => Url::fromRoute(
            'pagarme_marketplace.company_recipients_edit', 
            [
              'op' => 'edit',
              'company' => $company,
              'recipient_id' => $recipient->pagarme_id
            ]
        ),
        'query' => $destination,
      ];

      // $links['delete'] = array(
      //   'title' => t('Archive'),
      //   'url' => Url::fromRoute(
      //       'pagarme_marketplace.company_recipients_delete', 
      //       [
      //         'company' => $company,
      //         'recipient_id' => $recipient->recipient_id
      //       ]
      //   ),
      //   'query' => $destination,
      // );

      $links['balance'] = array(
        'title' => t('Balance'),
        'url' => Url::fromRoute(
            'pagarme_marketplace.company_recipients_balance', 
            [
              'company' => $company,
              'recipient_id' => $recipient->pagarme_id
            ]
        ),
        'query' => $destination,
      );

      $links['transfer'] = array(
        'title' => t('Perform service'),
        'url' => Url::fromRoute(
            'pagarme_marketplace.company_recipients_transfer', 
            [
              'company' => $company,
              'recipient_id' => $recipient->pagarme_id
            ]
        ),
        'query' => $destination,
      );

      $operations = [
        '#theme' => 'links',
        '#links' => $links,
        '#attributes' => array('class' => array('links', 'inline', 'nowrap')),
      ];
      $rows[$recipient->recipient_id]['operations'] = render($operations);
    }

    $build['recipients'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('There are no registered recipients.'),
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  public function balance() {
    $recipient_id = $this->route_match->getParameter('recipient_id');
    $company = $this->route_match->getParameter('company');
    $pagarme_sdk = new PagarmeSdk($company);
    $recipient = $pagarme_sdk->pagarme->recipient()->get($recipient_id);

    $balance = $pagarme_sdk->pagarme->recipient()->balance(
        $recipient
    );

    $header = array(
      t('Amount receivable'),
      t('Available amount'),
      t('Amount already transferred')
    );

    $rows = array();

    $waiting_funds = $balance->getWaitingFunds()->amount;
    $waiting_funds = PagarmeMarketplaceUtility::currencyAmountFormat($waiting_funds, 'integer');
    $rows['data']['waiting_funds'] = $waiting_funds;

    $available = $balance->getAvailable()->amount;
    $available = PagarmeMarketplaceUtility::currencyAmountFormat($available, 'integer');
    $rows['data']['available'] =  $available;

    $transferred = $balance->getTransferred()->amount;
    $transferred = PagarmeMarketplaceUtility::currencyAmountFormat($transferred, 'integer');
    $rows['data']['transferred'] =  $transferred;

    $build['recipient']['balance'] = array(
      '#type' => 'fieldset', 
      '#title' => t('Balance'),
      '#collapsible' => TRUE, 
      '#collapsed' => FALSE,
    );

    $build['recipient']['balance']['info'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
   
    $build['recipient']['account'] = array(
      '#type' => 'fieldset', 
      '#title' => t("Recipient's account information"),
      '#collapsible' => TRUE, 
      '#collapsed' => FALSE,
    );

    $rows = array();
    $rows[] = array(t('NAME/COMPANY NAME'), $recipient->getBankAccount()->getLegalName());
    $rows[] = array(t('Bank'), $recipient->getBankAccount()->getBankCode());
    $rows[] = array(t('CPF/CNPJ'), $recipient->getBankAccount()->getDocumentNumber());
    $rows[] = array(t('Agency'), $recipient->getBankAccount()->getAgencia());
    $rows[] = array(t('BANK ACCOUNT'), $recipient->getBankAccount()->getConta());

    $build['recipient']['account']['info'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
    );

    return $build;
  }

  public function autocomplete(request $request, $company = NULL) {
    $matches = array();
    $string = $request->query->get('q');

    if ($company == 'all') {
      $query = 'SELECT * FROM {pagarme_recipients} WHERE CONCAT(legal_name, document_number) LIKE :string';
      $args = array(':string' => '%' . $string . '%');
    } else {
      $query = 'SELECT * FROM {pagarme_recipients} WHERE CONCAT(legal_name, document_number) LIKE :string AND company = :company';
      $args = array(':string' => '%' . $string . '%', ':company' => $company);
    }

    $result = db_query_range($query, 0, 10, $args);

    foreach ($result as $row) {
      $label = $row->legal_name . ' [' . $row->recipient_id . ']';
      $matches[] = [
        'value' => $label,
        'label' => $label
      ];
    }

    return new JsonResponse($matches);
  }

  public function refreshRecipientsTable($pagarme_sdk) {
    $recipients = $pagarme_sdk->pagarme->recipient()->getList(1, 50);
    foreach ($recipients as $recipient) {
      $balance = $pagarme_sdk->pagarme->recipient()->balance(
        $recipient
      );
      $available = $balance->getAvailable()->amount;
      if ($available == 0) {
        continue;
      }
      $fields = array(
        'pagarme_id' => $recipient->getId(),
        'company' => $pagarme_sdk->getApiKey(),
        'transfer_enabled' => (int) $recipient->getTransferEnabled(),
        'transfer_interval' => $recipient->getTransferInterval(),
        'transfer_day' => (int) $recipient->getTransferDay(),
        'bank_id' => $recipient->getBankAccount()->getId(),
        'bank_code' => $recipient->getBankAccount()->getBankCode(),
        'type' => $recipient->getBankAccount()->getType(),
        'legal_name' => $recipient->getBankAccount()->getLegalName(),
        'document_number' => $recipient->getBankAccount()->getDocumentNumber(),
        'agencia' => $recipient->getBankAccount()->getAgencia(),
        'agencia_dv' => $recipient->getBankAccount()->getAgenciaDv(),
        'conta' => $recipient->getBankAccount()->getConta(),
        'conta_dv' => $recipient->getBankAccount()->getContaDv(),
        'created' => REQUEST_TIME,
        'changed' => REQUEST_TIME,
        'archived' => 0,
      );
      db_merge('pagarme_recipients')
        ->key(array('pagarme_id' => $recipient->getId()))
        ->fields($fields)
        ->execute();
    }
  }
}