<?php
namespace Drupal\pagarme_marketplace\Tests\Functional;
use Drupal\Core\Url;
use Drupal\pagarme\Helpers\PagarmeUtility;
use Drupal\pagarme\Pagarme\PagarmeSdk;
use Drupal\Tests\commerce_product\Functional\ProductBrowserTestBase;
/**
 * @file
 * Tests for pagarme_marketplace.module.
 */

/**
 * Defines a base class for testing the pagarme_marketplace module.
 */
class PagarmeMarketplaceTestCase extends ProductBrowserTestBase {
  protected $api_key;
  public static $modules = [
    'pagarme',
    'pagarme_marketplace'
  ];
  protected $postback_url;
  protected $web_user;

  protected function setUp() {
    parent::setUp();
    $company = PagarmeUtility::createCompany('unit_test');
    $this->api_key = $company->api_key->test;
    $this->postback_url = Url::fromUri('base:pagarme/notification', ['absolute' => TRUE]);
    //TODO Create new permissions on pagarme_marketplace routes to give right permissions to each
    $permissions = $this->getAdministratorPermissions();
    $this->web_user = $this->drupalCreateUser($permissions);
  }

  protected function randomCpfCnpj($mask = FALSE) {
    $cpf_cnpj = array('cpf', 'cnpj');
    $rand_keys = array_rand($cpf_cnpj, 1);
    switch ($cpf_cnpj[$rand_keys]) {
      case 'cpf':
      return $this->randomCpf($mask);
        break;
      default:
        return $this->randomCnpj($mask);
        break;
    }
  }

  protected function randomCpf($mask = FALSE) {
    $n1 = rand(0, 9);
    $n2 = rand(0, 9);
    $n3 = rand(0, 9);
    $n4 = rand(0, 9);
    $n5 = rand(0, 9);
    $n6 = rand(0, 9);
    $n7 = rand(0, 9);
    $n8 = rand(0, 9);
    $n9 = rand(0, 9);
    $d1 = $n9 * 2 + $n8 * 3 + $n7 * 4 + $n6 * 5 + $n5 * 6 + $n4 * 7 + $n3 * 8 + $n2 * 9 + $n1 * 10;
    $d1 = 11 - ($this->mod($d1, 11) );
    if ($d1 >= 10) {
      $d1 = 0;
    }
    $d2 = $d1 * 2 + $n9 * 3 + $n8 * 4 + $n7 * 5 + $n6 * 6 + $n5 * 7 + $n4 * 8 + $n3 * 9 + $n2 * 10 + $n1 * 11;
    $d2 = 11 - ($this->mod($d2, 11) );
    if ($d2 >= 10) {
      $d2 = 0;
    }
    $cpf = '';
    if ($mask) {
      $cpf = '' . $n1 . $n2 . $n3 . "." . $n4 . $n5 . $n6 . "." . $n7 . $n8 . $n9 . "-" . $d1 . $d2;
    } 
    else {
      $cpf = '' . $n1 . $n2 . $n3 . $n4 . $n5 . $n6 . $n7 . $n8 . $n9 . $d1 . $d2;
    }
    return $cpf;
  }

  protected function randomCnpj($masc = FALSE) {
    $n1 = rand(0, 9);
    $n2 = rand(0, 9);
    $n3 = rand(0, 9);
    $n4 = rand(0, 9);
    $n5 = rand(0, 9);
    $n6 = rand(0, 9);
    $n7 = rand(0, 9);
    $n8 = rand(0, 9);
    $n9 = 0;
    $n10 = 0;
    $n11 = 0;
    $n12 = 1;
    $d1 = $n12 * 2 + $n11 * 3 + $n10 * 4 + $n9 * 5 + $n8 * 6 + $n7 * 7 + $n6 * 8 + $n5 * 9 + $n4 * 2 + $n3 * 3 + $n2 * 4 + $n1 * 5;
    $d1 = 11 - ($this->mod($d1, 11) );
    if ($d1 >= 10) {
      $d1 = 0;
    }
    $d2 = $d1 * 2 + $n12 * 3 + $n11 * 4 + $n10 * 5 + $n9 * 6 + $n8 * 7 + $n7 * 8 + $n6 * 9 + $n5 * 2 + $n4 * 3 + $n3 * 4 + $n2 * 5 + $n1 * 6;
    $d2 = 11 - ($this->mod($d2, 11) );
    if ($d2 >= 10) {
      $d2 = 0;
    }
    $cnpj = '';
    if ($masc) {
      $cnpj = '' . $n1 . $n2 . "." . $n3 . $n4 . $n5 . "." . $n6 . $n7 . $n8 . "/" . $n9 . $n10 . $n11 . $n12 . "-" . $d1 . $d2;
    } 
    else {
      $cnpj = '' . $n1 . $n2 . $n3 . $n4 . $n5 . $n6 . $n7 . $n8 . $n9 . $n10 . $n11 . $n12 . $d1 . $d2;
    }
    return $cnpj;
  }

  protected function mod($dividend, $divider) {
    return round($dividend - (floor($dividend / $divider) * $divider));
  }

  /**
   * Generate a random valid email
   *
   * @param string $type
   *  Domain type
   *
   * @return string
   *  Valid email
   */
  protected function generateEmail($type = 'com'){
    return $this->randomString() . '@' . $this->randomString() . '.' . $type;
  }

  protected function dataDummyCustomer() {
    $customer_data = array();
    $data_address = array();
    $data_phone = array();

    $customer_data += array(
      'name' => $this->randomString(),
      'email' => $this->generateEmail(),
      'document_number' => $this->randomCpfCnpj(),
      'gender' => 'M',
      'bornAt' => '11-02-1985',
    );

    $data_address = array(
      'street' => $this->randomString(),
      'streetNumber' => rand(0, 100),
      'neighborhood' => $this->randomString(),
      'zipcode' => 94945200,
      'complementary' => '',
      'city' => $this->randomString(),
      'state' => $this->randomString(),
      'country' => $this->randomString(),
    );

    $data_phone = array(
      'ddd' => 11,
      'number' => 983565728,
      'ddi' => 55,
    );

    $customer_data += array(
      'address' => new \PagarMe\Sdk\Customer\Address($data_address),
      'phone' => new \PagarMe\Sdk\Customer\Phone($data_phone),
    );

    return new \PagarMe\Sdk\Customer\Customer($customer_data);
  }

  protected function pagarmeDummyBoletoTransaction($amount, $pay_transaction = TRUE) {
    $customer = $this->dataDummyCustomer();
    $metadata = array();
    $split_rules = $this->pagarmeSplitRuleCollection($amount);
    $transaction = $this->pagarme_marketplace->pagarme->transaction()->boletoTransaction(
        $amount,
        $customer,
        $this->postback_url,
        $metadata,
        $split_rules
    );

    if ($pay_transaction) {
      $this->pagarme_marketplace->pagarme->transaction()->payTransaction($transaction);
    }
    return $transaction;
  }

  protected function dataDummyRecipient() {
    $recipient = array();
    $transfer_intervals = \Drupal\pagarme\Helpers\PagarmeUtility::transferInterval();
    $banks = \Drupal\pagarme\Helpers\PagarmeUtility::banks();
    $account_types = \Drupal\pagarme\Helpers\PagarmeUtility::accountTypes();

    $recipient["transfer_enabled"] = mt_rand(0, 1);
    $recipient["transfer_interval"] = array_rand($transfer_intervals, 1);
    $recipient["bank_code"] = array_rand($banks, 1);
    $recipient["type"] = array_rand($account_types, 1);
    $recipient["legal_name"] = $this->randomString();
    $recipient["document_number"] = $this->randomCpfCnpj();
    $recipient["agencia"] = mt_rand(1000, 9999);
    $recipient["agencia_dv"] = mt_rand(0, 9);
    $recipient["conta"] = mt_rand(10000, 99999);
    $recipient["conta_dv"] = mt_rand(0, 9);
    return $recipient;
  }

  protected function dataDummySplit($product_id, $amount) {
    $split = [];
    // $split['product_id'] = $product_id;
    $split['status'] = 1;
    $split['default_liable'] = 1;
    $split['default_charge_processing_fee'] = 1;
    $split['split_type'] = 'amount';
    $split['default_amount'] = $amount;
    return $split;
  }

  protected function getRecipientByDocumentNumber($document_number) {
    return \Drupal::database()->select('pagarme_recipients', 'recipients')
      ->fields('recipients')
      ->condition('document_number', $document_number)
      ->execute()
      ->fetchObject();
  }

  protected function getSplitByProductVariationId($product_variation_id) {
    return \Drupal::database()->select('pagarme_splits', 'splits')
      ->fields('splits')
      ->condition('product_variation_id', $product_variation_id)
      ->execute()
      ->fetchObject();
  }

  protected function pagarmeCreateRecipient() {
    $data_recipient = $this->dataDummyRecipient();
    $bank_account = new \PagarMe\Sdk\BankAccount\BankAccount(array(
        'bankCode' => $data_recipient["bank_code"],
        'type' => $data_recipient["type"],
        'legalName' => $data_recipient["legal_name"],
        'documentNumber' => $data_recipient["document_number"],
        'agencia' => $data_recipient["agencia"],
        'agenciaDv' => $data_recipient["agencia_dv"],
        'conta' => $data_recipient["conta"],
        'contaDv' => $data_recipient["conta_dv"],
    ));

    return $this->pagarme_marketplace->pagarme->recipient()->create(
        $bank_account,
        'weekly',
        1,
        TRUE,
        FALSE,
        0
    );
  }

  protected function pagarmeSplitRuleCollection($amount_integer, $rules_total = 4) {
    $split_rule_collection = new \PagarMe\Sdk\SplitRule\SplitRuleCollection();
    $amount = floor($amount_integer / $rules_total);
    $amount_remnant = $amount_integer - $amount * $rules_total;

    for ($i = 0; $i < $rules_total; $i++) {
      $amount = ($i == 0) ? $amount + $amount_remnant : $amount;
      $split_rule_collection[] = $this->pagarme_marketplace->pagarme->splitRule()->monetaryRule(
          $amount,
          $this->pagarmeCreateRecipient(),
          1,
          1
      );
    }
    return array('splitRules' => $split_rule_collection);
  }
}
