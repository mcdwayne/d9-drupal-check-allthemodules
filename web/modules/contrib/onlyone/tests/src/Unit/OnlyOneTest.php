<?php

namespace Drupal\Tests\onlyone\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\onlyone\OnlyOne;
use Drupal\onlyone\OnlyOnePrintAdminPage;
use Drupal\onlyone\OnlyOnePrintDrush;
use Drupal\onlyone\OnlyOnePrintStrategyInterface;
use Drupal\Tests\onlyone\Traits\OnlyOneUnitTestTrait;

/**
 * Tests the OnlyOne class methods.
 *
 * @group onlyone
 * @coversDefaultClass \Drupal\onlyone\OnlyOne
 */
class OnlyOneTest extends UnitTestCase {

  use OnlyOneUnitTestTrait;

  /**
   * A entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * A connection instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * A language manager instance.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * A config factory instance.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The OnlyOne Object.
   *
   * @var Drupal\onlyone\OnlyOne
   */
  protected $onlyOne;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Entity type manager mock.
    $this->entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    // Connection mock.
    $this->connection = $this->createMock('Drupal\Core\Database\Connection');
    // Language manager mock.
    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    // Config factory mock.
    $this->configFactory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');

    // Creating the object.
    $this->onlyOne = new OnlyOne($this->entityTypeManager, $this->connection, $this->languageManager, $this->configFactory, $this->getStringTranslationStub());
  }

  /**
   * Tests the OnlyOne::getTemporaryContentTypesTableName() method.
   *
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @covers ::getTemporaryContentTypesTableName
   * @dataProvider providerGetTemporaryContentTypesTableName
   */
  public function testGetTemporaryContentTypesTableName($expected) {
    // Mocking queryTemporary.
    $this->connection->expects($this->any())
      ->method('queryTemporary')
      ->willReturn($expected);

    // Making accesible the getTemporaryContentTypesTableName method.
    $method = new \ReflectionMethod('Drupal\onlyone\OnlyOne', 'getTemporaryContentTypesTableName');
    $method->setAccessible(TRUE);

    // Testing the function.
    $this->assertEquals($expected, $method->invoke($this->onlyOne));
  }

  /**
   * Data provider for testGetTemporaryContentTypesTableName().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getTemporaryContentTypesTableName().
   *
   * @see testExistsNodesContentType()
   */
  public function providerGetTemporaryContentTypesTableName() {
    $tests[] = ['tablename1'];
    $tests[] = ['tablename2'];

    return $tests;
  }

  /**
   * Tests the content types label list with OnlyOne::getContentTypesList().
   *
   * @param string $expected
   *   The expected result from calling the function.
   * @param array $content_types_list
   *   A list with content types objects.
   *
   * @covers ::getContentTypesList
   * @dataProvider providerGetContentTypesList
   */
  public function testGetContentTypesList($expected, array $content_types_list) {
    // EntityStorage mock.
    $entity_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    // loadMultiple mock.
    $entity_storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn($content_types_list);
    // Mocking getStorage method.
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('node_type')
      ->willReturn($entity_storage);

    // Testing the function.
    $this->assertEquals($expected, $this->onlyOne->getContentTypesList());
  }

  /**
   * Data provider for testGetContentTypesList().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getContentTypesList().
   *   - 'content_types_list' - A list with content types objects.
   *
   * @see getContentTypesList()
   */
  public function providerGetContentTypesList() {

    $expected = [
      [
        'article' => 'Article',
        'page' => 'Basic Page',
        'test' => 'Test Content Type',
      ],
      [
        'article' => 'Article',
        'blog' => 'Blog Post',
        'house' => 'House',
        'page' => 'Basic Page',
      ],
    ];
    // Getting the number of tests.
    $number_of_tests = count($expected);
    $content_types_list = [];
    // Creating the array of objects.
    for ($i = 0; $i < $number_of_tests; $i++) {
      $j = 0;

      // Creating the array of objects.
      foreach ($expected[$i] as $id => $label) {
        // EntityInterface mock.
        $content_types_list[$i][$j] = $this->createMock('Drupal\Core\Entity\EntityInterface');
        // Mocking id method.
        $content_types_list[$i][$j]->expects($this->any())
          ->method('id')
          ->willReturn($id);
        // Mocking label method.
        $content_types_list[$i][$j]->expects($this->any())
          ->method('label')
          ->willReturn($label);

        $j++;
      }
    }

    $tests['content types list 1'] = [$expected[0], $content_types_list[0]];
    $tests['content types list 2'] = [$expected[1], $content_types_list[1]];

    return $tests;
  }

  /**
   * Tests the existence of nodes with OnlyOne::existsNodesContentType().
   *
   * @param string $expected
   *   The expected result from calling the function.
   * @param string $content_type
   *   The content type to check.
   * @param bool $multilingual
   *   Whether the site is multilingual or not.
   * @param string $current_language
   *   The current language selected from the interface.
   * @param array $nids
   *   An array of ids. The keys and values are entity ids.
   *
   * @covers ::existsNodesContentType
   * @dataProvider providerExistsNodesContentType
   */
  public function testExistsNodesContentType($expected, $content_type, $multilingual, $current_language, array $nids) {
    // QueryInterface mock.
    $query = $this->createMock('\Drupal\Core\Entity\Query\QueryInterface');
    // QueryInterface::condition mock.
    $query->method('condition')
      ->withConsecutive(['type', $content_type], ['langcode', $current_language])
      ->willReturnOnConsecutiveCalls($this->returnSelf(), $this->returnSelf());
    // Mocking execute method.
    $query->expects($this->any())
      ->method('execute')
      ->willReturn($nids);

    // EntityStorage mock.
    $entity_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    // Mocking getQuery.
    $entity_storage->expects($this->any())
      ->method('getQuery')
      ->willReturn($query);

    // Mocking getStorage method.
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('node')
      ->willReturn($entity_storage);

    // Mocking isMultilingual.
    $this->languageManager->expects($this->any())
      ->method('isMultilingual')
      ->willReturn($multilingual);

    // LanguageInterface mock.
    $language = $this->createMock('Drupal\Core\Language\LanguageInterface');
    // Mocking getId method.
    $language->expects($this->any())
      ->method('getId')
      ->willReturn($current_language);

    // Mocking getCurrentLanguage method.
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($language);

    // Testing the function.
    $this->assertEquals($expected, $this->onlyOne->existsNodesContentType($content_type));
  }

  /**
   * Data provider for testExistsNodesContentType().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from existsNodesContentType().
   *   - 'content_type' - The content type to check.
   *   - 'multilingual' - Whether the site is multilingual or not.
   *   - 'current_language' - The current language selected from the interface.
   *   - 'nids' - An array of ids. The keys and values are entity ids.
   *
   * @see testExistsNodesContentType()
   */
  public function providerExistsNodesContentType() {
    $tests['node multilingual 1'] = [1, 'page', TRUE, 'en', [1 => 1]];
    $tests['node multilingual 2'] = [1, 'page', TRUE, 'es', [1 => 1]];
    $tests['node multilingual 3'] = [2, 'article', TRUE, 'en', [2 => 2]];
    $tests['node multilingual 4'] = [2, 'article', TRUE, 'es', [2 => 2]];
    $tests['node multilingual 5'] = [3, 'blog', TRUE, 'en', [3 => 3]];
    $tests['node multilingual 6'] = [4, 'book', TRUE, 'es', [4 => 4]];

    $tests['node not multilingual 1'] = [1, 'page', FALSE, 'en', [1 => 1]];
    $tests['node not multilingual 2'] = [1, 'page', FALSE, 'es', [1 => 1]];
    $tests['node not multilingual 3'] = [2, 'article', FALSE, 'en', [2 => 2]];
    $tests['node not multilingual 4'] = [2, 'article', FALSE, 'es', [2 => 2]];
    $tests['node not multilingual 5'] = [3, 'blog', FALSE, 'en', [3 => 3]];
    $tests['node not multilingual 6'] = [4, 'book', FALSE, 'es', [4 => 4]];

    $tests['not node multilingual 1'] = [0, 'page', TRUE, 'en', []];
    $tests['not node multilingual 2'] = [0, 'page', TRUE, 'es', []];
    $tests['not node multilingual 3'] = [0, 'article', TRUE, 'en', []];
    $tests['not node multilingual 4'] = [0, 'article', TRUE, 'es', []];
    $tests['not node multilingual 5'] = [0, 'blog', TRUE, 'en', []];
    $tests['not node multilingual 6'] = [0, 'book', TRUE, 'es', []];

    $tests['not node not multilingual 1'] = [0, 'page', FALSE, 'en', []];
    $tests['not node not multilingual 2'] = [0, 'page', FALSE, 'es', []];
    $tests['not node not multilingual 3'] = [0, 'article', FALSE, 'en', []];
    $tests['not node not multilingual 4'] = [0, 'article', FALSE, 'es', []];
    $tests['not node not multilingual 5'] = [0, 'blog', FALSE, 'en', []];
    $tests['not node not multilingual 6'] = [0, 'book', FALSE, 'es', []];

    return $tests;
  }

  /**
   * Tests content type config deletion with OnlyOne::deleteContentTypeConfig().
   *
   * @param string $expected
   *   The expected result from calling the function.
   * @param string $content_type
   *   Content type machine name to delete through
   *   OnlyOne::eleteContentTypeConfig().
   * @param string $content_types
   *   Content types configured to have onlyone node.
   *
   * @covers ::deleteContentTypeConfig
   * @dataProvider providerDeleteContentTypeConfig
   */
  public function testDeleteContentTypeConfig($expected, $content_type, $content_types) {
    // ImmutableConfig mock.
    $config = $this->createMock('Drupal\Core\Config\ImmutableConfig');
    // ImmutableConfig::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('onlyone_node_types')
      ->willReturn($content_types);

    // ImmutableConfig::set mock.
    $config->expects($this->any())
      ->method('set')
      ->with('onlyone_node_types', $this->anything())
      ->willReturnSelf();

    // ImmutableConfig::save mock.
    $config->expects($this->any())
      ->method('save')
      ->willReturnSelf();

    // Mocking getEditable method.
    $this->configFactory->expects($this->any())
      ->method('getEditable')
      ->with('onlyone.settings')
      ->willReturn($config);

    // Testing the function.
    $this->assertEquals($expected, $this->onlyOne->deleteContentTypeConfig($content_type));
  }

  /**
   * Data provider for testDeleteContentTypeConfig().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from deleteContentTypeConfig().
   *   - 'content_types' - Content types configured to have onlyone node.
   *   - 'content_type' - The content type to delete from the config.
   *
   * @see testDeleteContentTypeConfig()
   */
  public function providerDeleteContentTypeConfig() {
    // Content types configured to have onlyone node.
    $content_types[1] = [
      'page',
      'blog',
      'article',
    ];

    $content_types[2] = [
      'house',
      'car',
    ];

    $content_types[3] = [
      'person',
      'dog',
      'cat',
      'bird',
    ];

    $tests['existing content type 1'] = [TRUE, 'page', $content_types[1]];
    $tests['existing content type 2'] = [TRUE, 'car', $content_types[2]];
    $tests['existing content type 3'] = [TRUE, 'dog', $content_types[3]];
    $tests['non existing content type 1'] = [FALSE, 'book', $content_types[1]];
    $tests['non existing content type 2'] = [FALSE, 'plane', $content_types[2]];
    $tests['non existing content type 3'] = [FALSE, 'horse', $content_types[3]];

    return $tests;
  }

  /**
   * Tests the language label with OnlyOne::getLanguageLabel().
   *
   * @param string $expected
   *   The expected result from calling the function.
   * @param string $language_code
   *   The language code to run through OnlyOne::getLanguageLabel().
   *
   * @covers ::getLanguageLabel
   * @dataProvider providerGetLanguageLabel
   */
  public function testGetLanguageLabel($expected, $language_code) {
    // LanguageInterface mock.
    $language = $this->createMock('Drupal\Core\Language\LanguageInterface');
    // Mocking getName method.
    $language->expects($this->any())
      ->method('getName')
      ->willReturn($expected);

    // Mocking getLanguage method.
    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->with($language_code)
      ->willReturn($language);

    // Testing the function.
    $this->assertEquals($expected, $this->onlyOne->getLanguageLabel($language_code));
  }

  /**
   * Data provider for testGetLanguageLabel().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getLanguageLabel().
   *   - 'language_code' - The language code.
   *
   * @see testGetLanguageLabel()
   */
  public function providerGetLanguageLabel() {
    $tests['und langcode'] = ['Not specified', LanguageInterface::LANGCODE_NOT_SPECIFIED];
    $tests['zxx langcode'] = ['Not applicable', LanguageInterface::LANGCODE_NOT_APPLICABLE];
    $tests['es langcode'] = ['Es', 'es'];
    $tests['en langcode'] = ['En', 'en'];

    return $tests;
  }

  /**
   * Tests language label for empty language with OnlyOne::getLanguageLabel().
   *
   * The empty string as language_code is a special situation, you can read more
   * at: https://stackoverflow.com/q/49466415/3653989 .
   *
   * @covers ::getLanguageLabel
   */
  public function testGetLanguageLabelForEmptyLanguage() {
    // LanguageInterface mock.
    $language = $this->createMock('Drupal\Core\Language\LanguageInterface');
    // Mocking getName method.
    $language->expects($this->any())
      ->method('getName')
      ->willReturn('Not specified');

    // Mocking getLanguage method.
    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->with(LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->willReturn($language);

    // Testing the function.
    $this->assertEquals('Not specified', $this->onlyOne->getLanguageLabel(''));
  }

  /**
   * Tests the available and not available content types methods.
   *
   * The test is for the methods OnlyOne::getAvailableContentTypes() and
   * OnlyOne::getNotAvailableContentTypes().
   *
   * @param array $expected
   *   The expected result from calling the function.
   * @param bool $multilingual
   *   Whether the site is multilingual or not.
   *
   * @covers ::getAvailableContentTypes
   * @covers ::getNotAvailableContentTypes
   * @dataProvider providerGetAvailableContentTypes
   */
  public function testGetAvailableContentTypes(array $expected, $multilingual) {
    // Mocking isMultilingual.
    $this->languageManager->expects($this->any())
      ->method('isMultilingual')
      ->willReturn($multilingual);

    // StatementInterface mock.
    $statement = $this->createMock('Drupal\Core\Database\StatementInterface');
    // StatementInterface::fetchCol mock.
    $statement->expects($this->any())
      ->method('fetchCol')
      ->willReturn($expected);

    // Mocking query.
    $this->connection->expects($this->any())
      ->method('query')
      ->willReturn($statement);

    // Testing the functions.
    $this->assertEquals($expected, $this->onlyOne->getAvailableContentTypes());
    $this->assertEquals($expected, $this->onlyOne->getNotAvailableContentTypes());
  }

  /**
   * Data provider for multiple tests.
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getAvailableContentTypes().
   *   - 'multilingual' - Whether the site is multilingual or not.
   *
   * @see testGetAvailableContentTypes()
   * @see testGetAvailableContentTypesSummarized()
   */
  public function providerGetAvailableContentTypes() {
    $tests['multilingual 1'] = [['page', 'article'], TRUE];
    $tests['multilingual 2'] = [['page'], TRUE];
    $tests['multilingual 3'] = [['page', 'article', 'blog'], TRUE];

    $tests['not multilingual 1'] = [['page', 'article'], FALSE];
    $tests['not multilingual 2'] = [['page'], FALSE];
    $tests['not multilingual 3'] = [['page', 'article', 'blog'], FALSE];

    return $tests;
  }

  /**
   * Tests the available and not available summarized content types methods.
   *
   * The test is for the methods OnlyOne::getAvailableContentTypesSummarized()
   * and OnlyOne::getNotAvailableContentTypesSummarized().
   *
   * @param array $expected
   *   The expected result from calling the function.
   * @param array $configured
   *   The configured content types to have onlyone node.
   * @param array $content_types
   *   The returned content types from the the database query.
   * @param bool $multilingual
   *   Whether the site is multilingual or not.
   *
   * @covers ::getAvailableContentTypesSummarized
   * @covers ::getNotAvailableContentTypesSummarized
   * @dataProvider providerGetAvailableContentTypesSummarized
   */
  public function testGetAvailableContentTypesSummarized(array $expected, array $configured, array $content_types, $multilingual) {
    // Mock OnlyOne.
    $controller = $this->getMockBuilder('Drupal\onlyone\OnlyOne')
      ->setConstructorArgs([
        $this->entityTypeManager,
        $this->connection,
        $this->languageManager,
        $this->configFactory,
        $this->getStringTranslationStub(),
      ])
      // Specify that we'll also mock other methods.
      ->setMethods(['getContentTypesList', 'getLanguageLabel'])
      ->getMock();

    // Mock getContentTypesList().
    $controller->expects($this->any())
      ->method('getContentTypesList')
      ->willReturn($this->getContentTypesList());

    // Mock getLanguageLabel().
    $controller->expects($this->any())
      ->method('getLanguageLabel')
      ->will($this->returnValueMap($this->getLanguageMap()));

    // ImmutableConfig mock.
    $config = $this->createMock('Drupal\Core\Config\ImmutableConfig');
    // ImmutableConfig::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('onlyone_node_types')
      ->willReturn($configured);

    // Mocking getEditable method.
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with('onlyone.settings')
      ->willReturn($config);

    // Mocking isMultilingual.
    $this->languageManager->expects($this->any())
      ->method('isMultilingual')
      ->willReturn($multilingual);

    // StatementInterface mock.
    $statement = $this->createMock('Drupal\Core\Database\StatementInterface');
    // StatementInterface::fetchCol mock.
    $statement->expects($this->any())
      ->method('fetchAll')
      ->with(\PDO::FETCH_GROUP)
      ->willReturn($content_types);

    // Mocking query.
    $this->connection->expects($this->any())
      ->method('query')
      ->willReturn($statement);

    // Testing the functions.
    $this->assertEquals($expected, $controller->getAvailableContentTypesSummarized());
    $this->assertEquals($expected, $controller->getNotAvailableContentTypesSummarized());
  }

  /**
   * Data provider for testGetAvailableContentTypesSummarized().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getAvailableContentTypesSummarized().
   *   - 'configured' - The configured content types.
   *   - 'content_types' - Returned content types from the the database query.
   *   - 'multilingual' - Whether the site is multilingual or not.
   *   - 'language_map' - The language map for the getLanguageLabel method.
   *
   * @see testGetAvailableContentTypesSummarized()
   */
  public function providerGetAvailableContentTypesSummarized() {

    // Getting the content types list.
    $expected = $this->getContentTypesObjectList();

    // Content types configured to have onlyone node.
    $configured = [
      [
        'page', 'blog',
      ],
      [
        'blog',
      ],
      [
        'car', 'page',
      ],
    ];

    $content_types = [
      [
        // Test 1.
        'page' => [
          (object) ['language' => 'en', 'total' => 1],
          (object) ['language' => 'es', 'total' => 1],
        ],
        'blog' => [
          (object) ['language' => '', 'total' => 0],
        ],
        'car' => [
          (object) ['language' => 'und', 'total' => 1],
          (object) ['language' => 'xzz', 'total' => 2],
          (object) ['language' => 'en', 'total' => 1],
        ],
        'article' => [
          (object) ['language' => 'und', 'total' => 1],
          (object) ['language' => 'en', 'total' => 2],
          (object) ['language' => 'es', 'total' => 1],
        ],
      ],
      // Test 2.
      [
        'blog' => [
          (object) ['language' => 'en', 'total' => 1],
        ],
        'car' => [
          (object) ['language' => '', 'total' => 0],
        ],
      ],
      // Test 3.
      [
        'page' => [
          (object) ['language' => 'en', 'total' => 1],
          (object) ['language' => 'es', 'total' => 1],
        ],
        'car' => [
          (object) ['language' => '', 'total' => 0],
        ],
        'article' => [
          (object) ['language' => 'es', 'total' => 3],
        ],
      ],
      // Test 4.
      [
        'page' => [
          (object) ['total' => 1],
        ],
        'blog' => [
          (object) ['total' => 2],
        ],
        'car' => [
          (object) ['total' => 0],
        ],
        'article' => [
          (object) ['total' => 5],
        ],
      ],
      // Test 5.
      [
        'blog' => [
          (object) ['total' => 0],
        ],
        'car' => [
          (object) ['total' => 1],
        ],
      ],
      // Test 6.
      [
        'page' => [
          (object) ['total' => 1],
        ],
        'car' => [
          (object) ['total' => 5],
        ],
        'article' => [
          (object) ['total' => 3],
        ],
      ],
    ];

    // Multilingual tests.
    $tests['multi 1'] = [$expected[0], $configured[0], $content_types[0], TRUE];
    $tests['multi 2'] = [$expected[1], $configured[1], $content_types[1], TRUE];
    $tests['multi 3'] = [$expected[2], $configured[2], $content_types[2], TRUE];
    // Not-multilingual tests.
    $tests['not 1'] = [$expected[3], $configured[0], $content_types[3], FALSE];
    $tests['not 2'] = [$expected[4], $configured[1], $content_types[4], FALSE];
    $tests['not 3'] = [$expected[5], $configured[2], $content_types[5], FALSE];

    return $tests;
  }

  /**
   * Tests the OnlyOne::addAditionalInfoToContentTypes() method in isolation.
   *
   * @param array $expected
   *   The expected result from calling the function.
   * @param array $configured
   *   The configured content types to have onlyone node.
   * @param array $content_types
   *   The returned content types from the the database query.
   * @param bool $multilingual
   *   Whether the site is multilingual or not.
   *
   * @covers ::addAditionalInfoToContentTypes
   * @dataProvider providerGetAvailableContentTypesSummarized
   */
  public function testAddAditionalInfoToContentTypesIsolated(array $expected, array $configured, array $content_types, $multilingual) {
    // Mock OnlyOne.
    $controller = $this->getMockBuilder('Drupal\onlyone\OnlyOne')
      ->setConstructorArgs([
        $this->entityTypeManager,
        $this->connection,
        $this->languageManager,
        $this->configFactory,
        $this->getStringTranslationStub(),
      ])
      // Specify that we will also mock getContentTypesList() and
      // getLanguageLabel().
      ->setMethods(['getContentTypesList', 'getLanguageLabel'])
      ->getMock();

    // Mock getContentTypesList().
    $controller->expects($this->any())
      ->method('getContentTypesList')
      ->willReturn($this->getContentTypesList());

    // Mock getLanguageLabel().
    $controller->expects($this->any())
      ->method('getLanguageLabel')
      ->will($this->returnValueMap($this->getLanguageMap()));

    // ImmutableConfig mock.
    $config = $this->createMock('Drupal\Core\Config\ImmutableConfig');
    // ImmutableConfig::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('onlyone_node_types')
      ->willReturn($configured);

    // Mocking getEditable method.
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with('onlyone.settings')
      ->willReturn($config);

    // Mocking isMultilingual.
    $this->languageManager->expects($this->any())
      ->method('isMultilingual')
      ->willReturn($multilingual);

    // Making accesible the getTemporaryContentTypesTableName method.
    $method = new \ReflectionMethod($controller, 'addAditionalInfoToContentTypes');
    $method->setAccessible(TRUE);

    // Invoking the function.
    $method->invokeArgs($controller, [&$content_types]);
    // Asserting the values.
    $this->assertEquals($expected, $content_types);
  }

  /**
   * Tests the setFormatter() method.
   *
   * @param Drupal\onlyone\OnlyOnePrintStrategyInterface $formatter
   *   The formatter type.
   *
   * @covers ::setFormatter
   * @dataProvider providerSetFormatter
   */
  public function testSetFormatter(OnlyOnePrintStrategyInterface $formatter) {
    // Testing the function.
    $this->assertEquals($this->onlyOne, $this->onlyOne->setFormatter($formatter));
  }

  /**
   * Data provider for testSetFormatter().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'formatter' - The formatter type.
   *
   * @see testExistsNodesContentType()
   */
  public function providerSetFormatter() {
    $tests['admin page formatter'] = [new OnlyOnePrintAdminPage()];
    $tests['drush formatter'] = [new OnlyOnePrintDrush()];

    return $tests;
  }

}
