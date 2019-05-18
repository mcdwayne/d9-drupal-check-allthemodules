<?php

namespace Drupal\pagarme_marketplace\Pagarme;

use Drupal\pagarme\Pagarme\PagarmeSdk;
use PagarMe\Sdk\Recipient\Recipient;
use PagarMe\Sdk\SplitRule\SplitRuleCollection;
use Drupal\pagarme\Helpers\PagarmeUtility;

class PagarmeSplitRuleCollection extends PagarmeSdk {

  const PAGARME_SPLIT_ACTIVE = 1;

  protected $order;

  protected $payment_method;
  protected $final_amount;

  protected $billet_per_discount = 0;
  protected $credit_card_per_interest = 0;

  protected $apply_split_rule = FALSE;
  protected $split_rule_remnant = 0;

  public function __construct($order, $payment_method, $final_amount) {

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
    $payment_gateway = $order->get('payment_gateway');
    $payment_gateway  = reset($payment_gateway->referencedEntities());
    $this->setPluginConfiguration($payment_gateway->get('configuration'));

    $api_key = $this->plugin_configuration['pagarme_api_key'];
    parent::__construct($api_key);

    $this->order = $order;

    $this->payment_method = $payment_method;
    $this->final_amount = intval($final_amount);
    switch ($this->payment_method) {
      case 'credit_card':
        $this->creditCardPercentageInterest();
        break;
      case 'boleto':
        $this->billetPercentageDiscount();
        break;
    }
  }

  public function doSplitRuleCollection() {
    $rules = array();
    $split_rule_mapping = $this->splitRuleMapping();
    $total_amount_final_rule = 0;
    if ($this->apply_split_rule) {
      $split_rule_collection = new SplitRuleCollection();
      foreach ($split_rule_mapping as $recipient_id => $items) {
        foreach ($items as $rule) {
          $amount = $rule['amount'];
          // Os centavos que não foram possível distribuir em uma divisão exata será distribuida entres os recebedores até atingir o valor total da sobra.
          if ($this->split_rule_remnant > 0) {
            $amount++;
            $this->split_rule_remnant--;
          }
          $total_amount_final_rule += $amount;
          /** @var $splitRule PagarMe\Sdk\SplitRule\SplitRule */
          $split_rule_collection[] = $this->pagarme->splitRule()->monetaryRule(
              $amount,
              new Recipient(array('id' => $recipient_id)),
              $rule['liable'],
              $rule['charge_processing_fee']
          );
        }
      }

      if ($total_amount_final_rule !== $this->final_amount) {
        $error_msg = print_r($split_rule_mapping, TRUE)
          . print_r(array('pagarme_final_amount' => $this->final_amount), TRUE);
        throw new \ErrorException('pagarme_split_rules - invalid split rule: <pre>' . $error_msg . '</pre>');
      }
      $rules['splitRules'] = $split_rule_collection;
    }
    if ($this->plugin_configuration['pagarme_debug']) {
      \Drupal::logger('pagarme')->debug(t('@split_rule_mapping: <pre>@pre</pre>'), array('@pre' => print_r($split_rule_mapping, TRUE)));

      \Drupal::logger('pagarme')->debug(t('@split_rule_collection: <pre>@pre</pre>'), array('@pre' => print_r($rules, TRUE)));
    }
    return $rules;
  }

  public function getIntegerAmountFromOrder() {
    return intval($this->order->getTotalPrice()->getNumber() * 100);
  }

  protected function creditCardPercentageInterest() {
    // Valor do pedido sem o desconto
    $order_amount = $this->getIntegerAmountFromOrder();

    // Valor final do pedido (com/sem juros)
    $final_amount = $this->final_amount;

    // Validando juros aplicados ao valor do pedido
    if ($final_amount > $order_amount) {

      // Diferenção do valor do pedido e o valor final
      $amount_interest = $final_amount - $order_amount;

      // Percentual de juros no valor final
      $this->credit_card_per_interest = $amount_interest * 100 / $order_amount;
    }
    return $this->credit_card_per_interest;
  }

  protected function billetPercentageDiscount() {
    // Valor do pedido sem o desconto
    $order_amount = $this->getIntegerAmountFromOrder();

    // Valor final do pedido (com/sem desconto)
    $final_amount = $this->final_amount;

    // Validando se o valor do boleto tem desconto
    if ($final_amount < $order_amount) {

      // Valor do desconto aplicado
      $billet_amount_difference = $order_amount - $final_amount;

      $discount_amount = 0;

      // Desconto boleto (Percentual/Valor em centavos)
      switch ($this->plugin_configuration['pagarme_boleto_discount']) {
        case 'amount':
          $discount_amount = $this->plugin_configuration['pagarme_boleto_discount_amount'];
          break;
        case 'percentage':
          $discount_percentage = $this->plugin_configuration['pagarme_boleto_discount_percentage'];
          if ($discount_percentage) {
            // Valor do desconto a ser aplicado
            $discount_amount = $order_amount * $discount_percentage / 100;
          }
          break;
        default:
          throw new \ErrorException('Order ' . $this->order->id() .', there is a divergence in the value of the order and the value processed by the Pagar.me.');
      }
      if ($billet_amount_difference != $discount_amount) {
        throw new \ErrorException('Order ' . $this->order->id() .', there is a divergence in the value of the order and the value processed by the Pagar.me.');
      }

      $this->billet_per_discount = $discount_amount * 100 / $order_amount;
    }
    return $this->billet_per_discount;
  }

  public function splitRuleMapping() {
    $split_rule_mapping = array();

    $company_info = $this->getCompanyInfo();

    // Default Recipient ID Pagarme.me
    $server = $this->plugin_configuration['pagarme_server'];
    $default_recipient_id = $company_info->default_recipient_id->{$server};

    $total_amount_rule = 0;

    $config = \Drupal::config('pagarme_marketplace.settings');
    $split_line_item_types = array_filter($config->get('split_line_item_types'));

    // Iterating cart items
    foreach ($this->order->getItems() as $line_item) {

      $line_item_type = $line_item->get('type')->getString();
      if (in_array($line_item_type, $split_line_item_types)) {
        $product_variation_id = $line_item->getPurchasedEntityId();

        // Split rules registered for the product
        $metadata = array('commerce_line_item' => $line_item, 'order' => $this->order);
        $company = $this->plugin_configuration['pagarme_api_key'];
        $split = $this->loadSplitProduct($product_variation_id, $company, $metadata);

        if ($split) {
          // Apply split rules if you have at least one product in the cart that has a split rule register
          $this->apply_split_rule = TRUE;
          $default_amount = intval($split->amount);
          if ($default_amount > 0 || $split->liable ||  $split->charge_processing_fee) {
            // Calculates the value in cents for the default recipient, over the total value of the item
            $recipient_rule = $this->calculateValueCents(
                $split->split_type,
                $default_amount,
                $line_item
            );
            $recipient_rule += array(
              'liable' => boolval($split->liable),
              'charge_processing_fee' => boolval($split->charge_processing_fee)
            );
            $split_rule_mapping[$default_recipient_id][] = $recipient_rule;
            $total_amount_rule += $recipient_rule['amount'];
          }
          foreach ($split->rules as $rule) {
            $recipient_amount = intval($rule->amount);
            if ($recipient_amount > 0 || $rule->liable || $rule->charge_processing_fee) {
              $recipient_pagarme_id = $rule->recipient_pagarme_id;

              // Calculates the value in cents for other recipient, over the total value of the item
              $recipient_rule = $this->calculateValueCents(
                  $split->split_type,
                  $recipient_amount,
                  $line_item
              );
              $recipient_rule += array(
                'liable' => boolval($rule->liable),
                'charge_processing_fee' => boolval($rule->charge_processing_fee)
              );
              $split_rule_mapping[$recipient_pagarme_id][] = $recipient_rule;
              $total_amount_rule += $recipient_rule['amount'];
            }
          }
        }
        // If the product does not have a split rule registered 100% of the value goes to the default recipient
        else {
          $line_item_total = PagarmeUtility::amountDecimalToInt($line_item->getTotalPrice()->getNumber());

          // Aplicar desconto ou juros(boleto/cartão) proporcional ao valor a ser recebido
          $amount = $this->balanceAmount($line_item_total);

          $split_rule_mapping[$default_recipient_id][] = array(
            'sku' => $line_item->getPurchasedEntity()->getSku(),
            'amount' => $amount,
            'liable' => TRUE,
            'charge_processing_fee' => TRUE
          );
          $total_amount_rule += $amount;
        }
      }
    }
    // A diferença do valor é os centavos que não é possivel adicionar em uma divisão exata de um número inteiro representando centavos.
    $this->split_rule_remnant = $this->final_amount - $total_amount_rule;
    return $split_rule_mapping;
  }

  protected function calculateValueCents($split_type, $value, $line_item) {
    $amount = 0;
    switch ($split_type) {
      case 'percentage':
        $line_item_total = PagarmeUtility::amountDecimalToInt($line_item->getTotalPrice()->getNumber());
        // O percentual é armazenado como número inteiro, no mesmo formato que valor, permitindo duas casas decimais
        $value = PagarmeUtility::amountIntToDecimal($value);
        $amount = $line_item_total * $value / 100;
        break;
      case 'amount':
        $quantity = intval($line_item->getQuantity());
        $amount = $quantity * $value;
        break;
      default:
        break;
    }

    // Aplicar desconto ou juros(boleto/cartão) proporcional ao valor a ser recebido
    $amount = $this->balanceAmount($amount);
    return array(
      'sku' => $line_item->getPurchasedEntity()->getSku(),
      'amount' => $amount,
    );
  }

  public function balanceAmount($amount) {
    switch ($this->payment_method) {
      case 'boleto':
        $discount_amount = $amount * $this->billet_per_discount / 100;
        $amount -= $discount_amount;
        break;
      case 'credit_card':
        $interest_amount = $amount * $this->credit_card_per_interest / 100;
        $amount += $interest_amount;
        break;
    }

    // O valor deve ser um número inteiro, pois o valor é um número inteiro representando centavos.
    return intval(floor($amount));
  }

  public function loadSplitProduct($product_variation_id, $company = NULL, $metadata = array()) {

    $database = \Drupal::database();
    $split = $database->select('pagarme_splits', 'splits')
      ->fields('splits')
      ->condition('product_variation_id', $product_variation_id)
      ->condition('status', self::PAGARME_SPLIT_ACTIVE)
      ->condition('company', $company)
      ->addMetaData('metadata', $metadata)
      ->addTag('load_split_product')
      ->orderBY('created', 'DESC')
      ->execute()
      ->fetchObject();

    if ($split) {
      $query = $database->select('pagarme_split_rules', 'rules');
      $query->fields('rules');
      $query->addField('recipients', 'pagarme_id', 'recipient_pagarme_id');
      $query->leftJoin('pagarme_recipients', 'recipients', 'recipients.recipient_id = rules.recipient_id');
      $query->condition('split_id', $split->split_id);
      $query->addMetaData('metadata', $metadata);
      $query->addTag('load_split_rules_product');
      $split->rules = $query->execute()->fetchAll();
    }
    return $split;
  }
}
