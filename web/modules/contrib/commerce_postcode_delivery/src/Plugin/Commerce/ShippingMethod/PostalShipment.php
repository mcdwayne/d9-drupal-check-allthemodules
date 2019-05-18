<?php

namespace Drupal\commerce_postcode_delivery\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\commerce_postcode_delivery\CsvManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Postal shipping method.
 *
 * @CommerceShippingMethod(
 *   id = "commerce_postal_delivery",
 *   label = @Translation("Commerce postal delivery"),
 * )
 */
class PostalShipment extends ShippingMethodBase {

  /**
   * The CSV file manager.
   *
   * @var \Drupal\commerce_postcode_delivery\CsvManager
   */
  protected $csvManager;

  /**
   * Constructs a new PostalShipment object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $package_type_manager
   *   The package type manager.
   * @param \Drupal\commerce_postcode_delivery\CsvManager $csvManager
   *   Manage CSV data.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $package_type_manager, CsvManager $csvManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager);

    $this->services['default'] = new ShippingService('default', $this->configuration['rate_label']);
    $this->csvManager = $csvManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.commerce_package_type'),
      $container->get('commerce_postcode_delivery.csv_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'rate_label' => NULL,
      'rate_sheet' => 0,
      'chars_to_match' => 0,
      'send_email_to' => NULL,
      'services' => ['default'],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['rate_label'] = [
      '#type' => 'textfield',
      '#title' => t('Rate label'),
      '#description' => t('Shown to customers during checkout.'),
      '#default_value' => $this->configuration['rate_label'],
      '#required' => TRUE,
    ];
    $form['rate_sheet'] = [
      '#type' => 'managed_file',
      '#title' => t('Rate sheet'),
      '#upload_location' => 'public://postal_shipping',
      '#default_value' => [$this->configuration['rate_sheet']],
      '#multiple' => FALSE,
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#required' => TRUE,
    ];
    $form['chars_to_match'] = [
      '#type' => 'textfield',
      '#title' => t('Chars to match'),
      '#description' => t('Number of first characters to match in postal code from left. Leave 0 to match the whole value from Shipping information.'),
      '#default_value' => $this->configuration['chars_to_match'],
      '#required' => TRUE,
    ];
    $form['send_email_to'] = [
      '#type' => 'textarea',
      '#title' => t('Send notification email to'),
      '#description' => t('Optional. Enter email address for persons who (additionally) need to be notified when an order of this shipping method is placed. For multiple email addresses, separate with a comma.'),
      '#default_value' => $this->configuration['send_email_to'],
    ];

    // Show currently uploaded shipping rates.
    $uploaded_rates = $this->csvManager->getCurrentUploadedRates($this->configuration['rate_sheet']);
    if (!empty($uploaded_rates)) {
      $form['current_rates'] = [
        '#type' => 'details',
        '#description' => t('As per CSV file.'),
        '#title' => t('Uploaded rates'),
      ];
      $form['current_rates']['rates'] = [
        '#markup' => $uploaded_rates,
      ];
    }

    $url = Link::fromTextAndUrl(t('Help'), Url::fromUri('internal:/admin/help/commerce_postcode_delivery'))->toString();
    $form['help_section'] = [
      '#markup' => t('Refer @help for more details.', [
        '@help' => $url,
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $file = $values['rate_sheet'];
    $file = File::load($file[0]);

    $verify = $this->csvManager->validateCsvInputFile($file->getFileUri());
    if (!$verify) {
      $form_state->setErrorByName('rate_sheet', t('Headers are not in the right format. Refer to description for further details.'));
    }

    $chars_to_match = $values['chars_to_match'];
    if (!is_numeric($chars_to_match) || $chars_to_match < 0) {
      $form_state->setErrorByName('chars_to_match', t('Number of characters to match must be an integer value greater than 0.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['rate_label'] = $values['rate_label'];
      $this->configuration['chars_to_match'] = $values['chars_to_match'];

      // Make the rate sheet file status permanent.
      $file = $values['rate_sheet'];
      if (isset($file[0])) {
        $fid = $file[0];
        $rate_sheet = File::load($fid);
        $rate_sheet->setPermanent();
        $rate_sheet->save();

        $file_usage = \Drupal::service('file.usage');
        $file_usage->add($rate_sheet, 'commerce_postcode_delivery', 'file', $fid);
        $this->configuration['rate_sheet'] = $fid;
      }

      // Verify and save email addresses.
      $emails = $values['send_email_to'];
      if (!empty($emails)) {
        $emails = explode(',', $emails);
        $trim_spaces = array_map(function($email_id) {
          return trim($email_id);
        }, $emails);

        // Remove any duplicates.
        $unique_ids = array_unique($trim_spaces, SORT_REGULAR);

        // Verify email address format.
        $verify_email_ids = array_map(function($email_id) {
          return \Drupal::service('email.validator')->isValid($email_id) ? $email_id : 'not_valid';
        }, $unique_ids);

        if (in_array('not_valid', $verify_email_ids)) {
          $form_state->setErrorByName('send_email_to', t('One or more email addresses are not valid'));
        }
        else {
          $this->configuration['send_email_to'] = implode(',', $verify_email_ids);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    $postal_code = '';
    $rate_id = 0;
    $amount = $rates = [];
    $found = FALSE;

    $shipping_address = $shipment->getShippingProfile()->toArray();
    if (isset($shipping_address['address'][0])) {
      $postal_code = $shipping_address['address'][0]['postal_code'];
      $postal_code = strtoupper($postal_code);

      // Get the first few number of characters to match, if given.
      $match_chars = $this->configuration['chars_to_match'];
      if ($match_chars > 0) {
        $postal_code = substr($postal_code, 0, $match_chars);
      }
    }

    if (!empty($postal_code)) {
      // Fetch shipping charges as per the rate sheet.
      $rate_sheet = $this->configuration['rate_sheet'];
      $file = File::load($rate_sheet);
      $rows = $this->csvManager->readCsvInputFile($file->getFileUri());

      foreach ($rows as $row) {
        if ($row['postal_code'] == $postal_code) {
          $amount = [
            'number' => $row['shipping_rate'],
            'currency_code' => $row['currency_code'],
          ];
          $found = TRUE;
          break;
        }
      }
    }

    if ($found) {
      $amount = new Price($amount['number'], $amount['currency_code']);
      $rates[] = new ShippingRate($rate_id, $this->services['default'], $amount);
    }
    else {
      // Get default currency of the store.
      $store_id = $shipment->getOrder()->getStoreId();
      $currency_code = Store::load($store_id)->getDefaultCurrencyCode();
      $amount = new Price('0.01', $currency_code);
      $rates[] = new ShippingRate($rate_id, $this->services['default'], $amount);
    }

    return $rates;
  }

}
