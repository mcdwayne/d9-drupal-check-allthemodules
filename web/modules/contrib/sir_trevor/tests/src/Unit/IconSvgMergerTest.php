<?php

namespace Drupal\sir_trevor\Tests\Unit;

use Drupal\sir_trevor\IconSvgMerger;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * @package Drupal\sir_trevor\Tests
 * @group SirTrevor
 */
class IconSvgMergerTest extends \PHPUnit_Framework_TestCase {
  /** @var Logger */
  protected $logger;

  public function setUp() {
    parent::setUp();
    $this->logger = new Logger();
  }

  /**
   * @test
   */
  public function givenNoFilesProducesNothing() {
    $sut = new IconSvgMerger($this->logger);

    $expected = file_get_contents(__DIR__ . '/fixtures/noSymbols-exported.svg');
    $this->assertSame($expected, $sut->merge([]));
  }

  /**
   * Dataprovider for @see fileWarnings
   * @return array
   */
  public function fileWarningTestDataProvider() {
    $testData = [];

    $testData['non existing file'] = [
      'filename' => 'non-existing.file',
      'warning message' => 'non-existing.file does not exist.',
    ];

    $testData['non xml file'] = [
      'filename' => __DIR__ . '/fixtures/non-xml-file.txt',
      'warning message' => __DIR__ . '/fixtures/non-xml-file.txt does not contain valid xml.',
    ];

    return $testData;
  }

  /**
   * @test
   * @dataProvider fileWarningTestDataProvider
   * @param string $fileName
   * @param string $warningMessage
   */
  public function fileWarnings($fileName, $warningMessage) {
    $sut = new IconSvgMerger($this->logger);

    $sut->merge([$fileName]);
    $this->assertWarningLogged($warningMessage);
  }

  /**
   * @test
   */
  public function givenSingleSvg_returnsItsUnchangedContents() {
    $sut = new IconSvgMerger($this->logger);
    $fileName = __DIR__ . '/fixtures/singleSymbol-exported.svg';

    $expected = file_get_contents($fileName);
    $this->assertEqualIgnoringWhiteSpace($expected, $sut->merge([$fileName]));
  }

  /**
   * @test
   */
  public function givenMultipleSvgFiles_returnsTheirMergedContents() {
    $sut = new IconSvgMerger($this->logger);

    $filenames = [
      __DIR__ . '/fixtures/singleSymbol.svg',
      __DIR__ . '/fixtures/twoSymbols.svg',
    ];

    $expected = file_get_contents(__DIR__ . '/fixtures/threeSymbols-exported.svg');
    $this->assertEqualIgnoringWhiteSpace($expected, $sut->merge($filenames));
  }


  /**
   * @param string $expectedWarning
   */
  private function assertWarningLogged($expectedWarning) {
    $this->assertContains($expectedWarning, $this->logger->getLogs(LogLevel::WARNING));
  }

  /**
   * @param string $expected
   * @param string $actual
   */
  private function assertEqualIgnoringWhiteSpace($expected, $actual) {
    $this->assertEquals($this->removeWhitespace($expected), $this->removeWhitespace($actual));
  }

  /**
   * @param string $string
   * @return string
   */
  private function removeWhitespace($string) {
    return preg_replace('/[\s]+/', '', $string);
  }
}
