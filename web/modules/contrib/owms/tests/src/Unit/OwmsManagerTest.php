<?php

namespace Drupal\Tests\owms\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\owms\Entity\OwmsData;
use Drupal\owms\OwmsManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\owms\OwmsManager
 *
 * @group owms
 */
class OwmsManagerTest extends UnitTestCase {

  /**
   * @var \Drupal\owms\OwmsManager
   */
  protected $owmsManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->owmsManager = new OwmsManager();
  }


  /**
   * Get an accessible method using reflection.
   */
  public function getAccessibleMethod($class_name, $method_name) {
    $class = new \ReflectionClass($class_name);
    $method = $class->getMethod($method_name);
    $method->setAccessible(TRUE);
    return $method;
  }

  /**
   * Sends test data to the parsing methods.
   */
  public function getTestXml() {
    return [
      [
        simplexml_load_file($this->getTestsRootDir() . '/TestData.xml'),
      ],
    ];
  }

  /**
   * Sends test data to the parsing methods.
   */
  public function getTestInvalidXml() {
    return [
      [
        simplexml_load_file($this->getTestsRootDir() . '/InvalidTestData.xml'),
      ],
    ];
  }


  /**
   * Sends test data to the parsing methods.
   */
  public function getTestUpdatedXml() {
    return [
      [
        simplexml_load_file($this->getTestsRootDir() . '/UpdatedTestData.xml'),
      ],
    ];
  }

  /**
   * Gets the root dir of the tests.
   *
   * @return bool|string
   */
  protected function getTestsRootDir(){
    return realpath(__DIR__ . '/../..');
  }

  /**
   * Test the parsed data values.
   *
   * @dataProvider getTestXml
   *
   * @covers ::parseDataValues
   *
   * @param \SimpleXMLElement $xml
   */
  public function testParseDataValues(\SimpleXMLElement $xml) {
    $expectedResult = [
      [
        'label' => '\'s-Graveland',
        'identifier' => 'http://standaarden.overheid.nl/owms/terms/\'s-Graveland_(gemeente)',
        'deprecated' => FALSE,
      ],
      [
        'label' => '\'s-Gravendeel',
        'identifier' => 'http://standaarden.overheid.nl/owms/terms/\'s-Gravendeel_(gemeente)',
        'deprecated' => FALSE,
      ],
      [
        'label' => '\'s-Gravenhage',
        'identifier' => 'http://standaarden.overheid.nl/owms/terms/\'s-Gravenhage',
        'deprecated' => FALSE,
      ],
      [
        'label' => '\'s-Hertogenbosch',
        'identifier' => 'http://standaarden.overheid.nl/owms/terms/\'s-Hertogenbosch',
        'deprecated' => FALSE,
      ],
      [
        'label' => 'ABG-organisatie',
        'identifier' => 'http://standaarden.overheid.nl/owms/terms/abgorg',
        'deprecated' => FALSE,
      ],
    ];
    $this->assertArrayEquals($expectedResult, $this->owmsManager->parseDataValues($xml));
  }

  /**
   * Test if the update of the items is executed correctly.
   *
   * @covers ::getUpdatedItems
   */
  public function testUpdateItems(){

    // The expected result is that One item (s-Gravezande) is inserted
    // into the right place, and another item (s-Hertogenbosch) is marked
    // deprecated.

    $expectedResult = [
      [
        'label' => '\'s-Graveland',
        'identifier' => 'http://standaarden.overheid.nl/owms/terms/\'s-Graveland_(gemeente)',
        'deprecated' => FALSE,
      ],
      [
        'label' => '\'s-Gravendeel',
        'identifier' => 'http://standaarden.overheid.nl/owms/terms/\'s-Gravendeel_(gemeente)',
        'deprecated' => FALSE,
      ],
      [
        'label' => '\'s-Gravenhage',
        'identifier' => 'http://standaarden.overheid.nl/owms/terms/\'s-Gravenhage',
        'deprecated' => FALSE,
      ],
      [
        'label' => '\'s-Gravenzande',
        'identifier' => 'http://standaarden.overheid.nl/owms/terms/\'s-Gravenzande_(gemeente)',
        'deprecated' => FALSE,
      ],
      [
        'label' => '\'s-Hertogenbosch',
        'identifier' => 'http://standaarden.overheid.nl/owms/terms/\'s-Hertogenbosch',
        'deprecated' => TRUE,
      ],
      [
        'label' => 'ABG-organisatie',
        'identifier' => 'http://standaarden.overheid.nl/owms/terms/abgorg',
        'deprecated' => FALSE,
      ],
    ];
    $methodToTest = $this->getAccessibleMethod(OwmsManager::class, 'getUpdatedItems');
    $itemsToCheck = $this->owmsManager->parseDataValues($this->getTestUpdatedXml()[0][0]);
    $existingItems = $this->owmsManager->parseDataValues($this->getTestXml()[0][0]);
    $result = $methodToTest->invokeArgs($this->owmsManager, [$itemsToCheck, $existingItems]);
    $this->assertArrayEquals($expectedResult, $result);

  }

  /**
   * Test the parsed data values.
   *
   * @dataProvider getTestInvalidXml
   *
   * @covers ::parseDataValues
   *
   * @param \SimpleXMLElement $xml
   */
  public function testParseInvalidDataValues(\SimpleXMLElement $xml){
    $this->setExpectedException("Exception", "Incorrectly formatted XML");
    $this->owmsManager->parseDataValues($xml);
  }

  /**
   * Test the validation of the endpoint.
   *
   * All the endpoints defined in owms.lists.yml should return a valid xml.
   *
   * @covers ::validateEndpoint
   */
  public function testValidateEndpoint() {
    $dir = realpath(__DIR__ . '/../../..');
    $yml = file_get_contents($dir . '/owms.lists.yml');
    $options = Yaml::decode($yml);
    $options = array_combine($options, $options);
    foreach ($options as $option) {
      $endpoint = OwmsData::ENDPOINT_BASE_URL . $option . '.xml';
      $xml = $this->owmsManager->validateEndpoint($endpoint);
      $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    }
  }

}
