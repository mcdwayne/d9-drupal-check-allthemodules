<?php

namespace Drupal\commerce_cmcic\PluginForm;

use Drupal\commerce_cmcic\CommerceCmcicAPI;
use Drupal\commerce_cmcic\kit\CmcicHmac;
use Drupal\commerce_cmcic\kit\CmcicTpe;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class CmcicPaymentForm extends PaymentOffsiteForm {

  /**
   * Builds a CM-CIC form from an order object.
   *
   * @param object $order
   *   The fully loaded order being paid for.
   * @param array $settings
   *   An array of settings used to build out the form, including:
   *   - tpe: The TPE number of the CM-CIC account.
   *   - company : The company number of the CM-CIC account.
   *   - bank_type : The bank chosen.
   *   - mode : The flag of the environnement type.
   *
   * @return array
   *   A renderable form array.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_cmcic\Plugin\Commerce\PaymentGateway\CmcicPaymentGateway $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $settings = $payment_gateway_plugin->getConfiguration();
    $order = $payment->getOrder();

    // Return an error if the enabling action's settings haven't been configured.
    if (empty($settings['tpe'])) {
      drupal_set_message(t('CM-CIC is not configured for use. No CM-CIC TPE has been specified.'), 'error');
      return array();
    }
    elseif (empty($settings['company'])) {
      drupal_set_message(t('CM-CIC is not configured for use. No CM-CIC company number has been specified.'), 'error');
      return array();
    }
    elseif (empty($settings['security_key'])) {
      drupal_set_message(t('CM-CIC is not configured for use. No CM-CIC security key has been specified.'), 'error');
      return array();
    }

    $settings += CommerceCmcicAPI::getSettings($order);

    /********************************/
    /**** GET ORDER INFORMATIONS ****/
    /********************************/

    $currency_code = $order->getTotalPrice()->getCurrencyCode();
    $amount = $order->getTotalPrice()->getNumber();

    /***********************************************/
    /**** PREPARE ALL VARIABLES FOR PAYMENT KIT ****/
    /***********************************************/

    $s_options = "";

    // Reference: unique, alphaNum (A-Z a-z 0-9), 12 characters max.

    $s_reference = CommerceCmcicAPI::invoice($order);

    // Amount : format  "xxxxx.yy" (no spaces).
    $s_amount = money_format('%.2n', $amount);

    // Currency : ISO 4217 compliant.
    $s_currency_code = $currency_code;

    // Free text : a bigger reference, session context for the return on the
    // merchant website.
    $s_free_text = "";

    // Transaction date : format d/m/y:h:m:s.
    $s_date = date("d/m/Y:H:i:s");

    // Language of the company code.
    $s_language = strtoupper(\Drupal::languageManager()->getCurrentLanguage()->getId());

    // Customer email.
    $s_email = $order->getEmail();

    // Between 2 and 4.
    $s_nbr_ech = "";

    // Date echeance 1 - format dd/mm/yyyy.
    $s_date_echeance1 = "";

    // Montant echeance 1 - format  "xxxxx.yy" (no spaces).
    $s_montant_echeance1 = "";

    // Date echeance 2 - format dd/mm/yyyy.
    $s_date_echeance2 = "";

    // Montant echeance 2 - format  "xxxxx.yy" (no spaces).
    $s_montant_echeance2 = "";

    // Date echeance 3 - format dd/mm/yyyy
    $s_date_echeance3 = "";

    // Montant echeance 3 - format  "xxxxx.yy" (no spaces).
    $_montant_echeance3 = "";

    // Date echeance 4 - format dd/mm/yyyy.
    $s_date_echeance4 = "";

    // Montant echeance 4 - format  "xxxxx.yy" (no spaces).
    $s_montant_echeance4 = "";

    $settings['url_server'] = CommerceCmcicAPI::getServer($settings['bank_type'], $settings['mode']);

    $o_tpe = new CmcicTpe($settings, $s_language);
    $o_hmac = new CmcicHmac($o_tpe);

    // Control String for support.
    $ctl_hmac = sprintf(CMCIC_CTLHMAC, $o_tpe->sVersion, $o_tpe->sNumero, $o_hmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $o_tpe->sVersion, $o_tpe->sNumero)));

    // Data to certify.
    $php1_fields = sprintf(CMCIC_CGI1_FIELDS, $o_tpe->sNumero,
      $s_date,
      $s_amount,
      $s_currency_code,
      $s_reference,
      $s_free_text,
      $o_tpe->sVersion,
      $o_tpe->sLangue,
      $o_tpe->sCodeSociete,
      $s_email,
      $s_nbr_ech,
      $s_date_echeance1,
      $s_montant_echeance1,
      $s_date_echeance2,
      $s_montant_echeance2,
      $s_date_echeance3,
      $_montant_echeance3,
      $s_date_echeance4,
      $s_montant_echeance4,
      $s_options);

    // MAC computation.
    $s_mac = $o_hmac->computeHmac($php1_fields);

    /***********************/
    /**** GENERATE FORM ****/
    /***********************/

    // Ensure a default value for the payment_method setting.
    $settings += array('payment_method' => '');

    // Build the data array that will be translated into hidden form values.
    $data = array(
      'version' => $o_tpe->sVersion,
      'TPE' => $o_tpe->sNumero,
      'date' => $s_date,
      'montant' => $s_amount . $s_currency_code,
      'reference' => $s_reference,
      'MAC' => $s_mac,
      'url_retour' => $o_tpe->sUrlKO,
      'url_retour_ok' => $o_tpe->sUrlOK,
      'url_retour_err' => $o_tpe->sUrlKO,
      'lgue' => $o_tpe->sLangue,
      'societe' => $o_tpe->sCodeSociete,
      'texte-libre' => $s_free_text,
      'mail' => $s_email,

      // For split payment.
      'nbrech' => $s_nbr_ech,
      'dateech1' => $s_date_echeance1,
      'montantech1' => $s_montant_echeance1,
      'dateech2' => $s_date_echeance2,
      'montantech2' => $s_montant_echeance2,
      'dateech3' => $s_date_echeance3,
      'montantech3' => $_montant_echeance3,
      'dateech4' => $s_date_echeance4,
      'montantech4' => $s_montant_echeance4,
    );

    foreach ($data as $name => $value) {
      if (!empty($value)) {
        $form[$name] = ['#type' => 'hidden', '#value' => $value];
      }
    }

    $mode = $payment_gateway_plugin->getMode();
    return $this->buildRedirectForm($form, $form_state, $o_tpe->sUrlPaiement, $data, 'post');
  }
}
