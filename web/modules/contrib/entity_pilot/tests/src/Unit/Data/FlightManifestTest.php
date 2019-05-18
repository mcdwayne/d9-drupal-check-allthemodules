<?php

namespace Drupal\Tests\entity_pilot\Unit\Data;

use Defuse\Crypto\Encoding;
use Defuse\Crypto\Key;
use Drupal\Component\Serialization\Json;
use Drupal\entity_pilot\AccountInterface;
use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\entity_pilot\Encryption\Encrypter;
use Drupal\entity_pilot\TransportInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_pilot\Data\FlightManifest
 * @group entity_pilot
 */
class FlightManifestTest extends UnitTestCase {

  /**
   * Manifest under test.
   *
   * @var \Drupal\entity_pilot\Data\FlightManifestInterface
   */
  protected $manifest;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->manifest = new FlightManifest();
  }

  /**
   * Tests \Drupal\entity_pilot\Data\FlightManifestTest::setInfo().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::setInfo()
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::getInfo()
   *
   * @covers ::setInfo
   * @covers ::getInfo
   */
  public function testGetSetInfo() {
    $this->manifest->setInfo('foobar');
    $this->assertEquals($this->manifest->getInfo(), 'foobar');
  }

  /**
   * Tests \Drupal\entity_pilot\Data\FlightManifestTest::setCarrierId().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::setCarrierId()
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::getCarrierId()
   *
   * @covers ::setCarrierId
   * @covers ::getCarrierId
   */
  public function testGetSetCarrierId() {
    $this->manifest->setCarrierId(1);
    $this->assertEquals($this->manifest->getCarrierId(), 1);
  }

  /**
   * Tests \Drupal\entity_pilot\Data\FlightManifestTest::getLog().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::getLog()
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::setLog()
   *
   * @covers ::getLog
   * @covers ::setLog
   */
  public function testGetSetLog() {
    $this->manifest->setLog('baloney');
    $this->assertEquals($this->manifest->getLog(), 'baloney');
  }

  /**
   * Tests \Drupal\entity_pilot\Data\FlightManifestTest::getContents().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::getContents()
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::setContents()
   *
   * @covers ::getContents
   * @covers ::setContents
   */
  public function testGetSetContents() {
    $this->manifest->setContents(['yo' => 'joe']);
    $this->assertEquals($this->manifest->getContents(), ['yo' => 'joe']);
    $this->assertEquals($this->manifest->getContents(TRUE), Json::encode(['yo' => 'joe']));
  }

  /**
   * Tests \Drupal\entity_pilot\Data\FlightManifestTest::setSite().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::setSite()
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::getSite()
   *
   * @covers ::setSite
   * @covers ::getSite
   */
  public function testGetSetSite() {
    $this->manifest->setSite('http://foo.bar');
    $this->assertEquals($this->manifest->getSite(), 'http://foo.bar');
  }

  /**
   * Tests \Drupal\entity_pilot\Data\FlightManifestTest::getRemoteId().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::getRemoteId()
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::setRemoteId()
   *
   * @covers ::getRemoteId
   * @covers ::setRemoteId
   */
  public function testGetSetRemoteId() {
    $this->manifest->setRemoteId(1);
    $this->assertEquals($this->manifest->getRemoteId(), 1);
  }

  /**
   * Tests \Drupal\entity_pilot\Data\FlightManifestTest::toArray().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::toArray()
   *
   * @covers ::toArray
   */
  public function testToArray() {
    $changed = time();
    $map = [
      'node' => [
        'field_foobar' => 'image',
      ],
    ];
    $this->manifest->setCarrierId(1)
      ->setRemoteId(1)
      ->setInfo('foo')
      ->setBlackBoxKey('abc')
      ->setChanged($changed)
      ->setContents(['yo' => ['joe']])
      ->setFieldMapping($map)
      ->setLog('woot')
      ->setSite('http://foo.bar');
    $date = new \DateTime('@' . $changed, new \DateTimeZone('UTC'));
    $key = Key::createNewRandomKey();
    $array = $this->manifest->toArray($key->saveToAsciiSafeString());
    $expected = [
      'log' => 'woot',
      'contents' => Json::encode(['yo' => base64_encode(Encrypter::encrypt($key->saveToAsciiSafeString(), Json::encode(['joe'])))]),
      'changed' => [
        'date' => [
          'year' => (int) $date->format('Y'),
          'month' => (int) $date->format('m'),
          'day' => (int) $date->format('d'),
        ],
        'time' => [
          'hour' => (int) $date->format('H'),
          'minute' => (int) $date->format('i'),
        ],
      ],
      'account' => 1,
      'site' => 'http://foo.bar',
      'info' => 'foo',
      'fields' => Json::encode($map),
      'drupal_version' => FlightManifest::DRUPAL_VERSION,
    ];
    // We can't test the contents as each encryption uses a unique iv.
    $expected['contents'] = $array['contents'];
    $this->assertEquals($expected, $array);
  }

  /**
   * Tests \Drupal\entity_pilot\Data\FlightManifestTest::create().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::create()
   *
   * @covers ::create
   */
  public function testCreate() {
    $flight = FlightManifest::create(['info' => 'foobar']);
    $this->assertInstanceOf('\Drupal\entity_pilot\Data\FlightManifest', $flight);
    $this->assertEquals($flight->getInfo(), 'foobar');
  }

  /**
   * Tests \Drupal\entity_pilot\Data\FlightManifestTest::fromArray().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::fromArray()
   *
   * @covers ::fromArray
   */
  public function testFromArray() {
    $map = [
      'node' => [
        'field_foobar' => 'image',
      ],
    ];
    $secret = 'def00000e3dd157ca0477cba6ee29f7b5f39106f29838e25e14542b59235e77e70cff292184da5ad79b4c09bca931d15072f4d48418b2b7a6cf83aba4c7ded9376f524c4';
    $flights = FlightManifest::fromArray([
      [
        'info' => 'foobar',
        'id' => 3,
        'contents' => Json::encode(['foo' => Encoding::binToHex(Encrypter::encrypt($secret, Json::encode(['bar'])))]),
        'fields' => Json::encode($map),
        'drupal_version' => FlightManifest::DRUPAL_VERSION,
      ],
    ], $secret);
    /** @var \Drupal\entity_pilot\Data\FlightManifestInterface $flight */
    $flight = reset($flights);
    $this->assertInstanceOf(FlightManifest::class, $flight);
    $this->assertEquals($flight->getInfo(), 'foobar');
    $this->assertEquals($flight->getContents(), ['foo' => ['bar']]);
    $this->assertEquals($map, $flight->getFieldMapping());
    $this->assertEquals(1, count($flights));
    $this->expectException(\UnexpectedValueException::class);
    FlightManifest::fromArray([
      [
        'info' => 'foobar',
        'contents' => base64_encode(Encrypter::encrypt($secret, JSON::encode(
          ['foo' => 'bar']
        ))),
        'drupal_version' => FlightManifest::DRUPAL_VERSION,
      ],
    ], $secret);
  }

  /**
   * Tests \Drupal\entity_pilot\Data\FlightManifestTest::setBlackBoxKey().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::setBlackBoxKey()
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::getBlackBoxKey()
   *
   * @covers ::setBlackBoxKey
   * @covers ::getBlackBoxKey
   */
  public function testGetSetBlackBoxKey() {
    $this->manifest->setBlackBoxKey('abc');
    $this->assertEquals($this->manifest->getBlackBoxKey(), 'abc');
  }

  /**
   * Tests \Drupal\entity_pilot\Data\FlightManifestTest::setChanged().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::setChanged()
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::getChanged()
   *
   * @covers ::setChanged
   * @covers ::getChanged
   */
  public function testSetChanged() {
    $this->manifest->setChanged(123);
    $this->assertEquals($this->manifest->getChanged(), 123);
  }

  /**
   * Tests FlightManifestTest::getTransposedContents().
   *
   * @see \Drupal\entity_pilot\Data\FlightManifestTest::getTransposedContents()
   *
   * @covers ::getTransposedContents
   */
  public function testGetTransposedContents() {
    $this->manifest->setContents(['http://foo.bar' => 'joe']);
    $this->manifest->setSite('http://foo.bar');
    $this->assertEquals($this->manifest->getTransposedContents('http://bar.foo', $this->createMock(TransportInterface::class), $this->createMock(AccountInterface::class)), Json::encode(['http://bar.foo' => 'joe']));
  }

}
