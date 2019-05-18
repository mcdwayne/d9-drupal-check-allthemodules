<?php

namespace Drupal\Tests\agreement\Unit\Entity;

use Drupal\agreement\Entity\Agreement;
use Drupal\Tests\UnitTestCase;

/**
 * Tests methods on the agreement entity.
 *
 * @group agreement
 */
class AgreementTest extends UnitTestCase {

  /**
   * Default agreement settings from agreement.agreement.default.
   *
   * @todo Add visibility in Drupal 9 when PHP 5 is non-supported.
   *
   * @internal
   */
  const DEFAULT_AGREEMENT_SETTINGS = [
    'visibility' => ['settings' => 0, 'pages' => []],
    'roles' => ['authenticated'],
    'frequency' => -1,
    'title' => 'Our Agreement',
    'checkbox' => 'I agree.',
    'submit' => 'Submit',
    'success' => 'Thank you for accepting our agreement.',
    'revoked' => 'You have successfully revoked your acceptance of our agreement.',
    'failure' => 'You must accept our agreement to continue.',
    'destination' => '',
    'recipient' => '',
    'reset_date' => 0,
    'format' => 'plain_text',
  ];

  /**
   * Asserts that settings are populated.
   *
   * @param array $expected
   *   The expected output.
   * @param array|null $settings
   *   The configuration settings.
   *
   * @dataProvider settingsProvider
   */
  public function testGetSettings(array $expected, $settings) {
    $agreement = new Agreement([
      'id' => 'test',
      'label' => 'Test',
      'path' => '/agreement',
      'agreement' => 'Agree',
      'settings' => $settings,
    ], 'agreement');

    $this->assertArrayEquals($expected, $agreement->getSettings());
  }

  /**
   * Asserts the frequency timestamp based on reset_date and frequency.
   *
   * @param int $lessThan
   *   The expected value to be equal to or less than. This is not the exact
   *   value because it changes by the second.
   * @param array|null $settings
   *   The settings to use.
   *
   * @dataProvider frequencyTimestampProvider
   */
  public function testGetAgreementFrequencyTimestamp($lessThan, $settings) {
    $agreement = new Agreement([
      'id' => 'test',
      'label' => 'Test',
      'path' => '/agreement',
      'agreement' => 'Agree',
      'settings' => $settings,
    ], 'agreement');
    $this->assertLessThanOrEqual($lessThan, $agreement->getAgreementFrequencyTimestamp());
  }

  /**
   * Provides various settings and expected values.
   *
   * @return array
   *   An array of test arguments.
   */
  public function frequencyTimestampProvider() {
    $defaults = Agreement::getDefaultSettings();
    $no_reset_date = self::DEFAULT_AGREEMENT_SETTINGS;
    unset($no_reset_date['reset_date']);
    $frequency_set = self::DEFAULT_AGREEMENT_SETTINGS;
    $frequency_set['frequency'] = 3600;

    return [
      'no settings provided' => [0, $defaults],
      'default agreement' => [0, self::DEFAULT_AGREEMENT_SETTINGS],
      'no reset date' => [0, $no_reset_date],
      'frequency > reset_date' => [time(), $frequency_set],
    ];
  }

  /**
   * Provides various settings arrays for tests.
   *
   * @return array
   *   An array of test arguments.
   */
  public function settingsProvider() {
    $defaults = Agreement::getDefaultSettings();
    $no_reset_date = self::DEFAULT_AGREEMENT_SETTINGS;
    unset($no_reset_date['reset_date']);

    return [
      'no settings provided' => [$defaults, NULL],
      'empty settings provided' => [$defaults, []],
      'default agreement' => [self::DEFAULT_AGREEMENT_SETTINGS, self::DEFAULT_AGREEMENT_SETTINGS],
      'no reset date' => [self::DEFAULT_AGREEMENT_SETTINGS, $no_reset_date],
    ];
  }

}
