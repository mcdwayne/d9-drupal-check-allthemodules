<?php

namespace Drupal\Tests\radioactivity\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Site\Settings;
use Drupal\radioactivity\Controller\EmitController;
use Drupal\radioactivity\DefaultIncidentStorage;
use Drupal\radioactivity\Incident;
use Drupal\radioactivity\IncidentStorageInterface;
use Drupal\radioactivity\StorageFactory;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\radioactivity\Controller\EmitController
 * @group radioactivity
 */
class EmitControllerTest extends UnitTestCase {

  /**
   * The request received by the controller.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $container;

  /**
   * The incident storage factory.
   *
   * @var \Drupal\radioactivity\StorageFactory
   */
  protected $incidentStorageFactory;

  /**
   * The default incident storage.
   *
   * @var \Drupal\radioactivity\DefaultIncidentStorage
   */
  protected $defaultIncidentStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Initiate the Settings singleton used by this test.
    new Settings([
      'hash_salt' => 'liesjeleerdelotjelopen',
    ]);

    $this->request = $this->prophesize(Request::class);

    $this->incidentStorageFactory = $this->prophesize(StorageFactory::class);
    $this->defaultIncidentStorage = $this->prophesize(IncidentStorageInterface::class);
    $this->incidentStorageFactory->get('default')->willReturn($this->defaultIncidentStorage->reveal());

    $this->container = $this->prophesize(ContainerInterface::class);
    \Drupal::setContainer($this->container->reveal());
    $this->container->get('radioactivity.storage')->willReturn($this->incidentStorageFactory->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->verifyMockObjects();
  }

  /**
   * @covers ::emit
   */
  public function testEmitEmptyRequest() {

    $this->request->getContent()->willReturn(NULL);

    $controller = EmitController::create($this->container->reveal());
    $response = $controller->emit($this->request->reveal());

    $this->defaultIncidentStorage->addIncident()->shouldNotBeCalled();

    $this->assertEquals('Symfony\Component\HttpFoundation\JsonResponse', get_class($response));
    $this->assertEquals('{"status":"error","message":"Empty request."}', $response->getContent());
  }

  /**
   * @covers ::emit
   */
  public function testEmitValidRequest() {
    $postData = Json::encode([
      [
        'fn' => 'field_name',
        'et' => 'entity_type',
        'id' => '99',
        'e' => 5.5,
        'h' => '5aa2ff01ac75da55751051a55021092768d079c5',
      ],
    ]);
    $this->request->getContent()->willReturn($postData);

    $sut = EmitController::create($this->container->reveal());
    $response = $sut->emit($this->request->reveal());

    $this->defaultIncidentStorage->addIncident(Argument::type(Incident::class))->shouldBeCalledTimes(1);

    $this->assertEquals('Symfony\Component\HttpFoundation\JsonResponse', get_class($response));
    $this->assertEquals('{"status":"ok","message":"1 incidents added."}', $response->getContent());
  }

  /**
   * @covers ::emit
   */
  public function testEmitInvalidRequest() {
    $postData = Json::encode([
      [
        'fn' => 'field_name',
        'et' => 'entity_type',
        'id' => '99',
        'e' => 5.5,
        'h' => '',
      ],
    ]);
    $this->request->getContent()->willReturn($postData);

    $controller = EmitController::create($this->container->reveal());
    $response = $controller->emit($this->request->reveal());

    $this->defaultIncidentStorage->addIncident(Argument::type(Incident::class))->shouldNotBeCalled(0);

    $this->assertEquals('Symfony\Component\HttpFoundation\JsonResponse', get_class($response));
    $this->assertEquals('{"status":"error","message":"invalid incident (0)."}', $response->getContent());
  }

  /**
   * @covers ::emit
   */
  public function testEmitMultipleRequest() {
    $postData = Json::encode([
      [
        'fn' => 'field_name',
        'et' => 'entity_type',
        'id' => '99',
        'e' => 5.5,
        'h' => '5aa2ff01ac75da55751051a55021092768d079c5',
      ],
      [
        'fn' => 'field_name',
        'et' => 'entity_type',
        'id' => '99',
        'e' => 3.3,
        'h' => '',
      ],
    ]);
    $this->request->getContent()->willReturn($postData);

    $controller = EmitController::create($this->container->reveal());
    $response = $controller->emit($this->request->reveal());

    $this->defaultIncidentStorage->addIncident(Argument::type(Incident::class))->shouldBeCalledTimes(1);

    $this->assertEquals('Symfony\Component\HttpFoundation\JsonResponse', get_class($response));
    $this->assertEquals('{"status":"error","message":"invalid incident (1)."}', $response->getContent());
  }

}
