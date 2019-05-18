<?php

namespace Drupal\sms_phone_number;

use libphonenumber\PhoneNumber;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\phone_number\PhoneNumberUtil;
use Drupal\user\Entity\User;

/**
 * The SMS Phone Number field utility class.
 */
class SmsPhoneNumberUtil extends PhoneNumberUtil implements SmsPhoneNumberUtilInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  public $token;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  public $flood;

  /**
   * SmsPhoneNumberUtil constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   Field manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   Country manager.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   Flood manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $field_manager, ModuleHandlerInterface $module_handler, CountryManagerInterface $country_manager, Token $token, FloodInterface $flood) {
    parent::__construct($config_factory, $field_manager, $module_handler, $country_manager, $token);
    $this->token = $token;
    $this->flood = $flood;
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
  public function checkFlood(PhoneNumber $phone_number, $type = 'verification') {
    switch ($type) {
      case 'verification':
        return $this->flood->isAllowed('phone_number_verification', $this::VERIFY_ATTEMPTS_COUNT, $this::VERIFY_ATTEMPTS_INTERVAL, $this->getCallableNumber($phone_number));

      case 'sms':
        return $this->flood->isAllowed('phone_number_sms', $this::SMS_ATTEMPTS_COUNT, $this::SMS_ATTEMPTS_INTERVAL, $this->getCallableNumber($phone_number)) &&
          $this->flood->isAllowed('phone_number_sms_ip', $this::SMS_ATTEMPTS_COUNT * 5, $this::SMS_ATTEMPTS_INTERVAL * 5);

      default:
        return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getToken(PhoneNumber $phone_number) {
    if (!empty($_SESSION['phone_number_verification'][$this->getCallableNumber($phone_number)]['token'])) {
      return $_SESSION['phone_number_verification'][$this->getCallableNumber($phone_number)]['token'];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function sendVerification(PhoneNumber $phone_number, $message, $code, array $token_data = []) {
    $message = $this->t($message);
    $message = str_replace('!code', $code, $message);
    $message = str_replace('!site_name', $this->configFactory->get('system.site')
      ->get('name'), $message);

    $message = $this->token->replace($message, $token_data);

    $this->flood->register('phone_number_sms', $this::SMS_ATTEMPTS_INTERVAL, $this->getCallableNumber($phone_number));
    $this->flood->register('phone_number_sms_ip', $this::SMS_ATTEMPTS_INTERVAL * 5);

    if ($this->sendSms($this->getCallableNumber($phone_number), $message)) {
      $token = $this->registerVerificationCode($phone_number, $code);

      $_SESSION['phone_number_verification'][$this->getCallableNumber($phone_number)] = [
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
  public function registerVerificationCode(PhoneNumber $phone_number, $code) {
    $time = time();
    $token = \Drupal::csrfToken()
      ->get(rand(0, 999999999) . $time . 'phone verification token' . $this->getCallableNumber($phone_number));
    $hash = $this->codeHash($phone_number, $token, $code);

    \Drupal::database()->insert('phone_number_verification')
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
  public function verifyCode(PhoneNumber $phone_number, $code, $token = NULL) {
    $token = $token ? $token : $this->getToken($phone_number);
    if ($code && $token) {
      $hash = $this->codeHash($phone_number, $token, $code);
      $query = \Drupal::database()->select('phone_number_verification', 'm');
      $query->fields('m', ['token'])
        ->condition('token', $token)
        ->condition('timestamp', time() - (60 * 60 * 24), '>')
        ->condition('verification_code', $hash);
      $result = $query->execute()->fetchAssoc();

      if ($result) {
        $_SESSION['phone_number_verification'][$this->getCallableNumber($phone_number)]['verified'] = TRUE;
        return TRUE;
      }

      $this->flood->register('phone_number_verification', $this::VERIFY_ATTEMPTS_INTERVAL, $this->getCallableNumber($phone_number));

      return FALSE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isVerified(PhoneNumber $phone_number) {
    return !empty($_SESSION['phone_number_verification'][$this->getCallableNumber($phone_number)]['verified']);
  }

  /**
   * {@inheritdoc}
   */
  public function codeHash(PhoneNumber $phone_number, $token, $code) {
    $number = $this->getCallableNumber($phone_number);
    $secret = $this->configFactory->getEditable('phone_number.settings')
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
      $callback = 'phone_number_send_sms';
    }
    $module_handler->alter('phone_number_send_sms_callback', $callback);
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
    $tfa_field = $this->configFactory->get('phone_number.settings')
      ->get('tfa_field');
    $user_fields = $this->fieldMananger->getFieldDefinitions('user', 'user');
    return $this->isTfaEnabled() && !empty($user_fields[$tfa_field]) ? $tfa_field : '';
  }

  /**
   * {@inheritdoc}
   */
  public function setTfaField($field_name) {
    $this->configFactory->getEditable('phone_number.settings')
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
