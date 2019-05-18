<?php
/**
 * @file
 * Contains \Drupal\Tests\collect\Unit\Plugin\rest\resource\CollectResourceTest.
 */

namespace Drupal\Tests\collect\Unit\Plugin\rest\resource;

use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests the Collect REST resource.
 *
 * @group collect
 */
class CollectResourceTest extends UnitTestCase {

  /**
   * The tested resource.
   *
   * @var \Drupal\collect\Plugin\rest\resource\CollectResource
   */
  protected $resource;

  /**
   * The mocked serializer service.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $serializer;

  /**
   * The mocked url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked queue for processing submissions.
   *
   * @var \Drupal\Core\Queue\QueueInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $queue;

  /**
   * The mocked logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * The mocked model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * The mocked query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->serializer = $this->getMock('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->queue = $this->getMock('Drupal\Core\Queue\QueueInterface');
    $this->logger = $this->getMock('Drupal\Core\Logger\LoggerChannelInterface');
    $this->modelManager = $this->getMock('Drupal\collect\Model\ModelManagerInterface');
    $this->queryFactory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $this->generated_url = $this->getMockBuilder('Drupal\Core\GeneratedUrl')
      ->disableOriginalConstructor()
      ->getMock();

    $this->resource = $this->getMockBuilder('Drupal\collect\Plugin\rest\resource\CollectResource')
      ->setConstructorArgs([
        $this->serializer,
        $this->urlGenerator,
        $this->entityManager,
        $this->queue,
        array(),
        'collect',
        array(),
        array(),
        $this->logger,
        $this->modelManager,
        $this->queryFactory
      ])->setMethods(NULL)->getMock();
  }

  /**
   * Tests GET of a exiting submission.
   */
  public function testGet() {
    $uuid = 'd18cf563-f20f-4e3c-ad7e-01818f1baeac';
    $url = 'http://example.com/collect/api/v1/submissions/d18cf563-f20f-4e3c-ad7e-01818f1baeac';
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $route = $this->getMockBuilder('Symfony\Component\Routing\Route')
      ->disableOriginalConstructor()
      ->getMock();
    $request->attributes = new ParameterBag(array(
      RouteObjectInterface::ROUTE_OBJECT => $route,
    ));
    $container = $this->getMockBuilder('Drupal\collect\Entity\Container')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityManager->expects($this->once())
      ->method('loadEntityByUuid')
      ->with('collect_container', $uuid)
      ->will($this->returnValue($container));

    $route->expects($this->any())
      ->method('getRequirement')
      ->with('_format')
      ->will($this->returnValue('hal_json'));

    $this->serializer->expects($this->once())
      ->method('normalize')
      ->with($container, 'hal_json')
      ->will($this->returnValue(array()));

    $this->generated_url->expects($this->once())
      ->method('getGeneratedUrl')
      ->will($this->returnValue($url));

    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->will($this->returnValue($this->generated_url));

    $result = $this->resource->get($uuid, NULL, $request);
    $data = $result->getResponseData();
    $this->assertEquals($data['_links']['self']['href'], $url);
  }

  /**
   * Tests the submission of new records.
   */
  public function testPost() {
    $container = $this->getMockBuilder('Drupal\collect\Entity\Container')
      ->disableOriginalConstructor()
      ->getMock();

    $location = 'http://example.com/collect/api/v1/submissions/d18cf563-f20f-4e3c-ad7e-01818f1baeac';

    $this->generated_url->expects($this->once())
      ->method('getGeneratedUrl')
      ->will($this->returnValue($location));

    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->will($this->returnValue($this->generated_url));

    $collect_storage = $this->getMockBuilder('Drupal\collect\CollectStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with('collect_container')
      ->willReturn($collect_storage);

    $collect_storage->expects($this->once())
      ->method('persist')
      ->with($container, FALSE)
      ->willReturn($container);

    $result = $this->resource->post($container);
    $this->assertEquals($result->getStatusCode(), 201);
    $this->assertEquals($result->headers->get('Location'), $location);
  }

}
