<?php

namespace Drupal\Tests\ivw_integration\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\Config;
use Drupal\ivw_integration\IvwLookupService;

/**
 * Provides automated tests for the ivw_integration module.
 *
 * @group ivw_integration
 */
class IvwLookupServiceTest extends UnitTestCase {

  /**
   * The route match mock.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatchMock;

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManagerMock;

  /**
   * Config Factory Mock -> provides base configuration required for Testing.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactoryMock;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "IvwLookupServic's controller functionality",
      'description' => 'Test Unit for module ivw_integration and service IvwLookupServic.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->routeMatchMock = $this->getMock('\Drupal\Core\Routing\RouteMatchInterface');
    $this->entityTypeManagerMock = $this->getMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->setUpConfigFactoryMock();
  }

  /**
   * Setup Config relevant for proper functioning of tests.
   */
  protected function setUpConfigFactoryMock() {
    $this->configFactoryMock = $this->getMock('\Drupal\Core\Config\ConfigFactoryInterface');

    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $typed_config = $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface');
    $config = new Config('ivw_integration', $storage, $event_dispatcher, $typed_config);
    $config->set('site', 'TestSiteName');
    $config->set('mobile_site', 'TestMobileSiteName');
    $config->set('frabo', 'IN');
    $config->set('frabo_mobile', 'mo');
    $config->set('frabo_overridable', 0);
    $config->set('frabo_mobile_overridable', 0);
    $config->set('code_template', '[ivw:offering]L[ivw:language]F[ivw:format]S[ivw:creator]H[ivw:homepage]D[ivw:delivery]A[ivw:app]P[ivw:paid]C[ivw:content]');
    $config->set('responsive', 1);
    $config->set('mobile_width', 480);
    $config->set('offering_default', '01');
    $config->set('offering_overridable', 0);
    $config->set('language_default', 1);
    $config->set('language_overridable', 0);
    $config->set('format_default', 1);
    $config->set('format_overridable', 0);
    $config->set('creator_default', 1);
    $config->set('creator_overridable', 0);
    $config->set('homepage_default', 2);
    $config->set('homepage_overridable', 0);
    $config->set('delivery_default', 1);
    $config->set('delivery_overridable', 0);
    $config->set('app_default', 1);
    $config->set('app_overridable', 0);
    $config->set('paid_default', 1);
    $config->set('paid_overridable', 0);
    $config->set('content_default', '01');
    $config->set('content_overridable', 1);
    $config->set('mcvd', 0);

    $this->configFactoryMock->expects($this->once())
      ->method('get')
      ->willReturn($config);
  }

  /**
   * Test default values for byCurrentRoute method.
   *
   * @param string $name
   *   The property to look up.
   * @param bool $parentOnly
   *   If set to TRUE, skips lookup on first level ivw_settings field.
   *   This is used when determining which property the
   *   currently examined entity WOULD inherit if it had
   *   no property for $name in its own ivw settings.
   * @param string $expected
   *   Expected result of byCurrentRoute execution.
   *
   * @dataProvider byCurrentRouteDataProvider
   */
  public function testByCurrentRouteDefaultValues($name, $parentOnly, $expected) {
    $ivwLookupService = new IvwLookupService($this->routeMatchMock, $this->configFactoryMock, $this->entityTypeManagerMock);
    $value = $ivwLookupService->byCurrentRoute($name, $parentOnly);

    $this->assertEquals($expected, $value);
  }

  /**
   * Data provider for byCurrentRoute method related tests.
   *
   * @return array
   *   Return test cases for byCurrentRoute.
   */
  public function byCurrentRouteDataProvider() {
    return [
      ['offering', FALSE, '01'],
      ['language', FALSE, 1],
      ['format', FALSE, 1],
      ['creator', FALSE, 1],
      ['homepage', FALSE, 2],
      ['delivery', FALSE, 1],
      ['app', FALSE, 1],
      ['paid', FALSE, 1],
      ['content', FALSE, '01'],
    ];
  }

}
