<?php

namespace Drupal\ji_quickbooks;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_shipping\Entity\Shipment;
use Drupal\Component\Utility\Html;
use Drupal\user\Entity\User;
use QuickBooksOnline\API\Data\IPPCustomer;
use QuickBooksOnline\API\Data\IPPEmailAddress;
use QuickBooksOnline\API\Data\IPPInvoice;
use QuickBooksOnline\API\Data\IPPLine;
use QuickBooksOnline\API\Data\IPPLinkedTxn;
use QuickBooksOnline\API\Data\IPPMemoRef;
use QuickBooksOnline\API\Data\IPPPayment;
use QuickBooksOnline\API\Data\IPPPhysicalAddress;
use QuickBooksOnline\API\Data\IPPTaxRate;
use QuickBooksOnline\API\Data\IPPReferenceType;
use QuickBooksOnline\API\Data\IPPSalesItemLineDetail;
use QuickBooksOnline\API\Data\IPPDiscountLineDetail;
use QuickBooksOnline\API\Data\IPPTelephoneNumber;
use QuickBooksOnline\API\Data\IPPTxnTaxDetail;
use QuickBooksOnline\API\Data\IPPWebSiteAddress;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Item;

require_once JIQuickBooksSupport::getLibraryPath() . '/src/config.php';

/**
 * JIQuickbooksService - provides access to QBO.
 */
class JIQuickBooksService {

  public static $faultValidation = 0;

  public static $faultSevere = 1;

  public static $faultUncaught = 2;

  /**
   * Service stype.
   *
   * @var string
   */
  public $serviceType;

  /**
   * Realm Id.
   *
   * @var string
   */
  public $realmId;

  /**
   * Service Context.
   *
   * @var \ServiceContext
   */
  public $serviceContext;

  /**
   * Data Service.
   *
   * @var DataService
   */
  public $dataService;

  /**
   * Saves error message if QBO settings cannot be parsed.
   *
   * @var string
   */
  public $settingErrorMessage;

  /**
   * Setup QuickBooks using the config page.
   *
   * @param bool $email_error
   *   TRUE sends email if error occurs.
   */
  public function __construct($email_error = TRUE) {
    try {
      $this->realmId = \Drupal::state()->get('ji_quickbooks_settings_realm_id');
      // If the user has disconnected, return NULL so we don't attempt to send
      // Data to QBO.
      if (empty($this->realmId) ||
        empty(\Drupal::state()->get('ji_quickbooks_settings_access_token')) ||
        empty(\Drupal::state()->get('ji_quickbooks_settings_refresh_token'))) {

        $this->settingErrorMessage = t("JI QuickBooks was disconnected from your QuickBooks Online account. Please try to reconnect.");
        \Drupal::logger('JI QuickBooks')->error($this->settingErrorMessage);

        if ($email_error) {
          $this->sendErrorNoticeEmail($this->settingErrorMessage);
        }

        // Clear access token.
        \Drupal::state()->delete('ji_quickbooks_settings_access_token');
        \Drupal::state()->delete('ji_quickbooks_settings_refresh_token');
        \Drupal::state()->delete('ji_quickbooks_settings_realm_id');
        return;
      }

      $env = \Drupal::state()->get('ji_quickbooks_settings_environment', 'dev');
      if ($env === 'dev') {
        $client_id = 'Q0n1LhpCzb1F9qEOBzbVoeplljET6WH2SvhQ6emi5uzplQEKIs';
        $client_secret = 'rn1eE4MYK6H4ILUxlNjb3J7cds9n4qRnkzgqnVBz';
        $base_url = 'development';
      }
      else {
        $client_id = 'Q0J1HKS0IcbfIzG0arOF7YES5iGYRquyiXkH79teciN8Z8RgZb';
        $client_secret = 'yIVaSxCl9kpfDJ93LqzrOKIval6MMuyZP5O04qJV';
        $base_url = 'production';
      }

      $access_token = \Drupal::state()
        ->get('ji_quickbooks_settings_access_token');
      $refresh_token = \Drupal::state()
        ->get('ji_quickbooks_settings_refresh_token');
      $realm_id = \Drupal::state()
        ->get('ji_quickbooks_settings_realm_id');

      $this->dataService = DataService::Configure([
        'auth_mode' => 'oauth2',
        'ClientID' => $client_id,
        'ClientSecret' => $client_secret,
        'accessTokenKey' => $access_token,
        'refreshTokenKey' => $refresh_token,
        'QBORealmID' => $realm_id,
        'baseUrl' => $base_url,
      ]);

      $access_expires_on = \Drupal::state()
        ->get('ji_quickbooks_qbo_access_token_expires_on', '');

      //      $refresh_expires_on = \Drupal::state()
      //        ->get('ji_quickbooks_qbo_refresh_token_expires_on', '');

      if (empty($access_expires_on)) {
        $this->renewTokens();
      }
      else {
        // We attempt to renew the tokens before they expire so
        // we subtract even more time.
        if ($access_expires_on < time() - 1000) {
          $this->renewTokens();
        }
      }

      if (!$this->dataService) {
        $this->settingErrorMessage = t("Problem while initializing DataService.");
        \Drupal::logger('JI QuickBooks')->error($this->settingErrorMessage);
        if ($email_error) {
          $this->sendErrorNoticeEmail($this->settingErrorMessage);
        }
      }
    } catch (\Exception $e) {
      watchdog_exception('JIQuickBooksService __construct', $e);
    }
  }

  /**
   * Store our new tokens.
   *
   * @throws \QuickBooksOnline\API\Exception\SdkException
   * @throws \QuickBooksOnline\API\Exception\ServiceException
   */
  private function renewTokens() {
    $OAuth2LoginHelper = $this->dataService->getOAuth2LoginHelper();
    $access_token = $OAuth2LoginHelper->refreshToken();
    $refresh_token = $OAuth2LoginHelper->getAccessToken()
      ->getRefreshToken();
    $this->dataService->updateOAuth2Token($access_token);

    $access_token_expires_on = $access_token->getAccessTokenExpiresAt();
    $expires_on = strtotime($access_token_expires_on);
    \Drupal::state()
      ->set('ji_quickbooks_qbo_access_token_expires_on', $expires_on);

    $refresh_token_expires_on = $access_token->getRefreshTokenExpiresAt();
    $expires_on = strtotime($refresh_token_expires_on);
    \Drupal::state()
      ->set('ji_quickbooks_qbo_refresh_token_expires_on', $expires_on);

    // Save our newest tokens for use with the next connection.
    \Drupal::state()
      ->set('ji_quickbooks_settings_access_token', $access_token->getAccessToken());
    \Drupal::state()
      ->set('ji_quickbooks_settings_refresh_token', $access_token->getRefreshToken());
  }

  /**
   * Send admin an email.
   */
  public function sendErrorNoticeEmail($message) {
    if ($message === '') {
      return;
    }
  }

  /**
   * Disconnect QBO keys from session.
   */
  public function oauthDisconnect() {
    // Clear access token.
    \Drupal::state()->delete('ji_quickbooks_settings_access_token');
    \Drupal::state()->delete('ji_quickbooks_settings_refresh_token');
    \Drupal::state()->delete('ji_quickbooks_settings_realm_id');
  }

  /**
   * Attempt to reconnect to QuickBooks.
   *
   * Returns an error code in ErrorCode or, returns ErrorCode 0
   * with OAuthToken and OAuthTokenSecret if reconnect was successful.
   */
  public function oauthRenew() {
    $platformService = new \PlatformService($this->serviceContext);
    return $platformService->Reconnect();
  }

  /**
   * Send customer information when checkout completes.
   */
  public function sendCustomer(Order $order, User $account) {
    $qbo_id = JIQuickBooksSupport::getResponseId($this->realmId, $order->get('uid')
      ->getString());

    // An anonymous user is placing an order.
    if ($order->get('uid')->getString() == 0) {
      $account_data = [
        'username' => 'Anonymous',
        'email' => '',
        'joined' => 'Anonymous',
      ];
    }
    else {
      $account_data = [
        'username' => $account->getUsername(),
        'email' => $account->getEmail(),
        'joined' => $account->getUsername() . ' ' . $account->getEmail(),
      ];
    }

    $customer_data = [
      // Id is a required field.
      // Setting 'Id' to '' tells QBO this is a new customer.
      'Id' => $qbo_id,
      // DisplayName is a required field.
      'DisplayName' => $account_data['joined'],
      'CompanyName' => '',
      'Title' => '',
      'GivenName' => '',
      'MiddleName' => '',
      'FamilyName' => '',
      'Notes' => '',
      'PrimaryPhone' => '',
      'Fax' => '',
      'Mobile' => '',
      'Email' => $account_data['email'],
      'Website' => '',
      'BillAddressStreet1' => '',
      'BillAddressStreet2' => '',
      'BillAddressCity' => '',
      'BillAddressCountrySubDivisionCode' => '',
      'BillAddressPostalCode' => '',
      'BillAddressCountry' => '',
      'ShipAddressStreet1' => '',
      'ShipAddressStreet2' => '',
      'ShipAddressCity' => '',
      'ShipAddressCountrySubDivisionCode' => '',
      'ShipAddressPostalCode' => '',
      'ShipAddressCountry' => '',
    ];

    $response = $this->processCustomer($customer_data);

    return JIQuickBooksSupport::logProcess($order->getOrderNumber(), $this->realmId, $order->get('uid')
      ->getString(), 'customer', $response);
  }

  /**
   * Adds or updates customer information.
   *
   * $customer_data array that maps to IPPCustomer.
   * Mapping is useful to help prevent creating multiple
   * functions when form submission or object submission is used.
   */
  private function processCustomer(array $customer_data = []) {
    $o_customer = new IPPCustomer();

    // Not new, retrieve QBO Id and token so Add()
    // updates rather than inserts.
    if (!empty($customer_data['Id'])) {
      // Quickbooks requires we retrieve a SyncToken
      // first, if we're going to update.
      $customer_response = $this->getCustomerById($customer_data['Id']);

      $o_customer->Id = $customer_data['Id'];
      $o_customer->SyncToken = $customer_response->SyncToken;
    }

    $o_customer->sparse = TRUE;
    $o_customer->Title = $customer_data['Title'];
    $o_customer->GivenName = $customer_data['GivenName'];
    $o_customer->MiddleName = $customer_data['MiddleName'];
    $o_customer->FamilyName = $customer_data['FamilyName'];
    // Was missing. Needed?
    // $oCustomer->Suffix = $customer_data['Suffix']; save.
    $o_customer->FullyQualifiedName = $o_customer->Title . ' ' . $o_customer->GivenName . ' ' . $o_customer->MiddleName . ' ' . $o_customer->FamilyName;

    $o_customer->CompanyName = $customer_data['CompanyName'];
    $o_customer->DisplayName = $customer_data['DisplayName'];
    $o_customer->PrintOnCheckName = $o_customer->FullyQualifiedName;

    $o_customer->Notes = $customer_data['Notes'];

    $o_primary_phone = new IPPTelephoneNumber();
    $o_primary_phone->FreeFormNumber = $customer_data['PrimaryPhone'];
    $o_customer->PrimaryPhone = $o_primary_phone;

    if (!empty($customer_data['Fax'])) {
      $o_fax = new IPPTelephoneNumber();
      $o_fax->FreeFormNumber = $customer_data['Fax'];
      $o_customer->Fax = $o_fax;
    }

    if (!empty($customer_data['Mobile'])) {
      $o_mobile = new IPPTelephoneNumber();
      $o_mobile->FreeFormNumber = $customer_data['Mobile'];
      $o_customer->Mobile = $o_mobile;
    }

    if (!empty($customer_data['Email'])) {
      $o_primary_email_addr = new IPPEmailAddress();
      $o_primary_email_addr->Address = $customer_data['Email'];
      $o_customer->PrimaryEmailAddr = $o_primary_email_addr;
    }
    if (!empty($customer_data['Website'])) {
      $o_web_addr = new IPPWebSiteAddress();
      $o_web_addr->URI = $customer_data['Website'];
      $o_customer->WebAddr = $o_web_addr;
    }

    $o_bill_address = new IPPPhysicalAddress();
    $o_bill_address->Line1 = $customer_data['BillAddressStreet1'];
    $o_bill_address->Line2 = $customer_data['BillAddressStreet2'];
    $o_bill_address->City = $customer_data['BillAddressCity'];
    $o_bill_address->CountrySubDivisionCode = $customer_data['BillAddressCountrySubDivisionCode'];
    $o_bill_address->PostalCode = $customer_data['BillAddressPostalCode'];
    $o_bill_address->Country = $customer_data['BillAddressCountry'];
    // $oBillAddress->Lat = $request->BillAddr->Lat; save.
    // $oBillAddress->Long = $request->BillAddr->Long; save.
    $o_customer->BillAddr = $o_bill_address;

    $o_ship_address = new IPPPhysicalAddress();
    $o_ship_address->Line1 = $customer_data['ShipAddressStreet1'];
    $o_ship_address->Line2 = $customer_data['ShipAddressStreet2'];
    $o_ship_address->City = $customer_data['ShipAddressCity'];
    $o_ship_address->CountrySubDivisionCode = $customer_data['ShipAddressCountrySubDivisionCode'];
    $o_ship_address->PostalCode = $customer_data['ShipAddressPostalCode'];
    $o_ship_address->Country = $customer_data['ShipAddressCountry'];
    // $oShipAddress->Lat = $request->ShipAddr->Lat; save.
    // $oShipAddress->Long = $request->ShipAddr->Long; save.
    $o_customer->ShipAddr = $o_ship_address;

    $response = [];
    // This may insert or update.
    $response['response'] = $this->dataService->Add($o_customer);
    $response['error'] = $this->checkErrors();
    return $response;
  }

  /**
   * Query QBO to GetCustomerById.
   */
  public function getCustomerById($id) {
    return $this->dataService->FindById(new IPPCustomer(['Id' => $id], TRUE));
  }

  /**
   * Returns any errors returned by the QBO API.
   *
   * Used during form submission/validation. Will stop a
   * form from successfully adding or updating data in
   * Drupal if QuickBooks returns an error, if needed. Displays
   * error message as well.
   *
   * @param object $response
   *   QBO object or NULL if nothing was returned.
   */
  public function checkErrors($response = '') {
    $return_value = [
      'message' => NULL,
      'code' => NULL,
    ];
    if (is_null($response)) {
      return [
        'message' => 'Nothing was found.',
        'code' => 0,
      ];
    }

    $error = $this->dataService->getLastError();
    if ($error) {
      $response_xml_obj = new \SimpleXMLElement($error->getResponseBody());
      foreach ($response_xml_obj->Fault->children() as $fault) {
        $fault_array = get_object_vars($fault);
        $type = isset($fault_array['@attributes']['type']) ? $fault_array['@attributes']['type'] : '';
        $code = $fault_array['@attributes']['code'];
        // Save $element = $fault_array['@attributes']['element'];.
        $message = $fault_array['Message'];
        // Save $detail = $fault_array['Detail'];.
      }

      // Severe errors do not stop form execution, validation ones do.
      // If the error is "severe" then a configuration error must have
      // occurred and an admin must address it (email is sent).
      switch ($code) {
        case 100:
          $message = "QuickBooks said: Error 100. Please verify your RealmID within configuration page - is it pointing to the correct company?";

          if (\Drupal::currentUser()->hasPermission('access quickbooks')) {
            \Drupal::messenger()->addError($message, FALSE);
          }

          $this->sendErrorNoticeEmail($message);

          $return_value = [
            'message' => $message,
            'code' => self::$faultSevere,
          ];
          break;

        case 3100:
        case 3200:
          $message = "QuickBooks said: Error $code ApplicationAuthenticationFailed. Your QBO access tokens may have expired.";

          // Remove our realm_id since our connection needs a reset.
          //\Drupal::state()->delete('ji_quickbooks_settings_realm_id');

          if (\Drupal::currentUser()->hasPermission('access quickbooks')) {
            \Drupal::messenger()->addError($message, FALSE);
          }

          $this->sendErrorNoticeEmail($message);

          $return_value = [
            'message' => $message,
            'code' => self::$faultSevere,
          ];
          break;

        default:
          // Generally, a ValidationFault is an error in customer input.
          if ($type === 'ValidationFault') {
            // Commerce forms use their own special validation.
            $moduleHandler = \Drupal::service('module_handler');
            if ($moduleHandler->moduleExists('commerce')) {
              \Drupal::messenger()->addError($message, FALSE);
            }

            $return_value = [
              'message' => $message,
              'code' => self::$faultValidation,
            ];
          }
          else {
            $return_value = [
              'message' => $message,
              'code' => self::$faultUncaught,
            ];
          }
          break;
      }
    }
    return $return_value;
  }

  /**
   * Send invoice data to QuickBooks when the checkout process is completed.
   */
  public function sendInvoice(Order $order, $qbo_customer_id) {
    $customer_type = new IPPReferenceType();
    $customer_type->value = $qbo_customer_id;
    $sales_term_type = new IPPReferenceType();
    // QBO Invoice due terms.
    $sales_term_type->value = \Drupal::state()->get('ji_quickbooks_term');
    $email_address = new IPPEmailAddress();
    $email_address->Address = $order->getEmail();

    // We assume tax id is NULL until code says otherwise. QuickBooks
    // won't complain if no tax code is used.
    $qbo_tax_id = NULL;
    $line_items = [];
    $promotions = [];
    $items = $order->getItems();
    foreach ($items as $item) {
      $line = new IPPLine();
      $line->Description = $item->getTitle();
      // QuickBooks requires this.
      $line->Amount = $item->getTotalPrice()->getNumber();
      $line->DetailType = 'SalesItemLineDetail';

      $sales_item_line_detail = new IPPSalesItemLineDetail();
      // Because SalesItemLineDetail is selected above, we don't need the date.
      // $salesItemLineDetail->ServiceDate = date("Y-m-d"); save.
      $item_ref_type = new IPPReferenceType();

      $product = ProductVariation::load($item->get('purchased_entity')
        ->getString());

      // We need a product or else no need to process an invoice.
      if (!isset($product)) {
        continue;
      }

      // Product ID.
      $item_ref_type->value = $this->sendCommerceProduct($product);
      $sales_item_line_detail->ItemRef = $item_ref_type;
      $sales_item_line_detail->UnitPrice = $item->getUnitPrice()
        ->getNumber();
      $sales_item_line_detail->Qty = $item->getQuantity();
      $tax_code_ref_type = new IPPReferenceType();

      $adjustments = $item->getAdjustments();
      if (count($adjustments)) {
        /** @var \Drupal\commerce_order\Adjustment $adjustment */
        foreach ($adjustments as $adjustment) {
          switch ($adjustment->getType()) {
            case 'tax':
              // A string such as "quickbooks_tax_id_7|default|9".
              $qbo_tax_id = $this->extractTaxId($adjustment->getSourceId());
              break;

            case 'promotion':
              // Don't allow more than one promotion since QBO
              // will complain.
              if (count($promotions)) {
                break;
              }

              $percentage = $adjustment->getPercentage();
              // If our promotion is a percentage base, else it's an assigned number.
              if (isset($percentage)) {
                $promotion = new IPPLine();
                $promotion->DetailType = 'DiscountLineDetail';
                $discount_line_detail = new IPPDiscountLineDetail();
                $discount_account = \Drupal::state()
                  ->get('ji_quickbooks_discount_account');
                $discount_line_detail->DiscountAccountRef = $discount_account;
                $discount_line_detail->DiscountPercent = $adjustment->getPercentage() * 100;
                $discount_line_detail->PercentBased = 'true';
                $promotion->DiscountLineDetail = $discount_line_detail;
                $promotions[] = $promotion;
              }
              else {
                /** @var \Drupal\commerce_price\Price $amount */
                $amount = $adjustment->getAmount();
                $promotion = new IPPLine();
                $promotion->Amount = abs($amount->getNumber());
                $promotion->DetailType = 'DiscountLineDetail';

                $discount_line_detail = new IPPDiscountLineDetail();
                $discount_account = \Drupal::state()
                  ->get('ji_quickbooks_discount_account');
                $discount_line_detail->DiscountAccountRef = $discount_account;
                // Make sure we return a positive number. QBO knows to subtract this
                // amount.
                $discount_line_detail->PercentBased = 'false';
                $promotion->DiscountLineDetail = $discount_line_detail;
                $promotions[] = $promotion;
              }
              break;

            case 'shipping':
              break;
          }
        }
      }

      // 'NON' for non-taxable or 'TAX' for taxable.
      if (is_null($qbo_tax_id)) {
        $tax_code_ref_type->value = 'NON';
      }
      else {
        $tax_code_ref_type->value = 'TAX';
      }

      $sales_item_line_detail->TaxCodeRef = $tax_code_ref_type;
      $line->SalesItemLineDetail = $sales_item_line_detail;

      // Add our product to our product array.
      $line_items[] = $line;
    }

    // Don't continue if we don't have any line items.
    if (count($line_items) == 0) {
      return NULL;
    }

    $order_adjustments = $order->getAdjustments();
    foreach ($order_adjustments as $order_adjustment) {
      $type = $order_adjustment->getType();
      switch ($type) {
        case 'tax':
          // A string such as "quickbooks_tax_id_7|default|9".
          $qbo_tax_id = $this->extractTaxId($order_adjustment->getSourceId());
          break;

        case 'promotion':
          // Don't allow more than one promotion since QBO will complain.
          if (count($promotions)) {
            break;
          }

          $percentage = $order_adjustment->getPercentage();
          // If our promotion is a percentage base, else it's an assigned number.
          if (isset($percentage)) {
            $promotion = new IPPLine();
            $promotion->DetailType = 'DiscountLineDetail';

            $discount_line_detail = new IPPDiscountLineDetail();
            $discount_account = \Drupal::state()
              ->get('ji_quickbooks_discount_account');
            $discount_line_detail->DiscountAccountRef = $discount_account;
            $discount_line_detail->DiscountPercent = $order_adjustment->getPercentage() * 100;
            $discount_line_detail->PercentBased = 'true';
            $promotion->DiscountLineDetail = $discount_line_detail;

            // Add our product to our product array.
            $promotions[] = $promotion;
          }
          else {
            /** @var \Drupal\commerce_price\Price $amount */
            $amount = $order_adjustment->getAmount();
            $line = new IPPLine();
            $line->Amount = abs($amount->getNumber());
            $line->DetailType = 'DiscountLineDetail';

            $discount_line_detail = new IPPDiscountLineDetail();
            $discount_account = \Drupal::state()
              ->get('ji_quickbooks_discount_account');
            $discount_line_detail->DiscountAccountRef = $discount_account;
            // Make sure we return a positive number. QBO knows to subtract this
            // amount.
            $discount_line_detail->PercentBased = 'false';
            $line->DiscountLineDetail = $discount_line_detail;

            // Add our product to our product array.
            $line_items[] = $line;
          }

          break;

        case 'shipping':
          $shipping_field = \Drupal::state()
            ->get('ji_quickbooks_config_qbo_preferences_shipping_field');
          // Is our shipping field enabled?
          if ($shipping_field) {
            $shipping_amount = $order_adjustment->getAmount();

            $line = new IPPLine();
            // QuickBooks requires this.
            $line->Amount = $shipping_amount->getNumber();
            $line->DetailType = 'SalesItemLineDetail';

            $sales_item_line_detail = new IPPSalesItemLineDetail();
            $sales_item_line_detail->ItemRef = 'SHIPPING_ITEM_ID';
            $line->SalesItemLineDetail = $sales_item_line_detail;

            // Add our product to our product array.
            $line_items[] = $line;
          }
          break;
      }
    }

    // Add the discount/promotion line item.
    if (count($promotions)) {
      $line_items[] = $promotions[0];
    }

    $txn_detail = new IPPTxnTaxDetail();
    $txn_code_ref_type = new IPPReferenceType();
    // TAX ID.
    $txn_code_ref_type->value = $qbo_tax_id;
    $txn_detail->TxnTaxCodeRef = $txn_code_ref_type;

    $o_bill_address = new IPPPhysicalAddress();
    $billing_profile = $order->getBillingProfile();
    if (isset($billing_profile)) {
      $billing = $order->getBillingProfile()->get('address')->first();
      $o_bill_address->Line1 = $billing->get('address_line1')->getString();
      $o_bill_address->Line2 = $billing->get('address_line2')->getString();
      $o_bill_address->City = $billing->get('locality')->getString();
      $o_bill_address->CountrySubDivisionCode = $billing->get('administrative_area')
        ->getString();
      $o_bill_address->PostalCode = $billing->get('postal_code')->getString();
      $o_bill_address->Country = $billing->get('country_code')->getString();
    }
    $transaction_timestamp = $order->getCreatedTime();
    $date = date("Y-m-d", $transaction_timestamp);

    $invoice_data = [
      'AllowIPNPayment' => 1,
      'AllowOnlinePayment' => 1,
      'AllowOnlineCreditCardPayment' => 1,
      'AllowOnlineACHPayment' => 1,
      'CustomerRef' => $customer_type,
      'SalesTermRef' => $sales_term_type,
      'BillEmail' => $email_address,
      'TxnDate' => $date,
      // You will generate an error if you put null value down here.
      'Line' => $line_items,
      'TxnTaxDetail' => $txn_detail,
      // Commerce will email invoice.
      //'EmailInvoice' => FALSE,
      'CustomerMemo' => 'Web order #' . $order->getOrderNumber() . '.',
    ];

    $id = $this->getInvoiceId($order);
    if (isset($id)) {
      $invoice_data['Id'] = $id;
    }

    // We won't add empty addresses.
    if (!empty($o_bill_address->Line1) &&
      !empty($o_bill_address->City) &&
      !empty($o_bill_address->CountrySubDivisionCode) &&
      !empty($o_bill_address->PostalCode) &&
      !empty($o_bill_address->Country)) {
      $invoice_data['BillAddr'] = $o_bill_address;
    }

    $response = $this->processInvoice($invoice_data);

    return JIQuickBooksSupport::logProcess($order->getOrderNumber(), $this->realmId, $order->get('uid')
      ->getString(), 'invoice', $response);
  }

  private function extractTaxId($tax_id) {
    $qbo_tax_id = NULL;
    $qbo_tax_id = $this->stripQuickBooksId($tax_id);
    if (isset($qbo_tax_id)) {
      // Does this ID actually exist? This can happen if a user switched
      // companies and there's tax ID's which were synced prior.
      $query = \Drupal::entityQuery('commerce_tax_type');
      $results = $query->execute();
      if ($results) {
        // We didn't find it, this must be this order was synced via
        // a different company or realm.
        if (!isset($qbo_tax_id[$tax_id])) {
          $qbo_tax_id = NULL;
        }
      }
    }

    // If we haven't found a suitable tax id, try once more.
    if (!isset($qbo_tax_id)) {
      $current_uri = \Drupal::request()->getRequestUri();
      // We're executing this from the batch admin screens, get a default.
      if (strpos($current_uri, 'batch?id') !== FALSE) {
        $qbo_tax_id = $this->stripQuickBooksId(\Drupal::state()
          ->get('ji_quickbooks_default_tax_for_old_orders', 0));
      }
    }

    return $qbo_tax_id;
  }

  private function stripQuickBooksId($tax_id) {
    $qbo_tax_id = NULL;
    $source = explode('|', $tax_id);
    $tax_machine_name = explode('_', $source[0]);
    $length = count($tax_machine_name);
    if (isset($tax_machine_name[$length - 1])) {
      if (is_numeric($tax_machine_name[$length - 1])) {
        $qbo_tax_id = $tax_machine_name[$length - 1];
      }
    }

    return $qbo_tax_id;
  }

  private function prepareAdjustments($adjustment, &$line_item) {
    // Don't allow more than one promotion since QBO
    // will complain.
    //    if (count($_adjustments)) {
    //      return;
    //    }

    $percentage = $adjustment->getPercentage();
    // If our promotion is a percentage base, else it's an assigned number.
    if (isset($percentage)) {
      $promotion = new IPPLine();
      $promotion->DetailType = 'DiscountLineDetail';
      $discount_line_detail = new IPPDiscountLineDetail();
      $discount_account = \Drupal::state()
        ->get('ji_quickbooks_discount_account');
      $discount_line_detail->DiscountAccountRef = $discount_account;
      $discount_line_detail->DiscountPercent = $adjustment->getPercentage() * 100;
      $discount_line_detail->PercentBased = 'true';
      $promotion->DiscountLineDetail = $discount_line_detail;
      $line_item[] = $promotion;
    }
    else {
      /** @var \Drupal\commerce_price\Price $amount */
      $amount = $adjustment->getAmount();
      $promotion = new IPPLine();
      $promotion->Amount = abs($amount->getNumber());
      $promotion->DetailType = 'DiscountLineDetail';

      $discount_line_detail = new IPPDiscountLineDetail();
      $discount_account = \Drupal::state()
        ->get('ji_quickbooks_discount_account');
      $discount_line_detail->DiscountAccountRef = $discount_account;
      // Make sure we return a positive number. QBO knows to subtract this
      // amount.
      $discount_line_detail->PercentBased = 'false';
      $promotion->DiscountLineDetail = $discount_line_detail;
      $line_item[] = $promotion;
    }
  }

  private function getInvoiceId(Order $order) {
    $connection = \Drupal::database();
    $query = $connection->query("SELECT response_id FROM {ji_quickbooks_realm_index} where oid = :oid and realm_id = :realm_id and process = 'invoice'",
      [
        'oid' => $order->getOrderNumber(),
        'realm_id' => $this->realmId,
      ]);
    $result = $query->fetchAll();
    if (isset($result[0])) {
      if ($result[0]->response_id != 0) {
        return $result[0]->response_id;
      }
    }

    return NULL;
  }

  private function retrieveRealmAndProductId($qbo_product_id_field) {
    $qbo_product_id_pairs = [];
    if (isset($qbo_product_id_field)) {
      // Create a list from our string of realm_id:product_id field.
      $qbo_product_id_field_array = explode(',', $qbo_product_id_field);
      foreach ($qbo_product_id_field_array as $pair) {
        list ($k, $v) = explode(':', $pair);
        $qbo_product_id_pairs[$k] = $v;
      }
    }

    return $qbo_product_id_pairs;
  }

  /**
   * Send our Commerce product data to QuickBooks.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariation $product
   *
   * @return string $response
   *   Returns the QBO product ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function sendCommerceProduct(ProductVariation $product, $save_product = FALSE) {
    $qbo_product_id_field = $product->get('field_qbo_product_id')->value;
    $qbo_product_id_pairs = $this->retrieveRealmAndProductId($qbo_product_id_field);

    // Since product hasn't synced yet, let's look up the SKU, if
    // allowed to within the admin UI.
    if (!isset($qbo_product_id_pairs[$this->realmId])) {
      $this->queryProductByField($product, $qbo_product_id_pairs);
    }

    $transaction_timestamp = $product->getCreatedTime();
    // One year from today.
    $date = date("Y-m-d", $transaction_timestamp - 26956800);

    $product_data = [
      "Name" => $this->validate_text($product->getTitle() . ' - ' . $product->getSku()),
      "Sku" => $product->getSku(),
      "Description" => \Drupal\Core\Mail\MailFormatHelper::htmlToText($product->get('field_description')->value),
      "Active" => $product->isActive(),
      "FullyQualifiedName" => $this->validate_text($product->getTitle()),
      "Taxable" => TRUE,
      "UnitPrice" => $product->getPrice()->getNumber(),
      "Type" => "Inventory",
      "IncomeAccountRef" => [
        "value" => \Drupal::state()
          ->get('ji_quickbooks_income_account', 0),
        //"name" => "Landscaping Services:Job Materials:Fountains and Garden Lighting",
      ],
      "PurchaseDesc" => '',
      "PurchaseCost" => $product->get('field_product_cost')->value,
      "ExpenseAccountRef" => [
        "value" => \Drupal::state()
          ->get('ji_quickbooks_expense_account', 0),
        //"name" => "Cost of Goods Sold",
      ],
      "AssetAccountRef" => [
        "value" => \Drupal::state()
          ->get('ji_quickbooks_inventory_asset_account', 0),
        //"name" => "Inventory Asset",
      ],
      // You cannot set this to FALSE and maintain that this product
      // is of a inventory type.
      "TrackQtyOnHand" => TRUE,
      "QtyOnHand" => 1,
      "InvStartDate" => $date,
    ];

    // If this realm_id exists with a product_id use it to tell QBO
    // to sync an existing item.
    if (isset($qbo_product_id_pairs[$this->realmId])) {
      $product_data['Id'] = $qbo_product_id_pairs[$this->realmId];
    }

    $response = $this->syncProduct($product, $product_data);
    $error = $this->checkErrors();

    if (empty($error['code'])) {
      $this->prepareQboProductId($product, $response->Id);

      /**
       * @todo: This is a terrible spot for this since it relates to our
       * hook_entity_presave() call.
       */
      // Only if we are saving from the product. An error is thrown
      // if we attempt to save during a presave call. In this case,
      // we want to call the save method only if we're working with
      // variations from the product page since those aren't directly
      // related to the commerce_product entity type.
      if ($product->getEntityTypeId() === 'commerce_product' || $save_product) {
        // Ugly but it prevents an infinite loop.
        global $ji_commerce_prevent_hook_entity_presave;
        $ji_commerce_prevent_hook_entity_presave = TRUE;
        $product->save();
      }

      return $response->Id;
    }
    else {
      \Drupal::logger('sendCommerceProduct')->error($error['message']);
    }
  }

  /**
   * @param $products
   *
   * @throws \Exception
   */
  public function sendCommerceBatchProducts($products) {
    $product_data_array = [];
    foreach ($products as $product) {
      $qbo_product_id_pairs = [];
      $qbo_product_id_field = $product->get('field_qbo_product_id')->value;

      if (isset($qbo_product_id_field)) {
        // Create a list from our string of realm_id:product_id field.
        $qbo_product_id_field_array = explode(',', $qbo_product_id_field);
        foreach ($qbo_product_id_field_array as $pair) {
          list ($k, $v) = explode(':', $pair);
          $qbo_product_id_pairs[$k] = $v;
        }
      }

      $transaction_timestamp = $product->getCreatedTime();
      $date = date("Y-m-d", $transaction_timestamp - 26956800);

      $product_data = [
        "Name" => $this->validate_text($product->getTitle() . ' - ' . $product->getSku()),
        "Sku" => $product->getSku(),
        "Description" => \Drupal\Core\Mail\MailFormatHelper::htmlToText($product->get('field_description')->value),
        "Active" => $product->isActive(),
        "FullyQualifiedName" => $this->validate_text($product->getTitle()),
        "Taxable" => TRUE,
        "UnitPrice" => $product->getPrice()->getNumber(),
        "Type" => "Inventory",
        "IncomeAccountRef" => [
          "value" => \Drupal::state()
            ->get('ji_quickbooks_income_account', 0),
          //"name" => "Landscaping Services:Job Materials:Fountains and Garden Lighting",
        ],
        "PurchaseDesc" => '',
        "PurchaseCost" => $product->get('field_product_cost')->value,
        "ExpenseAccountRef" => [
          "value" => \Drupal::state()
            ->get('ji_quickbooks_expense_account', 0),
          //"name" => "Cost of Goods Sold",
        ],
        "AssetAccountRef" => [
          "value" => \Drupal::state()
            ->get('ji_quickbooks_inventory_asset_account', 0),
          //"name" => "Inventory Asset",
        ],
        "TrackQtyOnHand" => TRUE,
        "QtyOnHand" => 0,
        "InvStartDate" => $date,
      ];

      // If this realm_id exists with a product_id use it to tell QBO
      // to sync an existing item.
      if (isset($qbo_product_id_pairs[$this->realmId])) {
        $product_data['Id'] = $qbo_product_id_pairs[$this->realmId];
      }

      $product_data_array[] = $product_data;
    }

    $batch = $this->dataService->CreateNewBatch();
    foreach ($product_data_array as $row => $product) {
      if (isset($product['Id'])) {
        $existing_entity = $this->queryProduct($product['Id']);
        $item = Item::update($existing_entity, $product);
        $batch->AddEntity($item, "UpdateProduct_" . $row, "Update");
      }
      else {
        $item = Item::create($product);
        $batch->AddEntity($item, 'create_product_' . $row, "Create");
      }
    }
    $batch->Execute();

    $batch_message = [];
    // \QuickBooksOnline\API\DataService;
    // Now save our new product IDs to the product.
    $rproducts = $batch->intuitBatchItemResponses;
    if (count($rproducts)) {
      /** @var \QuickBooksOnline\API\DataService\IntuitBatchResponse $rproduct */
      foreach ($rproducts as $key => $rproduct) {
        if ($rproduct->isSuccess()) {
          /** @var \QuickBooksOnline\API\Data\IPPItem $entity */
          $entity = $rproduct->getResult();
          $product = ProductVariation::load($entity->Sku);
          if (isset($product)) {
            $this->prepareQboProductId($product, $entity->Id);
            // Ugly but it prevents an infinite loop.
            global $ji_commerce_prevent_hook_entity_presave;
            $ji_commerce_prevent_hook_entity_presave = TRUE;
            $product->save();
          }
        }
        else {
          /** @var \QuickBooksOnline\API\Exception\ValidationException $exception */
          $exception = $rproduct->getError();
          $batch_message[$exception->getCode()] = $exception->getMessage();
        }
      }
    }
    else {
      $batch_message[] = "QuickBooks didn't process any products";
    }

    return $batch_message;
  }

  /**
   * @param array $orders
   *   Our array of \Drupal\commerce_order\Entity\Order objects.
   *
   * @throws \Exception
   */
  public function sendCommerceBatchCustomersAndOrders($orders) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    foreach ($orders as $order) {
      $account = User::load($order->get('uid')->getString());

      $qbo_customer_id = $this->sendCustomer($order, $account);
      if ($qbo_customer_id) {
        $qbo_invoice_id = $this->sendInvoice($order, $qbo_customer_id);
        if ($qbo_invoice_id) {
          $this->sendPayment($order, $qbo_customer_id, $qbo_invoice_id);
        }
      }
    }
  }

  /**
   *
   * @param \Drupal\commerce_product\Entity\ProductVariation $product
   * @param $id
   *
   * @return string
   */
  public function prepareQboProductId(&$product, $id) {
    $field_qbo_product_id = 'field_qbo_product_id';
    $qbo_product_id_field = $product->get($field_qbo_product_id)->value;

    if (empty($qbo_product_id_field)) {
      $product->set($field_qbo_product_id, $this->realmId . ':' . $id);
    }
    else {
      $qbo_product_id_pairs = $this->retrieveRealmAndProductId($qbo_product_id_field);

      if (!isset($qbo_product_id_pairs[$this->realmId])) {
        $qbo_product_id_pairs[$this->realmId] = $id;

        $string = '';
        // Let's clean up our field values ensuring only a distinct
        // realm:product id exists.
        foreach ($qbo_product_id_pairs as $realm_id => $qbo_product_id) {
          $string .= empty($string) ? $realm_id . ':' . $qbo_product_id : ',' . $realm_id . ':' . $qbo_product_id;
        }

        $product->set($field_qbo_product_id, $string);
      }
    }
  }

  /**
   * Adds/Updates an invoice.
   */
  private function processInvoice(array $invoice_data = []) {
    $o_invoice = new IPPInvoice();

    // CustomerId.
    $o_invoice->CustomerRef = $invoice_data['CustomerRef'];
    //$o_customer_memo_ref = new IPPMemoRef();
    //$o_invoice->CustomerMemo = $o_customer_memo_ref;
    $o_invoice->CustomerMemo = $invoice_data['CustomerMemo'];
    $o_invoice->TxnDate = $invoice_data['TxnDate'];
    $o_invoice->SalesTermRef = $invoice_data['SalesTermRef'];
    $o_invoice->BillEmail = $invoice_data['BillEmail'];
    $o_invoice->Line = $invoice_data['Line'];
    $o_invoice->TxnTaxDetail = $invoice_data['TxnTaxDetail'];
    $o_invoice->AllowOnlineCreditCardPayment = 0;
    $o_invoice->AllowOnlineACHPayment = 1;
    $o_invoice->AllowOnlinePayment = 1;
    $o_invoice->AllowIPNPayment = 1;

    // Optional billing and shipping address.
    if (isset($invoice_data['BillAddr'])) {
      $o_invoice->BillAddr = $invoice_data['BillAddr'];
    }
    if (isset($invoice_data['ShipAddr'])) {
      $o_invoice->ShipAddr = $invoice_data['ShipAddr'];
    }

    if (isset($invoice_data['Id'])) {
      $entity = $this->getInvoiceById($invoice_data['Id']);
    }

    /**
     * @todo: QBO is returning 'operation not permitted' when we
     * try to update an invoice.
     */
    if (isset($entity)) {
      $invoice = Invoice::update($entity, $invoice_data);
      $response = $this->dataService->Update($invoice);
    }
    else {
      $invoice = Invoice::create($invoice_data);
      $response = $this->dataService->Add($invoice);
    }

    $invoice = [];
    $invoice['error'] = $this->checkErrors();
    $invoice['response'] = $response;

    // Flags QuickBooks to send email containing an invoice.
    //    if ($invoice_data['EmailInvoice']) {
    //      $this->dataService->SendEmail($invoice);
    //      $invoice['error'] = $this->checkErrors();
    //    }

    return $invoice;
  }

  /**
   * Sends payment information to QuickBooks.
   */
  public function sendPayment(Order $order, $qbo_customer_id, $qbo_invoice_id) {
    $order_id = $order->getOrderNumber();
    $query = \Drupal::entityQuery('commerce_payment')
      ->condition('order_id', $order_id);
    $payments = $query->execute();
    $payment_entities = Payment::loadMultiple($payments);
    $total_paid = 0;
    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    foreach ($payment_entities as $payment) {
      $total_paid += $payment->getAmount()->getNumber();
    }

    if ($total_paid) {
      $transaction_timestamp = $order->getCreatedTime();
      $date = date("Y-m-d", $transaction_timestamp);
      $payment_data = [
        'customer_ref' => $qbo_customer_id,
        'payment_ref_num' => $order->getOrderNumber(),
        'total_amt' => $order->getTotalPrice()->getNumber(),
        'txn_date' => $date,
        'currency_ref' => $order->getTotalPaid()->getCurrencyCode(),
        'amount' => $total_paid,
        'txn_id' => $qbo_invoice_id,
      ];
      $response = $this->processPayment($payment_data);
    }
    else {
      $response = [
        'error' => [
          'message' => "Payment wasn't received.",
          'code' => 0,
        ],
        'response' => NULL,
      ];
    }

    return JIQuickBooksSupport::logProcess($order->getOrderNumber(), $this->realmId, $order->get('uid')
      ->getString(), 'payment', $response);
  }

  /**
   * Process payment.
   */
  private function processPayment(array $payment_data = []) {
    $o_payment = new IPPPayment();
    $o_payment->CustomerRef = $payment_data['customer_ref'];
    // Checking.
    $o_payment->DepositToAccountRef = \Drupal::state()
      ->get('ji_quickbooks_payment_account');
    // Check.
    $o_payment->PaymentMethodRef = \Drupal::state()
      ->get('ji_quickbooks_payment_method');
    // Shall we use another ref number?
    $o_payment->PaymentRefNum = 'Web order: ' . $payment_data['payment_ref_num'];
    $o_payment->TotalAmt = $payment_data['total_amt'];
    // TODO: determine the usage of this field.
    $o_payment->UnappliedAmt = '0';
    $o_payment->ProcessPayment = 'FALSE';
    $o_payment->TxnDate = $payment_data['txn_date'];
    $o_payment->CurrencyRef = $payment_data['currency_ref'];

    $o_line = new IPPLine();
    $o_line->Amount = $payment_data['amount'];

    $o_linked_txn = new IPPLinkedTxn();
    // Invoice ID.
    $o_linked_txn->TxnId = $payment_data['txn_id'];
    // TODO: what is this field used for?
    $o_linked_txn->TxnType = 'Invoice';
    $o_line->LinkedTxn = $o_linked_txn;

    $o_payment->Line = $o_line;

    $payment['response'] = $this->dataService->Add($o_payment);
    $payment['error'] = $this->checkErrors();

    return $payment;
  }

  /**
   * Queries QuickBooks for TaxCode name.
   */
  public function checkTaxName($name) {
    $name_checked = Html::escape($name);
    return $this->dataService->Query("SELECT * FROM TaxCode where Name in ('$name_checked')");
  }

  /**
   * Queries QuickBooks for TaxRate name.
   */
  public function checkTaxRateName($name) {
    $name_checked = Html::escape($name);
    return $this->dataService->Query("SELECT * FROM TaxRate where Name in ('$name_checked')");
  }

  /**
   * Queries QuickBooks for TaxAgency name.
   *
   * Either way we should receive a TaxAgency name.
   */
  public function checkAgencyAddAgencyName($name) {
    $name_checked = Html::escape($name);
    $query_response = $this->dataService->Query("SELECT * FROM TaxAgency where Name = '$name_checked'");

    // New name, let's add it.
    if (!$query_response) {
      $o_tax_agency = new \IPPTaxAgency();
      $o_tax_agency->DisplayName = $name;
      $add_response = $this->dataService->Add($o_tax_agency);
      return $add_response;
    }

    return $query_response;
  }

  /**
   * Query QBO to GetAllCustomers.
   */
  public function getAllCustomers() {
    return $this->dataService->FindAll('Customer');
  }

  /**
   * Query QBO to GetCustomerById.
   */
  public function getTaxAgencies() {
    return $this->dataService->Query("SELECT * FROM TaxAgency");
  }

  /**
   * Query QBO to getTaxCodeById.
   */
  public function getTaxCodeById($id = NULL) {
    return $this->dataService->Query("SELECT * FROM TaxCode where Active in (true)");
  }

  /**
   * Query QBO to getTaxRateById.
   */
  public function getTaxRateById($id = NULL) {
    return $this->dataService->FindById(new IPPTaxRate(['Id' => $id], TRUE));
  }

  /**
   * Returns all available taxes from QuickBooks.
   *
   * @return array|NULL
   *   NULL on error or the array of taxes.
   */
  public function getAllTaxes() {
    try {
      $qbo_tax_codes = $this->dataService->Query("SELECT * FROM TaxCode where Active in (true,false)");
      $qbo_tax_rates = $this->dataService->Query("SELECT * FROM TaxRate where Active in (true,false)");
      $qbo_tax_agencies = $this->dataService->Query("SELECT * FROM TaxAgency");

      $result = [];

      if (!$qbo_tax_codes) {
        return NULL;
      }

      foreach ($qbo_tax_codes as $value_tax_code) {
        $o_tax_response = new \stdClass();
        $o_tax_response->Id = $value_tax_code->Id;
        $o_tax_response->Name = $value_tax_code->Name;
        $o_tax_response->Active = $value_tax_code->Active;
        // Used to compare if two tax records are similar.
        $o_tax_response->MetaData = $value_tax_code->MetaData;

        $tax_rates = [];
        $count_tax_rate = 0;

        if (!is_array($value_tax_code->SalesTaxRateList->TaxRateDetail)) {
          $value_tax_code->SalesTaxRateList->TaxRateDetail = [$value_tax_code->SalesTaxRateList->TaxRateDetail];
        }

        foreach ($qbo_tax_rates as $value_tax_rate) {
          foreach ($value_tax_code->SalesTaxRateList->TaxRateDetail as $value_tax_rate_detail) {
            if ($value_tax_rate_detail->TaxRateRef == $value_tax_rate->Id) {
              $o_rate_response = new \stdClass();
              $o_rate_response->TaxRateRef = $value_tax_rate->Id;
              $o_rate_response->Name = $value_tax_rate->Name;
              $o_rate_response->RateValue = $value_tax_rate->RateValue;
              $o_rate_response->AgencyRef = $value_tax_rate->AgencyRef;

              foreach ($qbo_tax_agencies as $agency) {
                if ($agency->Id == $value_tax_rate->AgencyRef) {
                  $o_rate_response->AgencyName = $agency->DisplayName;
                }
              }

              $tax_rates[$count_tax_rate] = $o_rate_response;
              $count_tax_rate++;
            }
          }
        }

        $o_tax_response->TaxRates = $tax_rates;

        $result[$o_tax_response->Id] = $o_tax_response;
      }

      return $result;
    } catch (\Exception $e) {
      \Drupal::logger('getAllTaxes')->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * Query QBO to getAllTaxCodes.
   */
  public function getAllTaxCodes() {
    return $this->dataService->Query("SELECT * FROM TaxCode where Active in (true,false)");
  }

  /**
   * Query QBO to getAllTaxRates.
   */
  public function getAllTaxRates() {
    return $this->dataService->Query("SELECT * FROM TaxRate where Active in (true,false)");
  }

  /**
   * Query QBO to getAllTaxAgency.
   */
  public function getAllTaxAgency() {
    return $this->dataService->Query("SELECT * FROM TaxAgency");
  }

  /**
   * Query QBO to GetAllProducts or Items.
   */
  public function getAllProducts($accounts = NULL) {
    return $this->dataService->Query('SELECT * FROM Item');
  }

  /**
   * Query QBO to GetAllTerms.
   */
  public function getAllTerms($accounts = NULL) {
    return $this->dataService->Query('SELECT * FROM Term WHERE Active=TRUE ORDERBY Id');
  }

  /**
   * Query QBO to GetAllAccounts.
   */
  public function getAllAccounts($accounts = NULL) {
    return $this->dataService->Query("SELECT * FROM Account");
  }

  /**
   * Query QBO to getAccountsByType.
   */
  public function getAccountsByType($where = 'AccountType', $types = []) {
    $filter = '';
    foreach ($types as $key => $type) {
      $types[$key] = "'" . $type . "'";
    }

    if (!empty($types)) {
      $filter = " WHERE " . $where . " in(" . implode(', ', $types) . ")";
    }

    return $this->dataService->Query("SELECT * FROM Account" . $filter);
  }

  /**
   * Query QBO to GetAllPaymentMethods.
   */
  public function getAllPaymentMethods() {
    return $this->dataService->Query("SELECT * FROM PaymentMethod");
  }

  /**
   * Returns company information.
   *
   * Returns an array where $response[0] is the object with the
   * company data.
   */
  public function getCompanyData() {
    return $this->dataService->Query('SELECT * FROM CompanyInfo');
  }

  /**
   * Create a product/item within QuickBooks.
   *
   * @param
   *
   * @param array $item_array
   *   Our product data array which is taken from the entity.
   *
   * @return mixed
   *   Returns an object from QBO if successful.
   */
  public function syncProduct($product, $item_array = []) {
    try {
      $response = NULL;
      // Our product has an existing QBO ID, try to find it and update it.
      if (isset($item_array['Id'])) {
        $entity = $this->queryProduct($item_array['Id']);
      }

      // We didn't find a product via the ID. This can happen if
      // the product existed once but was removed.
      if (!isset($entity)) {
        // On edge cases, if a realm_id:product_id exist but upon querying
        // QBO it returns NULL, we should check against a SKU.
        $empty = [];
        $entity = $this->queryProductByField($product, $empty, TRUE);
      }

      // Ensure we clear the Id. We do this in case the ID was
      // different which occurs if this product was synced once within a realm
      // then was changed to a different one or data was cleared from an existing
      // realm.
      unset($item_array['Id']);

      if (isset($entity)) {
        $item = Item::update($entity, $item_array);
        $response = $this->dataService->Update($item);
      }
      else {
        $item = Item::create($item_array);
        $response = $this->dataService->Add($item);
      }

      return $response;
    } catch (\Exception $e) {
      watchdog_exception('syncProduct', $e);
      return NULL;
    }
  }

  /**
   * Query QBO per user selected Drupal field.
   *
   * We check if a product with a similar ID exists in QBO. Can be by
   * SKU.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariation $product
   *  Our commerce product variant.
   *
   * @param array $qbo_product_id_pairs
   *  Our product ID array.
   *
   * @param boolean $return_product
   *  Should we return an entity if found? FALSE by default.
   *
   * @throws \Exception
   */
  public function queryProductByField($product, &$qbo_product_id_pairs = [], $return_product = FALSE) {
    // Get our Drupal field first.
    $drupal_field = \Drupal::state()
      ->get('ji_quickbooks_search_before_sync_drupal', 'sku');
    $drupal_field_value = $product->get($drupal_field)->value;
    // Get which field we should compare within QuickBooks.
    $qbo_field = \Drupal::state()
      ->get('ji_quickbooks_search_before_qbo', 'Sku');
    $response = $this->dataService->Query("select * from Item where " . $qbo_field . " ='" . $drupal_field_value . "'");
    // Found an product with a matching SKU.
    if (is_array($response)) {
      $existing_item = reset($response);
      if (isset($existing_item)) {
        $qbo_product_id_pairs[$this->realmId] = $existing_item->Id;
      }

      // In case we need to turn the entity we found in the QBO database.
      if ($return_product) {
        return $existing_item;
      }
    }
  }

  public function queryProduct($id) {
    if (isset($id)) {
      $entity = $this->dataService->Query("SELECT * FROM Item where Id='" . $id . "'");
      if (is_array($entity)) {
        if (count($entity)) {
          return reset($entity);
        }
      }
    }
    return NULL;
  }

  /**
   * Replace characters which QBO Online will reject.
   *
   * Remove " and : from a string.
   *
   * @param string $string
   *   The string to parse.
   *
   * @return mixed|string
   *   Our cleaned/trimmed string.
   */
  public
  function validate_text($string = '') {
    $string = str_replace(':', '', $string);
    $string = str_replace('"', '', $string);
    return $string;
  }

  /**
   * The voidInvoice() method.
   *
   * @param int $id
   *   Invoice id.
   *
   * @return array
   *   $response['response'] and $response['error'] from QBO.
   */
  public
  function voidInvoice($id) {
    $ippinvoice = $this->getInvoiceById($id);
    $response['response'] = $this->dataService->Void($ippinvoice);
    $response['error'] = $this->checkErrors();

    return $response;
  }

  /**
   * Query QBO to GetInvoiceById.
   */
  public
  function getInvoiceById($id) {
    return $this->dataService->FindById(new IPPInvoice(['Id' => $id], TRUE));
  }

  /**
   * The voidPayment() method.
   *
   * @param int $id
   *   Payment id.
   *
   * @return array
   *   $response['response'] and $response['error'] from QBO.
   */
  public
  function voidPayment($id) {
    $ipppayment = $this->getPaymentById($id);
    $response['response'] = $this->dataService->VoidPayment($ipppayment);
    $response['error'] = $this->checkErrors();

    return $response;
  }

  /**
   * Query QBO to GetPaymentById.
   */
  public
  function getPaymentById($id) {
    return $this->dataService->FindById(new IPPPayment(['Id' => $id], TRUE));
  }

}
