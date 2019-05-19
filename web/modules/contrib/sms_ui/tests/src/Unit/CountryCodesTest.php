<?php

namespace Drupal\Tests\sms_ui\Unit;

use Drupal\sms_ui\Utility\CountryCodes;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\sms_ui\Utility\CountryCodes
 * @group SMS UI
 */
class CountryCodesTest extends UnitTestCase  {

  /**
   * @dataProvider providerCountryCodes
   */
  public function testGetCountryCode($number, $code, $country, $valid) {
    $this->assertEquals($code, CountryCodes::getCountryCode($number));
  }

  /**
   * @dataProvider providerCountryCodes
   */
  public function testGetCountryForCode($number, $code, $country, $valid) {
    $this->markTestIncomplete('Needs container to be mocked for StringTranslation');
//    $this->assertEquals($country, CountryCodes::getCountryForCode($code));
  }

  /**
   * @dataProvider providerCountryCodes
   */
  public function testGetCodeForCountry($number, $code, $country, $valid) {
    $this->markTestIncomplete('Needs container to be mocked for StringTranslation');
//    $this->assertEquals($code, CountryCodes::getCodeForCountry($country));
  }

  /**
   * @dataProvider providerCountryCodes
   */
  public function testIsValidCode($number, $code, $country, $valid) {
    $this->markTestIncomplete('Needs container to be mocked for StringTranslation');
//    $this->assertEquals($valid, CountryCodes::isValidCode($code));
  }

  public function providerCountryCodes() {
    return [
      ['0123456789', -1, '', TRUE],
      ['23482356789011', 234, 'Nigeria', TRUE],
      ['23582356789011', 235, 'Chad', TRUE],
      ['448235678901', 44, 'UK / Isle of Man / Jersey / Guernsey', TRUE],
      ['6534823567890', 65, 'Singapore', TRUE],
      ['6734823567890', 673, 'Brunei', TRUE],
      ['8934823567890', NULL, '', FALSE],
      ['5964823567890', 596, 'Martinique', TRUE],
      ['4204823567890', 420, 'Czech Republic', TRUE],
      ['5064823567890', NULL, '', FALSE],
      ['8004823567890', NULL, '', FALSE],
      ['182356789011', 1, 'USA / Canada / Dominican Rep. / Puerto Rico', TRUE],
    ];
  }

}
