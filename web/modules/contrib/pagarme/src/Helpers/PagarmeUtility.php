<?php

namespace Drupal\pagarme\Helpers;
use Drupal\Component\Utility\UrlHelper;
use Drupal\commerce_price\Entity\Currency;

class PagarmeUtility {
  public static function weekdays() {
    return array(
      '1' => t('Segunda-feira'),
      '2' => t('Terça-feira'),
      '3' => t('Quarta-feira'),
      '4' => t('Quinta-feira'),
      '5' => t('Sexta-feira')
    );
  }

  public static function daysMonth() {
    $days = array();
    for ($i = 1; $i <= 31; $i++) {
        $days[$i] = $i;
    }
    return $days;
  }

  public static function transferInterval() {
    return array(
      'daily' => t('Diário'),
      'weekly' => t('Semanal'),
      'monthly' => t('Mensal')
    );
  }

  public static function installmentsNumber() {
    $installments_number = array();
    for ($i = 1; $i <= 12; $i++) {
      $installments_number[$i] = $i;
    }
    return $installments_number;
  }

  public static function cardBrands() {
    return array(
      'amex' => 'Amex',
      'aura' => 'Aura', 
      'diners' => 'Diners',
      'discover' => 'Discover',
      'elo' => 'Elo',
      'hipercard' => 'Hipercard',
      'jcb' => 'JCB',
      'mastercard' => 'MasterCard',
      'visa' => 'Visa',
    );
  }

  public static function accountTypes() {
    return array(
      'conta_corrente' => t('Conta Corrente'),
      'conta_poupanca' => t('Conta Poupança'),
      'conta_corrente_conjunta' => t('Conta Corrente Conjunta'),
      'conta_poupanca_conjunta' => t('Conta Poupanca Conjunta')
    );
  }

  public static function banks() {
    return array(
      '104' => '104 - CAIXA ECONOMICA FEDERAL',
      '107' => '107 - BANCO BBM S/A',
      '136' => '136 - UNICRED - UNICRED OESTE CATARINENSE',
      '151' => '151 - BANCO NOSSA CAIXA S.A.',
      '208' => '208 - BANCO UBS PACTUAL S.A.',
      '212' => '212 - BANCO ORIGINAL',
      '213' => '213 - BANCO ARBI S.A.',
      '214' => '214 - BANCO DIBENS S.A.',
      '217' => '217 - BANCO JOHN DEERE S.A.',
      '218' => '218 - BANCO BONSUCESSO S.A.',
      '222' => '222 - BANCO CALYON BRASIL S.A.',
      '224' => '224 - BANCO FIBRA S.A.',
      '225' => '225 - BANCO BRASCAN S.A.',
      '229' => '229 - BANCO CRUZEIRO DO SUL S.A.',
      '230' => '230 - UNICARD BANCO MÚLTIPLO S.A.',
      '233' => '233 - BANCO GE CAPITAL S.A.',
      '237' => '237 - BANCO BRADESCO S.A.',
      '241' => '241 - BANCO CLASSICO S.A.',
      '243' => '243 - BANCO MÁXIMA S.A.',
      '246' => '246 - BANCO ABC BRASIL S.A.',
      '248' => '248 - BANCO BOAVISTA INTERATLANTICO S.A.',
      '249' => '249 - BANCO INVESTCRED UNIBANCO S.A.',
      '250' => '250 - BANCO SCHAHIN S.A.',
      '254' => '254 - PARANÁ BANCO S.A.',
      '263' => '263 - BANCO CACIQUE S.A.',
      '265' => '265 - BANCO FATOR S.A.',
      '266' => '266 - BANCO CEDULA S.A.',
      '300' => '300 - BANCO DE LA NACION ARGENTINA',
      '318' => '318 - BANCO BMG S.A.',
      '341' => '341 - BANCO ITAÚ S.A.',
      '356' => '356 - BANCO ABN AMRO REAL S.A.',
      '366' => '366 - BANCO SOCIETE GENERALE BRASIL S.A.',
      '370' => '370 - BANCO WESTLB DO BRASIL S.A.',
      '376' => '376 - BANCO J.P. MORGAN S.A.',
      '389' => '389 - BANCO MERCANTIL DO BRASIL S.A.',
      '394' => '394 - BANCO FINASA BMC S.A.',
      '399' => '399 - HSBC BANK BRASIL S.A. - BANCO MULTIPLO',
      '409' => '409 - UNIBANCO-UNIAO DE BANCOS BRASILEIROS S.A.',
      '412' => '412 - BANCO CAPITAL S.A.',
      '422' => '422 - BANCO SAFRA S.A.',
      '453' => '453 - BANCO RURAL S.A.',
      '456' => '456 - BANCO DE TOKYO-MITSUBISHI UFJ BRASIL S/A',
      '464' => '464 - BANCO SUMITOMO MITSUI BRASILEIRO S.A.',
      '477' => '477 - CITIBANK N.A.',
      '487' => '487 - DEUTSCHE BANK S.A. - BANCO ALEMAO',
      '488' => '488 - JPMORGAN CHASE BANK, NATIONAL ASSOCIATION',
      '492' => '492 - ING BANK N.V.',
      '494' => '494 - BANCO DE LA REPUBLICA ORIENTAL DEL URUGUAY',
      '495' => '495 - BANCO DE LA PROVINCIA DE BUENOS AIRES',
      '505' => '505 - BANCO CREDIT SUISSE (BRASIL) S.A.',
      '582' => '582 - UNICRED UNIÃO',
      '600' => '600 - BANCO LUSO BRASILEIRO S.A.',
      '604' => '604 - BANCO INDUSTRIAL DO BRASIL S.A.',
      '610' => '610 - BANCO VR S.A.',
      '611' => '611 - BANCO PAULISTA S.A.',
      '612' => '612 - BANCO GUANABARA S.A.',
      '613' => '613 - BANCO PECUNIA S.A.',
      '623' => '623 - BANCO PANAMERICANO S.A.',
      '626' => '626 - BANCO FICSA S.A.',
      '630' => '630 - BANCO INTERCAP S.A.',
      '633' => '633 - BANCO RENDIMENTO S.A.',
      '634' => '634 - BANCO TRIANGULO S.A.',
      '637' => '637 - BANCO SOFISA S.A.',
      '638' => '638 - BANCO PROSPER S.A.',
      '643' => '643 - BANCO PINE S.A.',
      '653' => '653 - BANCO INDUSVAL S.A.',
      '654' => '654 - BANCO A.J. RENNER S.A.',
      '655' => '655 - BANCO VOTORANTIM S.A.',
      '707' => '707 - BANCO DAYCOVAL S.A.',
      '719' => '719 - BANIF - BANCO INTERNACIONAL DO FUNCHAL (BRASIL), S.A.',
      '721' => '721 - BANCO CREDIBEL S.A.',
      '734' => '734 - BANCO GERDAU S.A',
      '735' => '735 - BANCO NEON S.A.',
      '738' => '738 - BANCO MORADA S.A.',
      '739' => '739 - BANCO BGN S.A.',
      '740' => '740 - BANCO BARCLAYS S.A.',
      '741' => '741 - BANCO RIBEIRAO PRETO S.A.',
      '743' => '743 - BANCO EMBLEMA S.A.',
      '745' => '745 - BANCO CITIBANK S.A.',
      '746' => '746 - BANCO MODAL S.A.',
      '747' => '747 - BANCO RABOBANK INTERNATIONAL BRASIL S.A.',
      '748' => '748 - BANCO COOPERATIVO SICREDI S.A.',
      '749' => '749 - BANCO SIMPLES S.A.',
      '751' => '751 - DRESDNER BANK BRASIL S.A. BANCO MULTIPLO',
      '752' => '752 - BANCO BNP PARIBAS BRASIL S.A.',
      '753' => '753 - NBC BANK BRASIL S. A. - BANCO MÚLTIPLO',
      '756' => '756 - BANCO COOPERATIVO DO BRASIL S.A. - BANCOOB',
      '757' => '757 - BANCO KEB DO BRASIL S.A.',
      '001' => '001 - BANCO DO BRASIL S.A.',
      '003' => '003 - BANCO DA AMAZONIA S.A.',
      '004' => '004 - BANCO DO NORDESTE DO BRASIL S.A.',
      '019' => '019 - BANCO AZTECA DO BRASIL S.A.',
      '021' => '021 - BANESTES S.A. BANCO DO ESTADO DO ESPIRITO SANTO',
      '025' => '025 - BANCO ALFA S.A',
      '033' => '033 - BANCO SANTANDER BANESPA S.A.',
      '037' => '037 - BANCO DO ESTADO DO PARÁ S.A.',
      '036' => '036 - Banco Bradesco BBI S/A',
      '040' => '040 - BANCO CARGILL S.A.',
      '041' => '041 - BANCO DO ESTADO DO RIO GRANDE DO SUL S.A.',
      '044' => '044 - BANCO BVA S.A.',
      '045' => '045 - BANCO OPPORTUNITY S.A.',
      '047' => '047 - BANCO DO ESTADO DE SERGIPE S.A.',
      '062' => '062 - HIPERCARD BANCO MÚLTIPLO S.A.',
      '063' => '063 - BANCO IBI S.A. - BANCO MÚLTIPLO',
      '065' => '065 - BANCO LEMON S.A.',
      '066' => '066 - BANCO MORGAN STANLEY S.A.',
      '069' => '069 - BPN BRASIL BANCO MÚLTIPLO S.A.',
      '070' => '070 - BRB - BANCO DE BRASILIA S.A.',
      '072' => '072 - BANCO RURAL MAIS S.A.',
      '073' => '073 - BB BANCO POPULAR DO BRASIL S.A.',
      '074' => '074 - BANCO J. SAFRA S.A.',
      '075' => '075 - BANCO CR2 S/A',
      '076' => '076 - BANCO KDB DO BRASIL S.A.',
      '077' => '077 - BANCO INTERMEDIUM S/A',
      '079' => '079 - JBS BANCO S/A',
      '081' => '081 - CONCÓRDIA BANCO S.A.',
      '084' => '084 - BANCO UNIPRIME NORTE DO PARANA',
      '085' => '085 - CECRED – Cooperativa Central de Crédito Urbano',
      '096' => '096 - BANCO BM&F DE SERVIÇOS DE LIQUIDAÇÃO E'
    );
  }

  public static function statusReadableName() {
    return array(
      'processing' => t('processando'),
      'authorized' => t('autorizado'),
      'paid' => t('pago'),
      'refunded' => t('estornado'),
      'waiting_payment' => t('aguardando pagamento'),
      'pending_refund' => t('aguardando para ser estornado'),
      'refused' => t('estornado'),
      'chargedback' => t('sofreu chargeback'),
    );
  }

  public static function createCompany($company_name) {
    date_default_timezone_set('America/Sao_Paulo');
    $http_client = \Drupal::service('http_client');
    $params = [
      'name' => $company_name . '_company',
      'email' => date('YmdHis') . '@pagarmemodule.com',
      'password' => 'password'
    ];
    $options = [
      'form_params' => $params,
    ];
    $response = $http_client->request('POST', 'https://api.pagar.me/1/companies/temporary', $options);
    if ($response->getStatusCode() === 200) {
      return json_decode($response->getBody());
    }
    return FALSE;
  }

  public static function getCardExpirationMonths() {
    // Build a month select list that shows months with a leading zero.
    $months = [];
    for ($i = 1; $i < 13; $i++) {
      $month = str_pad($i, 2, '0', STR_PAD_LEFT);
      $months[$month] = $month;
    }
    return $months;
  }
  
  public static function getCardExpirationYears() {
    // Build a year select list that uses a 4 digit key with a 2 digit value.
    $current_year_4 = date('Y');
    $current_year_2 = date('y');
    $years = [];
    for ($i = 0; $i < 10; $i++) {
      $years[$current_year_4 + $i] = $current_year_2 + $i;
    }
    return [
      'years' => $years,
      'current_year_2' => $current_year_2,
      'current_year_4' => $current_year_4,
    ];
  }

  public static function currencyAmountFormat($amount, $currency_code, $type = 'number') {
    $currency = Currency::load($currency_code);
    switch ($currency_code) {
      case 'BRL':
        if ($type === 'integer') {
          $amount = $amount / 100;
        }
        return $currency->getSymbol() . number_format($amount, $currency->getFractionDigits(), ',', '.');
        break;
      default:
        return $amount;
        break;
    }
  }

  public static function amountDecimalToInt($amount) {
    return intval($amount * 100);
  }

  public static function amountIntToDecimal($amount) {
    return strval($amount / 100);
  }

  public static function isDecimal($amount) {
    return is_numeric($amount) && floor($amount) != $amount;
  }
}
