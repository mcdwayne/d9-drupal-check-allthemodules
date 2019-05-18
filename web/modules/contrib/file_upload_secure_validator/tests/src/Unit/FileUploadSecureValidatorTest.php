<?php

namespace Drupal\Tests\file_upload_secure_validator\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\file_upload_secure_validator\Service\FileUploadSecureValidator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A class for unit testing the file_upload_secure_validator service.
 *
 * @group file_upload_secure_validator
 */
class FileUploadSecureValidatorTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Provide a mock service container, for the services our module uses.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getTranslationManagerMock());
    $container->set('logger.factory', $this->getLoggerFactoryMock());
    \Drupal::setContainer($container);
  }

  /**
   * Tests the file upload validate function of the provided service.
   *
   * @dataProvider fileUploadScenariosProvider
   */
  public function testFileUploadSecureValidatorValidate($case, $uri, $filename, $expected) {
    // This is the main class of the service.
    $file_upload_secure_validator_service = new FileUploadSecureValidator();

    $errors = $file_upload_secure_validator_service->validate($this->mockFile($uri, $filename));
    $error = array_pop($errors);

    $this->assertEquals($error, $expected);
  }

  /**
   * Scenario related data are created in this function.
   */
  public function fileUploadScenariosProvider() {
    return [
      [
        'case' => 'True extension',
        'uri' => dirname(__FILE__) . '/resources/original_pdf.pdf',
        'mimetype' => 'application/pdf',
        'expected' => NULL,
      ],
      [
        'case' => 'Falsified extension',
        'uri' => dirname(__FILE__) . '/resources/original_pdf.txt',
        'mimetype' => 'text/plain',
        // Setting this up as a new TranslatableMarkup with our mock translation
        // manager; otherwise assertEquals complains about non-identical objects
        // based on the attached TranslationManager service.
        'expected' => new TranslatableMarkup('There was a problem with this file. The uploaded file is of type @extension but the real content seems to be @real_extension', ['@extension' => 'text/plain', '@real_extension' => 'application/pdf'], [], $this->getTranslationManagerMock()),
      ],
      [
        'case' => 'CSV extension',
        'uri' => dirname(__FILE__) . '/resources/original_csv.csv',
        'mimetype' => 'text/csv',
        // Setting this up as a new TranslatableMarkup with our mock translation
        // manager; otherwise assertEquals complains about non-identical objects
        // based on the attached TranslationManager service.
        'expected' => NULL,
      ],
      [
        'case' => 'XML extension',
        'uri' => dirname(__FILE__) . '/resources/original_xml.xml',
        'mimetype' => 'text/xml',
        // Setting this up as a new TranslatableMarkup with our mock translation
        // manager; otherwise assertEquals complains about non-identical objects
        // based on the attached TranslationManager service.
        'expected' => NULL,
      ],
    ];
  }

  /**
   * Mock file entities.
   *
   * We are only interested in the uri and mimetype getters.
   */
  private function mockFile($uri, $mimetype) {
    $fileMock = $this->getMockBuilder('Drupal\file\Entity\File')
      ->disableOriginalConstructor()
      ->getMock();
    $fileMock->expects($this->any())
      ->method('getFileUri')
      ->willReturn($uri);
    $fileMock->expects($this->any())
      ->method('getMimeType')
      ->willReturn($mimetype);

    return $fileMock;
  }

  /**
   * Utility function for getting a TranslationManager service.
   */
  private function getTranslationManagerMock() {

    $translationManager = $this->getMockBuilder('Drupal\Core\StringTranslation\TranslationManager')
      ->disableOriginalConstructor()
      ->getMock();
    $translationManager->expects($this->any())
      ->method('translate')
      ->will($this->returnArgument(0));

    return $translationManager;
  }

  /**
   * Utility function for getting a LoggerChannelFactory service.
   */
  private function getLoggerFactoryMock() {

    $loggerChannel = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannel')
      ->disableOriginalConstructor()
      ->getMock();
    $loggerChannel->expects($this->any())
      ->method('error')
      ->will($this->returnValue(''));

    $loggerChannelFactory = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannelFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $loggerChannelFactory->expects($this->any())
      ->method('get')
      ->will($this->returnValue($loggerChannel));
    return $loggerChannelFactory;
  }

}
