<?php

namespace Drupal\Tests\entity_pilot\Unit;

use Drupal\entity_pilot\Customs;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Tests Customs service.
 *
 * @group entity_pilot
 *
 * @coversDefaultClass \Drupal\entity_pilot\Customs
 */
class CustomsTest extends UnitTestCase {

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $serializer;

  /**
   * The rest resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $resourceManager;

  /**
   * The typed link manager service.
   *
   * @var \Drupal\rest\LinkManager\TypeLinkManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typeLinkManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The plugin manager for determining if an entity already exists.
   *
   * @var \Drupal\entity_pilot\ExistsPluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $existsPluginManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $loggerFactory;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * Resolver service.
   *
   * @var \Drupal\entity_pilot\EntityResolver\UnsavedUuidResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $resolver;

  /**
   * Customs service under test.
   *
   * @var \Drupal\entity_pilot\CustomsInterface
   */
  protected $customs;

  /**
   * Mock cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    $this->serializer = $this->createMock('\Symfony\Component\Serializer\Serializer');
    $this->resourceManager = $this->createMock('\Drupal\rest\Plugin\Type\ResourcePluginManager', [], [], '', FALSE);
    $this->typeLinkManager = $this->createMock('\Drupal\rest\LinkManager\TypeLinkManagerInterface');
    $this->entityManager = $this->createMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->existsPluginManager = $this->createMock('\Drupal\entity_pilot\ExistsPluginManagerInterface');
    $this->logger = $this->createMock('\Psr\Log\LoggerInterface');
    $this->resolver = $this->createMock('\Drupal\entity_pilot\EntityResolver\UnsavedUuidResolverInterface');
    $this->loggerFactory = $this->createMock('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $this->cache = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    $this->loggerFactory->expects($this->any())
      ->method('get')
      ->willReturn($this->logger);
    $this->customs = $customs = new Customs($this->serializer, $this->resourceManager, $this->entityManager, $this->typeLinkManager, $this->existsPluginManager, $this->resolver, $this->getStringTranslationStub(), $this->loggerFactory, $this->cache, $this->createMock(EventDispatcher::class));
  }

  /**
   * Tests construct.
   *
   * @covers ::__construct
   * @covers ::previewPassenger
   */
  public function testPreviewInvalid() {
    $this->setExpectedException('\InvalidArgumentException');
    $this->customs->previewPassenger('fooey');
  }

  /**
   * Tests construct.
   *
   * @covers ::previewPassenger
   * @covers ::screen
   * @covers ::addEdges
   * @covers ::sortTree
   * @covers ::resetTree
   * @covers ::getVertex
   * @covers ::approve
   * @covers ::approvePassenger
   * @covers ::exists
   */
  public function testPreview() {
    $flight = $this->createMock('\Drupal\entity_pilot\ArrivalInterface');

    $passenger_one = [
      '_links' => [
        'type' => [
          'href' => 'monkey pants',
        ],
      ],
    ];
    $passenger_two = [
      '_links' => [
        'type' => [
          'href' => 'gorilla biscuit',
        ],
      ],
    ];
    $map = [
      'pony-uuid' => [
        '_links' => [
          'type' => [
            'href' => 'gorilla biscuit',
          ],
        ],
        '_embedded' => [
          [
            [
              '_links' => [
                'self' => [
                  'href' => 'fooey',
                ],
              ],
              'uuid' => [
                [
                  'value' => 'fooey-uuid',
                ],
              ],
            ],
          ],
        ],
      ],
      'fooey-uuid' => [
        '_links' => [
          'type' => [
            'href' => 'monkey pants',
          ],
        ],
        '_embedded' => [],
        'uuid' => [
          [
            'value' => 'pony-uuid',
          ],
        ],
      ],
    ];
    $flight->expects($this->any())
      // @codingStandardsIgnoreStart
      ->method('getPassengers')
      ->willReturnCallback(function($id = NULL) use ($passenger_one, $passenger_two, $map) {
        if ($id == NULL) {
          return $map;
        }
        elseif ($id == 'fooey-uuid') {
          return $passenger_one;
        }
        elseif ($id == 'pony-uuid') {
          return $passenger_two;
        }
        return NULL;
      });
    // @codingStandardsIgnoreEnd
    $this->typeLinkManager->expects($this->any())
      ->method('getTypeInternalIds')
      ->willReturn(['entity_type' => 'schnitzel_pirates']);
    $entity_type = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getClass')
      ->willReturn('KillerPandas');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with('schnitzel_pirates')
      ->willReturn($entity_type);
    $mock_entity = $this->createMock('\Drupal\node\NodeInterface');
    $this->serializer->expects($this->at(0))
      ->method('denormalize')
      ->with($map['fooey-uuid'], 'KillerPandas', 'hal_json')
      ->willReturn($mock_entity);
    $this->resolver->expects($this->once())
      ->method('add')
      ->with($mock_entity);
    $request = $this->createMock('Psr\Http\Message\RequestInterface');
    $request->expects($this->once())
      ->method('getUri')
      ->willReturn('http://example.com');
    $exception = new ClientException('fooey', $request, $this->createMock('Psr\Http\Message\ResponseInterface'));
    $this->serializer->expects($this->at(1))
      ->method('denormalize')
      ->with($map['pony-uuid'], 'KillerPandas', 'hal_json')
      ->willThrowException($exception);
    $this->logger->expects($this->exactly(5))
      ->method('error');
    $denormalized = $this->customs->screen($flight);
    $this->assertEquals($denormalized, ['fooey-uuid' => $mock_entity]);
    $this->assertEquals($this->customs->previewPassenger('fooey-uuid'), $mock_entity);

    // Test approvals.
    $flight->expects($this->any())
      ->method('getApproved')
      ->willReturn(['pony-uuid', 'fooey-uuid']);
    $this->existsPluginManager->expects($this->exactly(2))
      ->method('exists')
      ->with($this->entityManager, $mock_entity)
      ->willReturn($mock_entity);
    $this->existsPluginManager->expects($this->at(0))
      ->method('preApprove');
    $mock_entity->expects($this->once())
      ->method('save');
    $saved = $this->customs->approve($flight);
    $this->assertEquals($saved, [$mock_entity]);

    // Test approval error.
    $this->existsPluginManager->expects($this->once())
      ->method('preApprove')
      ->willThrowException(new \Exception('foobar'));
    $saved = $this->customs->approve($flight);
    $this->assertEquals($saved, []);
  }

}
