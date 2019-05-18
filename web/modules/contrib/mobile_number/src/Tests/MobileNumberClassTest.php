<?php

namespace Drupal\mobile_number\Tests;

use Drupal\mobile_number\MobileNumberUtilInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Mobile number class functionality.
 *
 * @group mobile_number
 */
class MobileNumberClassTest extends WebTestBase {

  public static $modules = ['mobile_number', 'sms'];

  /**
   * Mobile number util.
   *
   * @var \Drupal\mobile_number\MobileNumberUtilInterface
   */
  public $util;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  public $flood;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->util = \Drupal::service('mobile_number.util');
    $this->flood = \Drupal::service('flood');
  }

  /**
   * Test number validation.
   */
  public function testNumberValidity() {

    $local_numbers = [
      '+972502345678' => 'International IL',
      '091234567' => 'None mobile IL',
      '0502345678' => 'Valid IL',
      '111' => 'Invalid IL',
      NULL => 'Empty',
    ];

    $countries = [
      'IL' => 'IL',
      'US' => 'US',
      NULL => 'Empty',
    ];

    foreach ($countries as $country => $country_text) {
      foreach ($local_numbers as $number => $number_text) {
        $valid = TRUE;
        try {
          $this->util->testMobileNumber($number, $country);
        }
        catch (\Exception $e) {
          $valid = FALSE;
        }

        $supposed_valid = FALSE;
        switch ($country) {
          case 'IL':
            $supposed_valid = $number == '+972502345678' || $number == '0502345678';
            break;

          case NULL:
            $supposed_valid = $number == '+972502345678';
            break;
        }

        $success_text = $supposed_valid ? 'valid' : 'invalid';

        $this->assertTrue($valid == $supposed_valid, "$country_text country, $number_text number, is $success_text.");
      }
    }

  }

  /**
   * Test functions.
   */
  public function testFunctions() {
    $int = '+972502345678';
    $mobile_number = $this->util->getMobileNumber($int);

    $this->assertTrue($this->util->getCountryCode('IL') == 972, "getCountryCode()");
    $this->assertTrue(count($this->util->getCountryOptions()), "getCountryOptions()");
    $this->assertTrue(count($this->util->getCountryOptions(['IL' => 'IL'])) == 1, "getCountryOptions() filtered");
    $this->assertTrue($this->util->getCountryName('IL') == 'Israel', "getCountryName()");

    $code = $this->util->generateVerificationCode(6);
    $this->assertTrue(strlen($code) == 6, "generateVerificationCode()");

    $token = $this->util->registerVerificationCode($mobile_number, $code);
    $this->assertTrue(strlen($token) == 43, "registerVerificationCode()");

    $this->assertTrue($this->util->checkFlood($mobile_number), "checkFlood() success");

    $token2 = $this->util->sendVerification($mobile_number, 'test', $code);
    $this->assertTrue(strlen($token2) == 43 && $token2 != $token, "sendVerification()");

    $this->assertTrue($this->util->getToken($mobile_number) == $token2, "getToken()");

    $this->assertFalse($this->util->verifyCode($mobile_number, '000', $token), "verifyCode() failure");

    for ($i = 0; $i < MobileNumberUtilInterface::VERIFY_ATTEMPTS_COUNT; $i++) {
      $this->util->verifyCode($mobile_number, '000', $token);
    }

    $this->assertFalse($this->util->checkFlood($mobile_number), "checkFlood() failure");
    $this->flood->clear('mobile_number_verification', $int);

    $this->assertFalse($this->util->isVerified($mobile_number), "isVerified() failure");
    $this->assertTrue($this->util->verifyCode($mobile_number, $code, $token), "verifyCode() success");
    $this->assertTrue($this->util->isVerified($mobile_number), "isVerified() success");

    $this->assertTrue(strlen($this->util->codeHash($mobile_number, $token, $code)) == 40, "codeHash()");

  }

}
