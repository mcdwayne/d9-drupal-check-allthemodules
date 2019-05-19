<?php

namespace Drupal\vat_number\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Unicode;
use SoapClient;
use SoapFault;

/**
 * Defines a controller to validate the VAT Number.
 */
class VatNumberController extends ControllerBase {
  private $vatNumber;
  private $vatInfo;
  private $cleint = ['message' => ''];
  private $valid = TRUE;

  /**
   * {@inheritdoc}
   */
  public function __construct($vatNumber) {
    $this->vatNumber = $vatNumber;
    $this->vatInfo = $this->getComponents();
    $this->cleint = $this->connectDatabaseVies();
  }

  /**
   * Checks the VAT number format on the database.
   */
  public function check() {
    $this->checkVatFormat();
    if (!isset($this->vatInfo['message'])) {
      if ($this->validateVatNumber() === FALSE) {
        $this->vatInfo['message'] = $this->t('The VAT number could not be validated by the European VAT Database. Please go back and input a correct VAT number.');
      }
      else {
        $this->vatInfo['message'] = NULL;
      }
    }
    return [
      'status' => $this->valid,
      'message' => $this->vatInfo['message'],
    ];
  }

  /**
   * Validate the VAT number on the EU database.
   */
  private function validateVatNumber() {
    if ($this->cleint) {
      $params = ['countryCode' => $this->vatInfo['country_code'], 'vatNumber' => $this->vatInfo['vatNumber']];
      try {
        $r = $this->cleint->checkVat($params);
        if (($r->valid != TRUE)) {
          $this->valid = FALSE;
          return FALSE;
        }
        return TRUE;
      }
      catch (SoapFault $e) {
        \Drupal::logger('var_number')->error($e->faultstring);
      }
    }
  }

  /**
   * Splits a VAT identification number to a clean country prefix and number.
   */
  private function getComponents() {
    // Some countries like DK use spaces in the formatting.
    // Maybe someone does that too for readability or uses dots.
    // We remove all dots, spaces and dashes because they are not
    // important for the validation operations and we do NOT regex spaces.
    $vatid = preg_replace('/[ .-]/', '', $this->vatNumber);

    // First two letters are always country code.
    $vat_infos['country_code'] = Unicode::strtoupper(Unicode::substr($vatid, 0, 2));
    $vat_infos['vatNumber'] = Unicode::strtoupper(Unicode::substr($vatid, 2));

    return $vat_infos;
  }

  /**
   * Try to connect with the VIES database.
   *
   * Catch PHP warnings if connection with the host is not possible.
   */
  private function connectDatabaseVies() {
    try {
      return new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl", ["exceptions" => 1]);
    }
    catch (SoapFault $e) {
      // Connection to host not possible, europe.eu down?
      \Drupal::logger('vat_number')->error($e->faultstring);
      return FALSE;
    }
  }

  /**
   * Checks the VAT Number on its format.
   */
  private function checkVatFormat() {

    // Check for a country code.
    switch ($this->vatInfo['country_code']) {

      /* info on the regexes in general. /^(AT){0,1}U[0-9]{8}$/i'; --- we use {0,1} because eventually we want to provide the country later by a second field formatter so tahts no error, but the function           needs to be rewriten for that*/

      // Austria.
      case 'AT':
        // ATU99999999, 1 block of 9 characters.
        $example['AT'] = $this->t('@example, AT + 9 characters, the first position following the prefix is always an U', [
          '@example' => 'ATU99999999',
        ]);
        $regex = '/^(AT){0,1}U[0-9]{8}$/i';
        break;

      // Belgium.
      case 'BE':
        // BE0999999999, 1 block of 10 digits.
        $example['BE'] = $this->t('@example, BE + 10 digits, the first digit following the prefix is always zero (0)', [
          '@example' => 'BE0999999999',
        ]);
        $regex = '/^(BE){0,1}[0]{1}[0-9]{9}$/i';
        break;

      // Bukgaria.
      case 'BG':
        // BG999999999 or BG9999999999
        // 1 block of 9 digits or 1 block of 10 digits.
        $example['BG'] = $this->t('@example1 or @example2, BG + 9 or 10 digits', [
          '@example1' => 'BG999999999',
          '@example2' => 'BG9999999999',
        ]);
        $regex = '/^(BG){0,1}[0-9]{9,10}$/i';
        break;

      // Cyprus.
      case 'CY':
        // CY99999999L, 1 block of 9 characters, ATTENTION L:, A letter.
        $example['CY'] = $this->t('@example, CY + 9 characters', [
          '@example' => 'CY99999999L',
        ]);
        $regex = '/^(CY){0,1}[0-9]{8}[A-Z]{1}$/i';
        break;

      // Czech Republic.
      case 'CZ':
        // CZ99999999 or CZ999999999 or CZ9999999999
        // 1 block of either 8, 9 or 10 digits.
        $example['CZ'] = $this->t('@example1 or @example2 or @example3, CZ + 8, 9 or 10 digits', [
          '@example1' => 'CZ99999999',
          '@example2' => 'CZ999999999',
          '@example3' => 'CZ9999999999',
        ]);
        $regex = '/^(CZ){0,1}[0-9]{8,10}$/i';
        break;

      // Denmark.
      case 'DK':
        // DK99 99 99 99 4 blocks of 2 digits.
        $example['DK'] = $this->t('@example, @block_count blocks of @digit_count digits', [
          '@example' => 'DK99 99 99 99',
          '@block_count' => 4,
          '@digit_count' => 2,
        ]);
        $regex = '/^(DK){0,1}([0-9]){8}$/i';
        break;

      // Estonia.
      case 'EE':
        // EE999999999 block of 9 digits.
        $example['EE'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'EE999999999',
          '@digit_count' => 9,
        ]);
        $regex = '/^(EE){0,1}[0-9]{9}$/i';
        break;

      // Germany.
      case 'DE':
        // DE999999999 1 block of 9 digits.
        $example['DE'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'DE999999999',
          '@digit_count' => 9,
        ]);
        $regex = '/^(DE){0,1}[0-9]{9}$/i';
        break;

      // Greece.
      case 'EL':
        // EL999999999, 1 block of 9 digits.
        $example['EL'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'EL999999999',
          '@digit_count' => 9,
        ]);
        $regex = '/^(EL){0,1}[0-9]{9}$/i';
        break;

      // Protugal.
      case 'PT':
        // PT999999999, 1 block of 9 digits.
        $example['PT'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'PT999999999',
          '@digit_count' => 9,
        ]);
        $regex = '/^(PT){0,1}[0-9]{9}$/i';
        break;

      // France.
      case 'FR':
        // FRXX 999999999, 1 block of 2 characters, 1 block of 9 digits,
        // X:, A letter or a digit.
        $example['FR'] = $this->t('@example, 1 block of 2 characters, 1 block of 9 digits', [
          '@example' => 'FRXX 999999999',
        ]);
        $regex = '/^(FR){0,1}[0-9A-Z]{2}[0-9]{9}$/i';
        break;

      // Finland.
      case 'FI':
        // FI99999999, 1 block of 8 digits.
        $example['FI'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'FI99999999',
          '@digit_count' => 8,
        ]);
        $regex = '/^(FI){0,1}[0-9]{8}$/i';
        break;

      // Croatia.
      case 'HR':
        // HU12345678901, 1 block of 11 digits.
        $example['HU'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'HU12345678901',
          '@digit_count' => 11,
        ]);
        $regex = '/^(HR){0,1}[0-9]{11}$/i';
        break;

      // Hungary.
      case 'HU':
        // HU99999999, 1 block of 8 digits.
        $example['HU'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'HU99999999',
          '@digit_count' => 8,
        ]);
        $regex = '/^(HU){0,1}[0-9]{8}$/i';
        break;

      // Luxembourg.
      case 'LU':
        // LU99999999, 1 block of 8 digits.
        $example['LU'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'LU99999999',
          '@digit_count' => 8,
        ]);
        $regex = '/^(LU){0,1}[0-9]{8}$/i';
        break;

      // Malta.
      case 'MT':
        // MT99999999, 1 block of 8 digits.
        $example['MT'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'MT99999999',
          '@digit_count' => 8,
        ]);
        $regex = '/^(MT){0,1}[0-9]{8}$/i';
        break;

      // Slovenia.
      case 'SI':
        // SI12345678 (8 characters).
        $example['SI'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'SI12345678',
          '@digit_count' => 8,
        ]);
        $regex = '/^(SI){0,1}[0-9]{8}$/i';
        break;

      // Ireland.
      case 'IE':
        // IE9S99999L, 1 block of 8 characters,
        // S: A letter; a digit; "+" or "*"  AND L:, A letter.
        $example['IE'] = $this->t('@example, 1 block of @char_count characters', [
          '@example' => 'IE9S99999L',
          '@char_count' => 8,
        ]);
        $regex = '/^(IE){0,1}[0-9][0-9A-Z\+\*][0-9]{5}[A-Z]$/i';
        break;

      // Italy.
      case 'IT':
        // IT99999999999, 1 block of 11 digits.
        $example['IT'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'IT99999999999',
          '@digit_count' => 11,
        ]);
        $regex = '/^(IT){0,1}[0-9]{11}$/i';
        break;

      // Latvia.
      case 'LV':
        // LV99999999999, 1 block of 11 digits.
        $example['LV'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'LV99999999999',
          '@digit_count' => 11,
        ]);
        $regex = '/^(LV){0,1}[0-9]{11}$/i';
        break;

      // Lithuania.
      case 'LT':
        // LT999999999 or LT999999999999
        // 1 block of 9 digits, or 1 block of 12 digits.
        $example['LT'] = $this->t('@example1 or @example2, 1 block of 9 digits, or 1 block of 12 digits', [
          '@example1' => 'LT999999999',
          '@example2' => 'LT999999999999',
        ]);
        $regex = '/^(LT){0,1}([0-9]{9}|[0-9]{12})$/i';
        break;

      // Netherlands.
      case 'NL':
        // NL999999999B99, 1 block of 12 characters,
        // The 10th position following the prefix is always "B".
        $example['NL'] = $this->t('@example, 1 block of @char_count characters', [
          '@example' => 'NL999999999B99',
          '@char_count' => 12,
        ]);
        $regex = '/^(NL){0,1}[0-9]{9}B[0-9]{2}$/i';
        break;

      // Poland.
      case 'PL':
        // PL9999999999, 1 block of 10 digits.
        $example['PL'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'PL9999999999',
          '@digit_count' => 10,
        ]);
        $regex = '/^(PL){0,1}[0-9]{10}$/i';
        break;

      // Slovakia.
      case 'SK':
        // SK9999999999, 1 block of 10 digits.
        $example['SK'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'SK9999999999',
          '@digit_count' => 10,
        ]);
        $regex = '/^(SK){0,1}[0-9]{10}$/i';
        break;

      // Romania.
      case 'RO':
        // RO999999999, 1 block of minimum 2 digits and maximum 10 digits.
        $example['RO'] = $this->t('@example, 1 block of minimum 2 digits and maximum 10 digits', [
          '@example' => 'RO999999999',
        ]);
        $regex = '/^(RO){0,1}[0-9]{2,10}$/i';
        break;

      // Sweden.
      case 'SE':
        // SE999999999999, 1 block of 12 digits.
        $example['SE'] = $this->t('@example, 1 block of @digit_count digits', [
          '@example' => 'SE999999999999',
          '@digit_count' => 12,
        ]);
        $regex = '/^(SE){0,1}[0-9]{12}$/i';
        break;

      // Spain.
      case 'ES':
        // ESX9999999X, 1 block of 9 characters,
        // ATTENTION X: A letter or a digit,
        // The first and last characters may be alpha or numeric;
        // but they may not both be numeric.
        $example['ES'] = $this->t('@example, 1 block of @char_count characters', [
          '@example' => 'ESX9999999X',
          '@char_count' => 9,
        ]);
        $regex = '/^(ES){0,1}([0-9A-Z][0-9]{7}[A-Z])|([A-Z][0-9]{7}[0-9A-Z])$/i';
        break;

      // United Kingdom.
      case 'GB':
        // GB999 9999 99      standard:
        // - 9 digits (block of 3, block of 4, block of 2).
        // GB999 9999 99 999  branch traders: 12 digits
        // - (as for 9 digits, followed by a block of 3 digits).
        // GBGD999            government departments:
        // - the letters GD then 3 digits orom 000 to 499 (e.g. GBGD001).
        // GBHA999            health authorities:
        // - the letters HA then 3 digits from 500 to 999 (e.g. GBHA599).
        $example['GB'] = $this->t('@example, 9 digits (block of 3, block of 4, block of 2)', [
          '@example' => 'GB999 9999 99',
        ]);
        $regex = '/^(GB){0,1}(([0-9]{9})|([0-9]{12})|((GD|HA)[0-9]{3}))$/i';
        break;

      default:
        // No valid country code, return all invalid data.
        $this->valid = FALSE;
        $this->vatInfo['vat_format'] = FALSE;
        $this->vatInfo['message'] = $this->t('The country of the VAT number can not be detected. Please do not remove the language prefix. Example: DE123456789 (where DE is the country prefix).');
        break;
    }

    // OK now check if the regex matched the supplied VAT.
    if ($this->valid) {
      $vatNumber = $this->vatInfo['country_code'] . $this->vatInfo['vatNumber'];
      $this->vatInfo['vat_format'] = preg_match($regex, $vatNumber);
    }

    // Output a message with info about wrong format if a country code was in
    // front but regex validation does not match in case the user added too
    // many numbers or forgot something else.
    $eu_countries = $this->euCountries();
    $valid_eu = isset($eu_countries[$this->vatInfo['country_code']]);

    if ($valid_eu && !$this->vatInfo['vat_format']) {
      $this->valid = FALSE;
      $this->vatInfo['message'] = $this->t("Your VAT number does not match the '%country' VAT format.<br />It must have the following format: <strong>%format</strong>",
        [
          '%country' => $eu_countries[$this->vatInfo['country_code']],
          '%format' => $example[$this->vatInfo['country_code']],
        ]);
    }
  }

  /**
   * A list of valid countries of the EU.
   *
   * @return array
   *   A list of EU countries, key is country code, value is readable name.
   */
  public function euCountries() {

    // Necessary for country_get_list().
    $countries = \Drupal::service('country_manager')->getList();

    // ISO 3166.
    $eu_country_codes = [
      "AT",
      "BE",
      "BG",
      "CY",
      "CZ",
      "DE",
      "DK",
      "EE",
      "ES",
      "FI",
      "FR",
      "GB",
      "GR",
      "HR",
      "HU",
      "IE",
      "IT",
      "LT",
      "LU",
      "LV",
      "MT",
      "NL",
      "PL",
      "PT",
      "RO",
      "SE",
      "SI",
      "SK",
    ];

    // Merge in country names from country_get_list().
    foreach ($eu_country_codes as $value) {
      $eu_countries[$value] = $countries[$value];
    }
    return $eu_countries;
  }

}
