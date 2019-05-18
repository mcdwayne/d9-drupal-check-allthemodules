<?php

namespace Drupal\Tests\dcat_export\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dcat_export\DcatExportService;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\dcat_export\DcatExportService
 * @group dcat_export
 */
class DcatExportServiceTest extends UnitTestCase {

  /**
   * Config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The DCAT export service.
   *
   * @var \Drupal\dcat_export\DcatExportService
   */
  protected $dcatExportService;

  /**
   * Easy Rdf graph object.
   *
   * @var \EasyRdf_Graph
   */
  protected $graph;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Provide mandatory settings.
    $this->configFactory = $this->getConfigFactoryStub([
      'dcat_export.settings' => [
        'formats' => ['rdf'],
        'catalog_title' => 'The title',
        'catalog_description' => 'The description',
        'catalog_uri' => 'http://www.catalog-example.com',
        'catalog_language_uri' => 'http://www.lexvo.org/page/iso639-3/eng',
        'catalog_homepage_uri' => 'http://www.homepage-example.com',
        'catalog_issued' => '2011-12-05',
        'catalog_publisher_uri' => 'http://www.publisher-example.com',
        'catalog_publisher_name' => 'The publisher',
        'catalog_license_uri' => 'http://www.opendefinition.org/licenses/cc-zero',
      ],
    ]);

    $this->dcatExportService = $this->getDcatExportService();
    $this->graph = new \EasyRdf_Graph();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    unset($this->configFactory, $this->dcatExportService, $this->graph);
  }

  /**
   * Test addLiteral().
   *
   * @dataProvider providerAddLiteral
   */
  public function testAddLiteral($expected, $values) {
    $resource = $this->graph->resource('http://www.example.com/');
    $this->assertSame($expected, $this->dcatExportService->addLiteral($resource, 'dcat:title', $values));
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerAddLiteral() {
    return [
      [1, 'The title'],
      [2, ['The title', 'The second title']],
      [0, ''],
    ];
  }

  /**
   * Test addResourceSilently().
   *
   * @dataProvider providerAddResourceSilently
   */
  public function testAddResourceSilently($expected, $value) {
    $resource = $this->graph->resource('http://www.resource-example.com/', ['vcard:Kind']);
    $this->assertSame($expected, $this->dcatExportService->addResourceSilently($resource, 'vcard:hasEmail', $value));
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerAddResourceSilently() {
    return [
      [1, 'mailto:john@doe.com'],
      [0, ''],
    ];
  }

  /**
   * Return a DcatExportController class.
   *
   * @return \Drupal\dcat_export\DcatExportService
   *   The DcatExportService.
   */
  protected function getDcatExportService() {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $event_dispatcher = $this->createMock(EventDispatcherInterface::class);

    return new DcatExportService($this->configFactory, $entity_type_manager, $event_dispatcher);
  }

}
