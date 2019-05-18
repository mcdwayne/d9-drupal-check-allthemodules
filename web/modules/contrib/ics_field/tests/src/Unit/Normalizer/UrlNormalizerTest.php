<?php

namespace Drupal\Tests\ics_field\Unit\Normalizer;

use Drupal\ics_field\Normalizer\UrlNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * Class UrlNormalizerTest
 *
 * @group ics_field
 */
class UrlNormalizerTest extends UnitTestCase {

  /**
   * Tests URL normalization.
   *
   * @dataProvider schemeHttpHostProvider
   */
  public function testNormalizeUrlEmpty($scheme, $schemeAndHttpHost) {
    $url = '';
    $un = new UrlNormalizer();
    $this->assertNull($un->normalize($url, $scheme, $schemeAndHttpHost));
  }

  /**
   * Tests URL normalization.
   *
   * @dataProvider schemeHttpHostProvider
   */
  public function testNormalizeUrlPrefixMissingProtocolSinglePart($scheme,
                                                                  $schemeAndHttpHost) {

    $tests = ['drupal', 'drupal/subnode'];
    $this->runNormalizationTest($tests,
                                $scheme,
                                $schemeAndHttpHost,
                                $schemeAndHttpHost,
                                '/');

  }

  /**
   * Tests URL normalization.
   *
   * @dataProvider schemeHttpHostProvider
   */
  public function testNormalizeUrlPrefixMissingProtocolThreeParts($scheme,
                                                                  $schemeAndHttpHost) {

    $tests = ['www.drupal.org', 'www.drupal.org/node'];
    $this->runNormalizationTest($tests, $scheme, $schemeAndHttpHost, $scheme);

  }

  /**
   * Tests URL normalization.
   *
   * @dataProvider schemeHttpHostProvider
   */
  public function testNormalizeUrlPrefixMissingProtocolTwoParts($scheme,
                                                                $schemeAndHttpHost) {

    $tests = ['drupal.org', 'drupal.org/node'];
    $this->runNormalizationTest($tests, $scheme, $schemeAndHttpHost, $scheme);

  }

  /**
   * Tests URL normalization.
   *
   * @dataProvider schemeHttpHostProvider
   */
  public function testNormalizeUrlSubSubSubdomain($scheme,
                                                  $schemeAndHttpHost) {

    $tests = ['sub1.sub2.www.drupal.org', 'sub1.sub2.www.drupal.org/node'];
    $this->runNormalizationTest($tests, $scheme, $schemeAndHttpHost, $scheme);

  }

  /**
   * Tests URL normalization.
   *
   * @dataProvider schemeHttpHostProvider
   */
  public function testNormalizeUrlRelativePath($scheme,
                                               $schemeAndHttpHost) {

    $tests = ['/node', '/node/1'];
    $this->runNormalizationTest($tests,
                                $scheme,
                                $schemeAndHttpHost,
                                $schemeAndHttpHost,
                                '');
  }

  /**
   * Tests URL normalization.
   *
   * @dataProvider schemeHttpHostProvider
   */
  public function testNormalizeUrlIllegalCharactersForHostnames($scheme,
                                                                $schemeAndHttpHost) {
    $tests = [
      'node.',
      '.node',
      '.some.node',
      '#node',
      'some#node',
      'node#',
      'some.node#',
      'node#anchor',
      'some.node#anchor',
      'node#anchor?',
      '#anchor',
      '#anchor?',
      'node#anchor?so=',
      'node#anchor?so=me&',
      'node#anchor?so=me&query=string',
      'some.node#anchor?so=me&query=string',
    ];

    $this->runNormalizationTest($tests,
                                $scheme,
                                $schemeAndHttpHost,
                                $schemeAndHttpHost,
                                '/');
  }

  /**
   * Tests URL normalization.
   *
   * @dataProvider schemeHttpHostProvider
   */
  public function testNormalizeUrlIpAddress($scheme,
                                            $schemeAndHttpHost) {

    $tests = ['10.0.0.1', '10.0.0.1/node'];
    $this->runNormalizationTest($tests,
                                $scheme,
                                $schemeAndHttpHost,
                                $scheme
    );

  }

  /**
   * @param array  $tests
   * @param string $scheme
   * @param string $schemeAndHttpHost
   * @param string $pathStart
   * @param string $divider
   */
  protected function runNormalizationTest(array $tests,
                                          $scheme,
                                          $schemeAndHttpHost,
                                          $pathStart,
                                          $divider = '://'
  ) {

    $un = new UrlNormalizer();
    foreach ($tests as $test) {
      $this->assertEquals($pathStart . $divider . $test,
                          $un->normalize($test, $scheme, $schemeAndHttpHost));
    }
  }

  /**
   * A data provider.
   *
   * @return array
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
      $dataProvidedArray[] = [preg_replace('#://.*#', '', $host), $host];
    }
    return $dataProvidedArray;
  }

}
