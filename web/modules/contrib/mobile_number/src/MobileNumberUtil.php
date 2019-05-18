<?php

namespace Drupal\mobile_number;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use Drupal\mobile_number\Exception\MobileNumberException;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\user\Entity\User;

/**
 * Turns a render array into a HTML string.
 */
class MobileNumberUtil implements MobileNumberUtilInterface {

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
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  public $flood;

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
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  public $token;

  /**
   * MobileNumberUtil constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   Flood manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   Field manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   Country manager.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FloodInterface $flood, EntityFieldManagerInterface $field_manager, ModuleHandlerInterface $module_handler, CountryManagerInterface $country_manager, Token $token) {
    $this->libUtil = PhoneNumberUtil::getInstance();
    $this->moduleHandler = $module_handler;
    $this->flood = $flood;
    $this->configFactory = $config_factory;
    $this->countryManager = $country_manager;
    $this->fieldMananger = $field_manager;
    $this->token = $token;
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
  public function getMobileNumber($number, $country = NULL, $types = [
    1 => 1,
    2 => 2,
  ]) {
    try {
      return $this->testMobileNumber($number, $country, $types);
    }
    catch (MobileNumberException $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testMobileNumber($number, $country = NULL, $types = [
    1 => 1,
    2 => 2,
  ]) {

    if (!$number) {
      throw new MobileNumberException('Empty number', MobileNumberException::ERROR_NO_NUMBER);
    }

    try {
      /** @var \libphonenumber\PhoneNumber $phone_number */
      $phone_number = $this->libUtil->parse($number, $country);
    }
    catch (NumberParseException $e) {
      throw new MobileNumberException('Invalid number or unknown country', MobileNumberException::ERROR_INVALID_NUMBER);
    }

    if ($types) {
      if (!in_array($this->libUtil->getNumberType($phone_number), $types)) {
        throw new MobileNumberException('Not a mobile number', MobileNumberException::ERROR_WRONG_TYPE);
      }
    }

    $mcountry = $this->libUtil->getRegionCodeForNumber($phone_number);

    if ($country && ($mcountry != $country)) {
      throw new MobileNumberException('Mismatch country with the number\'s prefix', MobileNumberException::ERROR_WRONG_COUNTRY);
    }

    return $phone_number;
  }

  /**
   * {@inheritdoc}
   */
  public function getCallableNumber(PhoneNumber $mobile_number) {
    return $mobile_number ? $this->libUtil->format($mobile_number, PhoneNumberFormat::E164) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalNumber(PhoneNumber $mobile_number) {
    if (!$mobile_number) {
      return NULL;
    }

    $region_code = $this->libUtil->getRegionCodeForNumber($mobile_number);
    $prefix = $this->libUtil->getNddPrefixForRegion($region_code, TRUE);
    $national_number = $mobile_number->getNationalNumber();

    return $prefix . $national_number;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountry(PhoneNumber $mobile_number) {
    return $mobile_number ? $this->libUtil()
      ->getRegionCodeForNumber($mobile_number) : NULL;
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
  public function getCountryOptions($filter = [], $show_country_names = FALSE) {

    $libUtil = $this->libUtil;
    $regions = $libUtil->getSupportedRegions();
    $countries = [];

    foreach ($regions as $region => $country) {
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
  public function generateVerificationCode($length = 4) {
    return str_pad((string) rand(0, pow(10, $length)), $length, '0', STR_PAD_LEFT);
  }

  /**
   * {@inheritdoc}
   */
  public function checkFlood(PhoneNumber $mobile_number, $type = 'verification') {
    switch ($type) {
      case 'verification':
        return $this->flood->isAllowed('mobile_number_verification', $this::VERIFY_ATTEMPTS_COUNT, $this::VERIFY_ATTEMPTS_INTERVAL, $this->getCallableNumber($mobile_number));

      case 'sms':
        return $this->flood->isAllowed('mobile_number_sms', $this::SMS_ATTEMPTS_COUNT, $this::SMS_ATTEMPTS_INTERVAL, $this->getCallableNumber($mobile_number)) &&
          $this->flood->isAllowed('mobile_number_sms_ip', $this::SMS_ATTEMPTS_COUNT * 5, $this::SMS_ATTEMPTS_INTERVAL * 5);

      default:
        return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getToken(PhoneNumber $mobile_number) {
    if (!empty($_SESSION['mobile_number_verification'][$this->getCallableNumber($mobile_number)]['token'])) {
      return $_SESSION['mobile_number_verification'][$this->getCallableNumber($mobile_number)]['token'];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function sendVerification(PhoneNumber $mobile_number, $message, $code, $token_data = []) {
    $message = t($message);
    $message = str_replace('!code', $code, $message);
    $message = str_replace('!site_name', $this->configFactory->get('system.site')
      ->get('name'), $message);

    $message = $this->token->replace($message, $token_data);

    $this->flood->register('mobile_number_sms', $this::SMS_ATTEMPTS_INTERVAL, $this->getCallableNumber($mobile_number));
    $this->flood->register('mobile_number_sms_ip', $this::SMS_ATTEMPTS_INTERVAL * 5);

    if ($this->sendSms($this->getCallableNumber($mobile_number), $message)) {
      $token = $this->registerVerificationCode($mobile_number, $code);

      $_SESSION['mobile_number_verification'][$this->getCallableNumber($mobile_number)] = [
        'token' => $token,
        'verified' => FALSE,
      ];

      return $token;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function registerVerificationCode(PhoneNumber $mobile_number, $code) {
    $time = time();
    $token = \Drupal::csrfToken()
      ->get(rand(0, 999999999) . $time . 'mobile verification token' . $this->getCallableNumber($mobile_number));
    $hash = $this->codeHash($mobile_number, $token, $code);

    \Drupal::database()->insert('mobile_number_verification')
      ->fields([
        'token' => $token,
        'timestamp' => $time,
        'verification_code' => $hash,
      ])
      ->execute();

    return $token;
  }

  /**
   * {@inheritdoc}
   */
  public function verifyCode(PhoneNumber $mobile_number, $code, $token = NULL) {
    $token = $token ? $token : $this->getToken($mobile_number);
    if ($code && $token) {
      $hash = $this->codeHash($mobile_number, $token, $code);
      $query = \Drupal::database()->select('mobile_number_verification', 'm');
      $query->fields('m', ['token'])
        ->condition('token', $token)
        ->condition('timestamp', time() - (60 * 60 * 24), '>')
        ->condition('verification_code', $hash);
      $result = $query->execute()->fetchAssoc();

      if ($result) {
        $_SESSION['mobile_number_verification'][$this->getCallableNumber($mobile_number)]['verified'] = TRUE;
        return TRUE;
      }

      $this->flood->register('mobile_number_verification', $this::VERIFY_ATTEMPTS_INTERVAL, $this->getCallableNumber($mobile_number));

      return FALSE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isVerified(PhoneNumber $mobile_number) {
    return !empty($_SESSION['mobile_number_verification'][$this->getCallableNumber($mobile_number)]['verified']);
  }

  /**
   * {@inheritdoc}
   */
  public function codeHash(PhoneNumber $mobile_number, $token, $code) {
    $number = $this->getCallableNumber($mobile_number);
    $secret = $this->configFactory->getEditable('mobile_number.settings')
      ->get('verification_secret');
    return sha1("$number$secret$token$code");
  }

  /**
   * {@inheritdoc}
   */
  public function smsCallback() {
    $module_handler = $this->moduleHandler;
    $callback = [];

    if ($module_handler->moduleExists('sms')) {
      $callback = 'mobile_number_send_sms';
    }
    $module_handler->alter('mobile_number_send_sms_callback', $callback);
    return is_callable($callback) ? $callback : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function sendSms($number, $message) {
    $callback = $this->smsCallback();

    if (!$callback) {
      return FALSE;
    }

    return call_user_func($callback, $number, $message);
  }

  /**
   * {@inheritdoc}
   */
  public function isSmsEnabled() {
    return $this->smsCallback() ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function tfaAccountNumber($uid) {
    $user = User::load($uid);
    $field_name = $this->getTfaField();
    if (
      $this->isTfaEnabled() &&
      $field_name &&
      !empty($user->get($field_name)->getValue()[0]['value']) &&
      !empty($user->get($field_name)->getValue()[0]['tfa'])
    ) {
      return $user->get($field_name)->getValue()[0]['value'];
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTfaField() {
    $tfa_field = $this->configFactory->get('mobile_number.settings')
      ->get('tfa_field');
    $user_fields = $this->fieldMananger->getFieldDefinitions('user', 'user');
    return $this->isTfaEnabled() && !empty($user_fields[$tfa_field]) ? $tfa_field : '';
  }

  /**
   * {@inheritdoc}
   */
  public function setTfaField($field_name) {
    $this->configFactory->getEditable('mobile_number.settings')
      ->set('tfa_field', $field_name)
      ->save(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function isTfaEnabled() {
    return $this->configFactory->get('tfa.settings')
      ->get('enabled') && $this->isSmsEnabled();
  }

}
