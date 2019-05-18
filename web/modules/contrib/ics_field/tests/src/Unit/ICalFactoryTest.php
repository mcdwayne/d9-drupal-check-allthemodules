<?php

namespace Drupal\Tests\ics_field\Unit;

use Drupal\ics_field\ICalFactory;
use Drupal\ics_field\Normalizer\UrlNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @group ics_field
 */
class ICalFactoryTest extends UnitTestCase {

  /**
   * A valid calendar array of properties.
   *
   * @var array
   */
  protected $validCalendarProperties = [
    'timezone'           => 'Europe/Zurich',
    'product_identifier' => 'my domain',
    'summary'            => 'An exciting event',
    'description'        => 'with a lot more information',
    'dates_list'         => [
      "1970-01-01 01:00:00 Europe/Zurich",
      "1971-02-02 02:00:00 Europe/Zurich",
    ],
    'uuid'               => '123456789',
  ];

  /**
   * Tests calendar properties validation.
   *
   * @dataProvider schemeHttpHostProvider
   */
  public function testCheckPropertiesHavingAll($request) {
    $calendarUtil = new ICalFactory(new UrlNormalizer());
    $this->assertNotNull($calendarUtil);
  }

  /**
   * Tests generated calendar.
   *
   * @dataProvider schemeHttpHostProvider
   */
  public function testCalendarGeneration($request) {
    $calProperties = [
      'timezone'           => 'Europe/Zurich',
      'product_identifier' => 'my domain',
      'summary'            => 'An exciting event',
      'description'        => 'with a lot more information',
      'dates_list'         => [
        "1970-01-01 01:00:00 Europe/Zurich",
        "1971-02-02 02:00:00 Europe/Zurich",
      ],
      'uuid'               => '123456789',
    ];
    $expectedStrMd5 = '33d28e98e1cc215716067e69ec9bf058';
    $calendarUtil = new ICalFactory(new UrlNormalizer());
    // Ignore the DTSTAMP lines,they change constantly.
    $generatedStrMd5 = md5(preg_replace('#^DTSTAMP.*\n#m',
                                        '',
                                        $calendarUtil->generate($calProperties,
                                                                $request)));
    $this->assertEquals($expectedStrMd5, $generatedStrMd5);

    // Expected vcalendar string.
    // We are using on its md5 to check if it matched.
    /*
    VERSION:2.0
    PRODID:my domain
    X-WR-TIMEZONE:Europe/Zurich
    X-PUBLISHED-TTL:P1W
    BEGIN:VTIMEZONE
    TZID:Europe/Zurich
    X-LIC-LOCATION:Europe/Zurich
    END:VTIMEZONE
    BEGIN:VEVENT
    UID:e807f1fcf82d132f9bb018ca6738a19f
    DTSTART;TZID=Europe/Zurich:19700101T010000
    SEQUENCE:0
    TRANSP:OPAQUE
    SUMMARY:An exciting event
    CLASS:PUBLIC
    DESCRIPTION:with a lot more information
    X-ALT-DESC;FMTTYPE=text/html:with a lot more information
    DTSTAMP:20170110T185519Z
    END:VEVENT
    BEGIN:VEVENT
    UID:0f7e44a922df352c05c5f73cb40ba115
    DTSTART;TZID=Europe/Zurich:19710202T020000
    SEQUENCE:0
    TRANSP:OPAQUE
    SUMMARY:An exciting event
    CLASS:PUBLIC
    DESCRIPTION:with a lot more information
    X-ALT-DESC;FMTTYPE=text/html:with a lot more information
    DTSTAMP:20170110T185519Z
    END:VEVENT
    END:VCALENDAR
     */
  }

  /**
   * A data provider.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject[]
   *   The mock object for Symfony\Component\HttpFoundation\Request.
   */
  public function schemeHttpHostProvider() {
    $hosts = [
      'http://localhost',
      'https://localhost',
      'http://localhost:8081',
      'https://localhost:8081',
    ];
    $dataProvidedArray = [];
    foreach ($hosts as $host) {
      $scheme = preg_replace('#://.*#', '', $host);
      $schemeAndHttpHost = $host;

      $requestMock = $this->getMock(
        'Symfony\Component\HttpFoundation\Request',
        ['getScheme', 'getSchemeAndHttpHost']
      );

      $requestMock->method('getScheme')
                  ->will($this->returnValue($scheme));
      $requestMock->method('getSchemeAndHttpHost')
                  ->will($this->returnValue($schemeAndHttpHost));

      $dataProvidedArray[$host] = [$requestMock];
    }
    return $dataProvidedArray;
  }

}
