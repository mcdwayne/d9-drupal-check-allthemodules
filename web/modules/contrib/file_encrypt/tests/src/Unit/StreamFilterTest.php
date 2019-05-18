<?php

namespace Drupal\Tests\file_encrypt\Unit;

use Drupal\encrypt\EncryptServiceInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\file_encrypt\EncryptStreamWrapper;
use Drupal\file_encrypt\StreamFilter\DecryptStreamFilter;
use Drupal\file_encrypt\StreamFilter\EncryptStreamFilter;
use Drupal\file_encrypt\StreamFilter\StreamFilterBase;
use Drupal\Tests\UnitTestCase;

/**
 * Tests stream filtering.
 */
class StreamFilterTest extends UnitTestCase  {

  /**
   * The encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $encryptionService;

  /**
   * The encryption method.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $encryptionProfile;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->encryptionService = $this->getMockBuilder(EncryptServiceInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    // @todo The current alpha release of encrypt module incorrectly depends on
    //   a concretion rather than an interface, so we must mock accordingly.
    //   Use \Drupal\encrypt\EncryptionProfileInterface once the next release
    //   comes out.
    $this->encryptionProfile = $this->getMockBuilder(EncryptionProfile::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests the OpenSslUnsafe helper class.
   */
  public function testOpenSslUnsafe() {
    $original_data = 'Lorem ipsum dolor sit amet';

    $encrypted_data = OpenSslUnsafe::encrypt($original_data);
    $decrypted_data = OpenSslUnsafe::decrypt($encrypted_data);

    $this->assertEquals($original_data, $decrypted_data, 'Data preserved through encryption and decryption.');
  }

  /**
   * Tests the stream filters end-to-end.
   *
   * Note: This does NOT exercise the stream wrapper, which appends the filters
   * in production.
   *
   * @param string $raw_data
   *   The raw data to filter.
   * @param int $chunk_size
   *   The number of bytes on which to break buckets.
   * @param string $message
   *   The assertion message.
   *
   * @dataProvider providerTestEndToEnd
   */
  public function testEndToEnd($raw_data, $chunk_size, $message) {
    $this->encryptionService->method('encrypt')
      ->willReturnCallback([OpenSslUnsafe::class, 'encrypt']);
    $this->encryptionService->method('decrypt')
      ->willReturnCallback([OpenSslUnsafe::class, 'decrypt']);
    $stream = $this->createFilteredStream($chunk_size);

    fwrite($stream, $raw_data);
    fseek($stream, 0);
    $filtered_data = stream_get_contents($stream);
    fclose($stream);

    $this->assertEquals($raw_data, $filtered_data, $message);
  }

  /**
   * Data provider.
   */
  public function providerTestEndToEnd() {
    yield ['', 1000, 'Zero-length string with large chunk size.'];
    yield ['test', 100, 'Shorter string than chunk size.'];
    yield ['Lorem ipsum dolor sit amet', 4, 'Larger string than chunk size.'];
    $two_mb = pow(1024, 2) * 2;
    $default_chunk_size = 1024 * 8;
    yield [openssl_random_pseudo_bytes($two_mb), $default_chunk_size, 'Two megabytes of random data with the default chunk size.'];
  }

  /**
   * Tests creating a stream filter without required parameters.
   *
   * @param string $name
   *   The name of the required parameter to omit.
   *
   * @covers \Drupal\file_encrypt\StreamFilter\StreamFilterBase::onCreate
   *
   * @dataProvider providerTestCreatingStreamFilterWithoutRequiredParam
   */
  public function testCreatingStreamFilterWithoutRequiredParam($name) {
    $stream = fopen('php://temp', 'r+');
    $params = $this->createDefaultParams();
    unset($params[$name]);
    stream_filter_register(EncryptStreamFilter::NAME, EncryptStreamFilter::class);

    try {
      stream_filter_append($stream, EncryptStreamFilter::NAME, NULL, $params);
      $this->fail(sprintf("Did not throw exception for missing required parameter '%s'.", $name));
    }
    catch (\AssertionError $e) {
      $this->assertEquals(sprintf("Missing or invalid '%s' parameter.", $name), $e->getMessage(), sprintf("Threw correct exception for missing required parameter '%s'.", $name));
    }
    finally {
      fclose($stream);
    }
  }

  /**
   * Data provider.
   */
  public function providerTestCreatingStreamFilterWithoutRequiredParam() {
    return [
      ['encryption_service'],
      ['encryption_profile'],
    ];
  }

  /**
   * Tests StreamFilterBase::maxPayloadLength().
   */
  public function testMaxPayloadLength() {
    $this->assertSame(9999999, StreamFilterBase::maxPayloadLength(), 'Maxinum payload length calculated correctly.');
  }

  /**
   * Tests EncryptStreamFilter::filterData().
   *
   * @param string $data
   *   The data to filter.
   * @param string $message
   *   The assertion message.
   *
   * @dataProvider providerTestEncryptFilterData
   */
  public function testEncryptFilterData($data, $message) {
    $this->encryptionService
      ->method('encrypt')
      ->willReturn($data);
    $filter = $this->createEncryptStreamFilter();

    $actual_filtered_data = $filter->filterData($data);

    $header = str_pad(strlen($data), StreamFilterBase::HEADER_LENGTH, StreamFilterBase::HEADER_PADDING_CHARACTER);
    $expected_filtered_data = $header . $data;
    $this->assertEquals($expected_filtered_data, $actual_filtered_data, $message);
  }

  /**
   * Data provider.
   */
  public function providerTestEncryptFilterData() {
    return [
      ['', 'Zero-length string.'],
      ['test', 'Arbitrary string.'],
      ["\0\t\r\n", 'Special characters.'],
      [str_pad('', StreamFilterBase::maxPayloadLength(), 'abcdefghijklmnopqrstuvwxyz1234567890'), 'Maximum length string.'],
    ];
  }

  /**
   * Tests DecryptStreamFilter::shiftDatumFromBuffer().
   *
   * @param string $buffer
   *   The buffer variable.
   * @param int $next_datum_size
   *   The size of the next datum in bytes.
   * @param string $expected_datum
   *   The expected datum.
   * @param string $expected_buffer
   *   The expected buffer value after operation.
   *
   * @dataProvider providerTestShiftDatumFromBuffer
   */
  public function testShiftDatumFromBuffer($buffer, $next_datum_size, $expected_datum, $expected_buffer) {
    $datum = DecryptStreamFilter::shiftDatumFromBuffer($buffer, $next_datum_size);

    $this->assertEquals($expected_datum, $datum, 'Returned expected datum.');
    $this->assertEquals($expected_buffer, $buffer, 'Updated buffer value correctly.');
  }

  /**
   * Data provider.
   */
  public function providerTestShiftDatumFromBuffer() {
    return [
      ['1234567890', 5, '12345', '67890'],
      ["4\0\0\0\0\0\0test", 7, "4\0\0\0\0\0\0", 'test'],
    ];
  }

  /**
   * Tests trying to decrypt bucket data that is too long.
   *
   * @expectedException \Drupal\encrypt\Exception\EncryptException
   * @expectedExceptionMessage Payload is too large
   */
  public function testEncryptingTooLongData() {
    $payload = str_repeat('x', StreamFilterBase::maxPayloadLength() + 1);
    $this->encryptionService
      ->method('encrypt')
      ->willReturn($payload);
    $filter = $this->createEncryptStreamFilter();

    $filter->filterData('test');
  }

  /**
   * Creates a stream resource with encrypt/decrypt filters attached.
   *
   * @param int $chunk_size
   *   The number of bytes on which to break buckets.
   *
   * @return resource
   *   A stream resource with filters attached.
   */
  protected function createFilteredStream($chunk_size = 100) {
    $stream = fopen('php://temp', 'r+');

    stream_set_chunk_size($stream, $chunk_size);
    stream_set_read_buffer($stream, $chunk_size);
    stream_set_write_buffer($stream, $chunk_size);

    $params = $this->createDefaultParams();
    EncryptStreamWrapper::appendStreamFilter($stream, EncryptStreamFilter::NAME, EncryptStreamFilter::class, STREAM_FILTER_WRITE, $params);
    EncryptStreamWrapper::appendStreamFilter($stream, DecryptStreamFilter::NAME, DecryptStreamFilter::class, STREAM_FILTER_READ, $params);

    return $stream;
  }

  /**
   * Creates default parameters for stream filters.
   *
   * @return array
   *   An array of stream filter parameters.
   */
  protected function createDefaultParams() {
    return [
      'encryption_service' => $this->encryptionService,
      'encryption_profile' => $this->encryptionProfile,
    ];
  }

  /**
   * Creates an encrypt stream filter instance.
   *
   * @return \Drupal\file_encrypt\StreamFilter\EncryptStreamFilter
   *   An encrypt stream filter instance.
   */
  protected function createEncryptStreamFilter() {
    $filter = new EncryptStreamFilter();
    $filter->params = $this->createDefaultParams();
    $filter->onCreate();
    return $filter;
  }

  /**
   * Creates a decrypt stream filter instance.
   *
   * @return \Drupal\file_encrypt\StreamFilter\DecryptStreamFilter
   *   A decrypt stream filter instance.
   */
  protected function createDecryptStreamFilter() {
    $filter = new DecryptStreamFilter();
    $filter->params = $this->createDefaultParams();
    $filter->onCreate();
    return $filter;
  }

}
