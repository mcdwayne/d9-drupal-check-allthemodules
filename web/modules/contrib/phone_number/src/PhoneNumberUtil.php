<?php

namespace Drupal\phone_number;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil as LibPhoneNumberUtil;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use Drupal\phone_number\Exception\CountryException;
use Drupal\phone_number\Exception\ParseException;
use Drupal\phone_number\Exception\PhoneNumberException;
use Drupal\phone_number\Exception\TypeException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The Phone Number field utility class.
 */
class PhoneNumberUtil implements PhoneNumberUtilInterface {
  use StringTranslationTrait;

  /**
   * The PhoneNumberUtil object.
   *
   * @var \libphonenumber\PhoneNumberUtil
   */
  public $libUtil;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * The field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  public $fieldMananger;

  /**
   * The country manager service.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  public $countryManager;

  /**
   * PhoneNumberUtil constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   Field manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   Country manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $field_manager, ModuleHandlerInterface $module_handler, CountryManagerInterface $country_manager) {
    $this->libUtil = LibPhoneNumberUtil::getInstance();
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->countryManager = $country_manager;
    $this->fieldMananger = $field_manager;
  }

  /**
   * Strip non-digits from a string.
   *
   * @param string $string
   *   The input string, potentially with non-digits.
   *
   * @return string
   *   The input string with non-digits removed.
   */
  protected function stripNonDigits($string) {
    return preg_replace('~\D~', '', $string);
  }

  /**
   * {@inheritdoc}
   */
  public function libUtil() {
    return $this->libUtil;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhoneNumber($number, $country = NULL, $extension = NULL) {
    try {
      return $this->testPhoneNumber($number, $country, $extension);
    }
    catch (PhoneNumberException $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testPhoneNumber($number, $country = NULL, $extension = NULL, $types = NULL) {
    try {
      /** @var \libphonenumber\PhoneNumber $phone_number */
      $phone_number = $this->libUtil->parse($number, $country);
      if ($extension) {
        $phone_number->setExtension($extension);
      }
    }
    catch (NumberParseException $e) {
      throw new ParseException('Invalid number', NULL, $e);
    }

    $number_country = $this->libUtil->getRegionCodeForNumber($phone_number);

    if ($country && ($number_country != $country)) {
      throw new CountryException("Phone number's country and the country provided do not match", $number_country);
    }

    $number_type = $this->libUtil->getNumberType($phone_number);

    if ($types && !in_array($number_type, $types)) {
      throw new TypeException("Phone number's type is not allowed", $number_type);
    }

    return $phone_number;
  }

  /**
   * {@inheritdoc}
   */
  public function getCallableNumber(PhoneNumber $phone_number, $strip_non_digits = FALSE, $strip_extension = TRUE) {
    if ($strip_extension) {
      $copy = clone $phone_number;
      $copy->clearExtension();
      $callable = $this->libUtil->format($copy, PhoneNumberFormat::E164);
    }
    else {
      $callable = $this->libUtil->format($phone_number, PhoneNumberFormat::E164);
    }

    if ($callable && $strip_non_digits) {
      $callable = $this->stripNonDigits($callable);
    }

    return $callable;
  }

  /**
   * {@inheritdoc}
   */
  public function getNationalDialingPrefix(PhoneNumber $phone_number, $strip_non_digits = FALSE) {
    if (!$phone_number) {
      return NULL;
    }

    $region_code = $this->libUtil->getRegionCodeForNumber($phone_number);
    return $this->libUtil->getNddPrefixForRegion($region_code, $strip_non_digits);
  }

  /**
   * {@inheritdoc}
   */
  public function getNationalNumber(PhoneNumber $phone_number, $strip_non_digits = FALSE, $strip_extension = TRUE) {
    if ($strip_extension) {
      $copy = clone $phone_number;
      $copy->clearExtension();
      $national = $copy->getNationalNumber();
    }
    else {
      $national = $phone_number->getNationalNumber();
    }

    if ($national && $strip_non_digits) {
      $national = $this->stripNonDigits($national);
    }

    return $national;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalNumber(PhoneNumber $phone_number, $strip_non_digits = FALSE, $strip_extension = TRUE) {
    if ($strip_extension) {
      $copy = clone $phone_number;
      $copy->clearExtension();
      $local = $this->libUtil->format($copy, PhoneNumberFormat::NATIONAL);
    }
    else {
      $local = $this->libUtil->format($phone_number, PhoneNumberFormat::NATIONAL);
    }

    if ($local && $strip_non_digits) {
      $local = $this->stripNonDigits($local);
    }

    return $local;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountry(PhoneNumber $phone_number) {
    return $phone_number ? $this->libUtil()
      ->getRegionCodeForNumber($phone_number) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryCode($country) {
    return $this->libUtil->getCountryCodeForRegion($country);
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryOptions(array $filter = NULL, $show_country_names = FALSE) {

    $libUtil = $this->libUtil;
    $regions = $libUtil->getSupportedRegions();
    $countries = [];

    foreach ($regions as $country) {
      $code = $libUtil->getCountryCodeForRegion($country);
      if (!$filter || !empty($filter[$country])) {
        $name = $this->getCountryName($country);
        $countries[$country] = ($show_country_names && $name) ? "$name (+$code)" : "$country (+$code)";
      }
    }

    asort($countries);
    return $countries;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryName($country) {
    $drupal_countries = $this->countryManager->getList();

    return !empty($drupal_countries[$country]) ? $drupal_countries[$country] : $country;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeOptions() {
    $options = [];
    foreach (PhoneNumberType::values() as $type => $label) {
      switch ($type) {
        case PhoneNumberType::FIXED_LINE:
          $options[$type] = $this->t('Fixed line');
          break;

        case PhoneNumberType::MOBILE:
          $options[$type] = $this->t('Mobile');
          break;

        case PhoneNumberType::FIXED_LINE_OR_MOBILE:
          $options[$type] = $this->t('Fixed line or mobile');
          break;

        case PhoneNumberType::TOLL_FREE:
          $options[$type] = $this->t('Toll-free');
          break;

        case PhoneNumberType::PREMIUM_RATE:
          $options[$type] = $this->t('Premium rate');
          break;

        case PhoneNumberType::SHARED_COST:
          $options[$type] = $this->t('Shared cost');
          break;

        case PhoneNumberType::VOIP:
          $options[$type] = $this->t('VOIP');
          break;

        case PhoneNumberType::PERSONAL_NUMBER:
          $options[$type] = $this->t('Personal number');
          break;

        case PhoneNumberType::PAGER:
          $options[$type] = $this->t('Pager');
          break;

        case PhoneNumberType::UAN:
          $options[$type] = $this->t('UAN');
          break;

        case PhoneNumberType::UNKNOWN:
          $options[$type] = $this->t('Unknown');
          break;

        case PhoneNumberType::EMERGENCY:
          $options[$type] = $this->t('Emergency');
          break;

        case PhoneNumberType::VOICEMAIL:
          $options[$type] = $this->t('Voicemail');
          break;

        case PhoneNumberType::SHORT_CODE:
          $options[$type] = $this->t('Short code');
          break;

        case PhoneNumberType::STANDARD_RATE:
          $options[$type] = $this->t('Standard rate');
          break;

        default:
          // At the time of writing this is everyting, but let's make sure we're
          // covered if types are ever added/changed in the upstream library.
          $options[$type] = $this->t($label);
      }
    }
    return $options;
  }

}
