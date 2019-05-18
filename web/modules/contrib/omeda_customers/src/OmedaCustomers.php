<?php

namespace Drupal\omeda_customers;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use Drupal\omeda\Omeda;

/**
 * Establishes a connection to Omeda.
 */
class OmedaCustomers {

  /**
   * The omeda_customers.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Omeda service for making API requests.
   *
   * @var \Drupal\omeda\Omeda
   */
  protected $omeda;

  /**
   * Constructs an Omeda Customers object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\omeda\Omeda $omeda
   *   The base Omeda service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger, Omeda $omeda) {
    $this->config = $config_factory->get('omeda_customers.settings');
    $this->logger = $logger;
    $this->omeda = $omeda;
  }

  /**
   * Submit customer information to Omeda for data processing.
   *
   * @param array $data
   *   Prepared data to send to Omeda.
   *
   * @see https://jira.omeda.com/wiki/en/Save_Customer_and_Order_API
   *
   * @return array
   *   The response data from the API.
   *
   * @throws \Exception
   */
  public function saveCustomerAndOrder(array $data) {
    $api_response = $this->omeda->submitRequest('/storecustomerandorder/*', 'POST', $data, TRUE);
    if (isset($api_response['Errors'][0]['Error'])) {
      $this->omeda->handleApiError($api_response['Errors'][0]['Error']);
    }
    else {
      // If immediate execution is enabled, run the processor.
      if ($this->config->get('force_immediate_execution')) {
        if ($transaction_id = $api_response['ResponseInfo'][0]['TransactionId'] ?? NULL) {
          $this->runProcessor($transaction_id);
        }
      }
    }

    return $api_response;
  }

  /**
   * Submit run processor to Omeda for immediate processing.
   *
   * @param array|int $transaction_ids
   *   One or more transaction IDs to be processed.
   *
   * @see https://jira.omeda.com/wiki/en/Run_Processor_API
   *
   * @return array
   *   The response data from the API.
   *
   * @throws \Exception
   */
  public function runProcessor($transaction_ids) {
    if (!is_array($transaction_ids)) {
      $transaction_ids = [$transaction_ids];
    }

    $data = [
      'Process' => [
        [
          'TransactionId' => $transaction_ids,
        ],
      ],
    ];

    $api_response = $this->omeda->submitRequest('/runprocessor/*', 'POST', $data);
    if (isset($api_response['Errors'][0]['Error'])) {
      $this->logger->info('Run processor failed with the following error from the Omeda API: @error', [
        '@error' => $api_response['Errors'][0]['Error'],
      ]);
    }

    return $api_response;
  }

  /**
   * Retrieve a customer record by email address.
   *
   * @param string $email
   *   Email to be looked up.
   * @param bool $load_full_customer
   *   Whether or not to load the full customer details (by default, returns
   *   only the simple response from the lookup-by-email call).
   *
   * @see https://jira.omeda.com/wiki/en/Customer_Lookup_Service_By_Email
   *
   * @return array
   *   The result of the email lookup, or the full details of the customer if
   *   $load_full_customer is TRUE.
   *
   * @throws \Exception
   */
  public function customerLookupByEmail($email, $load_full_customer = FALSE) {
    $api_response = $this->omeda->submitRequest('/customer/email/' . $email . '/*', 'GET');
    if (isset($api_response['Errors'][0]['Error'])) {
      $this->omeda->handleApiError($api_response['Errors'][0]['Error']);
    }
    elseif ($load_full_customer) {
      if ($id = $api_response['Customers'][0]['Id'] ?? NULL) {
        $api_response = $this->customerComprehensiveLookup($id);
      }
    }

    return $api_response;
  }

  /**
   * Retrieve the comprehensive information about a single customer by ID.
   *
   * @param string $id
   *   ID of customer to be looked up.
   *
   * @see https://jira.omeda.com/wiki/en/Customer_Comprehensive_Lookup_Service
   *
   * @return array
   *   The customer details.
   *
   * @throws \Exception
   */
  public function customerComprehensiveLookup($id) {
    $api_response = $this->omeda->submitRequest('/customer/' . $id . '/comp/*', 'GET');
    if (isset($api_response['Errors'][0]['Error'])) {
      $this->omeda->handleApiError($api_response['Errors'][0]['Error']);
    }

    return $api_response;
  }

}
