<?php

namespace Drupal\omeda_customers;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Psr\Log\LoggerInterface;
use Drupal\encryption\EncryptionService;

/**
 * Synchronizes user data with Omeda.
 */
class OmedaCustomersSynchronizer {

  /**
   * The omeda_customers.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The country repository service.
   *
   * @var \Drupal\address\Repository\CountryRepository
   */
  protected $countryRepository;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The encryption service.
   *
   * @var \Drupal\encryption\EncryptionService
   */
  protected $encryption;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * API for interacting with Omeda Customer data.
   *
   * @var \Drupal\omeda_customers\OmedaCustomers
   */
  protected $omedaCustomers;

  /**
   * Constructs an OmedaCustomersSynchronizer object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\encryption\EncryptionService $encryption
   *   The encryption service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\omeda_customers\OmedaCustomers $omeda_customers
   *   API for interacting with Omeda Customer data.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter, EncryptionService $encryption, LoggerInterface $logger, ModuleHandlerInterface $module_handler, OmedaCustomers $omeda_customers) {
    $this->config = $config_factory->get('omeda_customers.settings');
    $this->dateFormatter = $date_formatter;
    $this->encryption = $encryption;
    $this->logger = $logger;
    $this->moduleHandler = $module_handler;
    $this->omedaCustomers = $omeda_customers;

    // We can't inject the country repository since the address module is
    // optional.
    if ($this->moduleHandler->moduleExists('address')) {
      $this->countryRepository = \Drupal::service('address.country_repository');
    }
  }

  /**
   * Syncs user data to Omeda if applicable.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object to sync data from.
   */
  public function syncUserToOmeda(UserInterface $user) {
    if (!$this->config->get('user_sync_enabled')) {
      // Syncing is not enabled.
      return;
    }

    if (!array_intersect($user->getRoles(), $this->config->get('roles_to_sync'))) {
      // User does not have any of the roles that are configured to be synced.
      return;
    }

    $sync_data = [];

    $mappings = $this->config->get('field_mappings');

    if ($mappings) {
      foreach ($mappings as $drupal_field_name => $mapping) {
        if ($mapping['sync_enabled']) {
          if ($user->hasField($drupal_field_name)) {
            $value_wrappers = $user->get($drupal_field_name)->getValue() ?? NULL;
            switch ($mapping['omeda_field_type']) {

              case 'address':
                // The address module (and its country repository) are required
                // in order to sync addresses.
                if ($this->countryRepository) {
                  foreach ($value_wrappers as $value_wrapper) {
                    $address = [];
                    $address['AddressContactType'] = $mapping['omeda_contact_type'];
                    if ($value_wrapper['organization']) {
                      $address['Company'] = substr($value_wrapper['organization'], 0, 255);
                    }
                    if ($value_wrapper['address_line1']) {
                      $address['Street'] = substr($value_wrapper['address_line1'], 0, 255);
                    }
                    if ($value_wrapper['address_line2']) {
                      $address['ExtraAddress'] = substr($value_wrapper['address_line2'], 0, 255);
                    }
                    if ($value_wrapper['locality']) {
                      $address['City'] = substr($value_wrapper['locality'], 0, 100);
                    }
                    if ($value_wrapper['administrative_area']) {
                      $address['RegionCode'] = $value_wrapper['administrative_area'];
                    }
                    if ($value_wrapper['postal_code']) {
                      $address['PostalCode'] = $value_wrapper['postal_code'];
                    }
                    if ($value_wrapper['country_code']) {
                      if ($country = $this->countryRepository->get($value_wrapper['country_code'])) {
                        if ($country_three_letter_code = $country->getThreeLetterCode()) {
                          $address['CountryCode'] = $country_three_letter_code;
                        }
                        elseif ($country_name = $country->getThreeLetterCode()) {
                          $address['Country'] = substr($country_name, 0, 100);
                        }
                        else {
                          $address['Country'] = substr($value_wrapper['country_code'], 0, 100);
                        }
                      }
                      else {
                        $address['Country'] = substr($value_wrapper['country_code'], 0, 100);
                      }
                    }
                    $sync_data['Addresses'][] = $address;
                  }
                }
                break;

              case 'base':
                if ($value = $value_wrappers[0]['value'] ?? NULL) {
                  $char_limit_100 = [
                    'FirstName',
                    'MiddleName',
                    'LastName',
                    'Title',
                  ];
                  $char_limit_10 = ['Salutation', 'Suffix'];
                  if (in_array($mapping['omeda_field'], $char_limit_100)) {
                    $sync_data[$mapping['omeda_field']] = substr($value, 0, 100);
                  }
                  elseif (in_array($mapping['omeda_field'], $char_limit_10)) {
                    $sync_data[$mapping['omeda_field']] = substr($value, 0, 10);
                  }
                  elseif ($mapping['omeda_field'] === 'Gender') {
                    $gender_code = strtoupper(substr($value, 0, 1));
                    if (in_array($gender_code, ['M', 'F'])) {
                      $sync_data['Gender'] = $gender_code;
                    }
                  }
                  elseif ($mapping['omeda_field'] === 'SignupDate') {
                    if ($formatted_date = $this->dateFormatter->format($value, 'custom', 'Y-m-d H:i')) {
                      $sync_data['SignupDate'] = $formatted_date;
                    }
                  }
                }
                break;

              case 'demographic':
                $omeda_values = [];
                foreach ($value_wrappers as $value_wrapper) {
                  if ($value = $value_wrapper['value'] ?? $value_wrapper['target_id'] ?? NULL) {
                    foreach ($mapping['omeda_demographic_value_mapping'] as $demographic_values) {
                      if ($value == $demographic_values['drupal']) {
                        $omeda_values[] = $demographic_values['omeda'];
                      }
                    }
                  }
                }

                if (count($omeda_values)) {
                  $sync_data['CustomerDemographics'][] = [
                    'OmedaDemographicId' => $mapping['omeda_demographic_field'],
                    'OmedaDemographicValue' => $omeda_values,
                  ];
                }
                break;

              case 'email':
                foreach ($value_wrappers as $value_wrapper) {
                  if ($value = $value_wrapper['value'] ?? NULL) {
                    $sync_data['Emails'][] = [
                      'EmailContactType' => $mapping['omeda_contact_type'],
                      'EmailAddress' => $value,
                    ];
                  }
                }
                break;

              case 'phone':
                foreach ($value_wrappers as $value_wrapper) {
                  if ($value = $value_wrapper['value'] ?? NULL) {
                    $sync_data['Phones'][] = [
                      'PhoneContactType' => $mapping['omeda_contact_type'],
                      'Number' => $value,
                    ];
                  }
                }
                break;

              default:
                break;
            }
          }
        }
      }
    }

    if ($encrypted_external_customer_id_namespace = $this->config->get('external_customer_id_namespace')) {
      if ($external_customer_id_namespace = $this->encryption->decrypt($encrypted_external_customer_id_namespace, TRUE)) {
        $sync_data['ExternalCustomerIdNamespace'] = $external_customer_id_namespace;
        $sync_data['ExternalCustomerId'] = $user->uuid();
      }
      else {
        $this->logger->error('Failed to decrypt the configured external customer ID namespace. Please ensure that the Encryption module is enabled and that an encryption key is set.');
      }
    }
    elseif ($mappings && isset($mappings['mail']['sync_enabled']) && $mappings['mail']['sync_enabled']) {
      // If we aren't mapping by external customer ID, we'll try to map by email
      // address instead. If the user is being updated, we'll use the previous
      // email address rather than the new one in order to hopefully find
      // the correct existing customer in Omeda.
      $email = $user->getEmail();
      if (isset($user->original) && $user->original->getEmail()) {
        $email = $user->original->getEmail();
      }

      try {
        $customer_lookup = $this->omedaCustomers->customerLookupByEmail($email);
        if ($customer_id = $customer_lookup['Customers'][0]['Id'] ?? NULL) {
          $sync_data['OmedaCustomerId'] = $customer_id;
        }
      }
      catch (\Exception $e) {
        // Most errors here are just that there wasn't a matching address,
        // which we can ignore at this point.
      }
    }

    $this->moduleHandler->alter('omeda_customer_data', $sync_data, $user);

    try {
      $this->omedaCustomers->saveCustomerAndOrder($sync_data);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to sync data to Omeda about user @uid.', [
        '@uid' => $user->id(),
      ]);
    }
  }

}
