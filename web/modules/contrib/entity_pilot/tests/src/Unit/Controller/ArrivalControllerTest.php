<?php

namespace Drupal\Tests\entity_pilot\Unit\Controller;

use Drupal\entity_pilot\Controller\ArrivalController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the controller.
 *
 * @coversDefaultClass \Drupal\entity_pilot\Controller\ArrivalController
 * @group entity_pilot
 */
class ArrivalControllerTest extends UnitTestCase {

  /**
   * Entity Manager service.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * Arrival storage.
   *
   * @var \Drupal\entity_pilot\ArrivalStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $arrivalStorage;

  /**
   * Config storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $accountStorage;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->arrivalStorage = $this->getMockBuilder('\Drupal\entity_pilot\Storage\ArrivalStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $this->accountStorage = $this->getMockBuilder('\Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests \Drupal\entity_pilot\Controller\ArrivalController::add().
   *
   * @see \Drupal\entity_pilot\Controller\ArrivalController::add()
   *
   * @covers ::add
   */
  public function testAdd() {
    $account_one = $this->getMockBuilder('\Drupal\entity_pilot\Entity\Account')
      ->disableOriginalConstructor()
      ->getMock();
    $account_two = $this->getMockBuilder('\Drupal\entity_pilot\Entity\Account')
      ->disableOriginalConstructor()
      ->getMock();
    $arrival = $this->getMockBuilder('\Drupal\entity_pilot\Entity\Arrival')
      ->disableOriginalConstructor()
      ->getMock();
    $this->accountStorage->expects($this->at(0))
      ->method('loadMultiple')
      ->will($this->returnValue([$account_one, $account_two]));
    $this->arrivalStorage->expects($this->any())
      ->method('create')
      ->will($this->returnValue($arrival));
    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->willReturnMap([
        ['ep_arrival', $this->arrivalStorage],
        ['ep_account', $this->accountStorage],
      ]);
    $entity_form_builder = $this->createMock('\Drupal\Core\Entity\EntityFormBuilderInterface');
    $entity_form_builder->expects($this->any())
      ->method('getForm')
      ->will($this->returnValue('MagicPoniesMadeMeDoIt'));
    $this->accountStorage->expects($this->at(1))
      ->method('loadMultiple')
      ->will($this->returnValue([$account_one]));
    $this->accountStorage->expects($this->at(2))
      ->method('loadMultiple')
      ->will($this->returnValue([]));

    $account_one->expects($this->any())
      ->method('id')
      ->will($this->returnValue('foo'));

    $account_one->expects($this->any())
      ->method('label')
      ->will($this->returnValue('Foo'));

    $account_one->expects($this->any())
      ->method('getDescription')
      ->will($this->returnValue('Some foo'));

    $account_one->expects($this->any())
      ->method('toUrl')
      ->will($this->returnValue('foo/bar'));

    $account_two->expects($this->any())
      ->method('id')
      ->will($this->returnValue('bar'));

    $account_two->expects($this->any())
      ->method('label')
      ->will($this->returnValue('Bar'));

    $account_two->expects($this->any())
      ->method('getDescription')
      ->will($this->returnValue('Some bar'));

    $account_two->expects($this->any())
      ->method('toUrl')
      ->will($this->returnValue('bar/foo'));

    $url_generator = $this->getMockBuilder('\Drupal\Core\Routing\UrlGeneratorInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $url_generator->expects($this->any())
      ->method('generateFromRoute')
      ->willReturnArgument(0);

    $string_translation = $this->getMockBuilder('\Drupal\Core\StringTranslation\TranslationInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $controller = new ArrivalController($this->entityManager, $entity_form_builder);
    $controller->setUrlGenerator($url_generator);
    $controller->setStringTranslation($string_translation);

    $output = $controller->add();
    $expected = [
      '#theme' => 'admin_block_content',
      '#content' => [
        'foo' => [
          'title' => 'Foo',
          'description' => 'Some foo',
          'url' => 'foo/bar',
          'localized_options' => [],
        ],
        'bar' => [
          'title' => 'Bar',
          'description' => 'Some bar',
          'url' => 'bar/foo',
          'localized_options' => [],
        ],
      ],
    ];
    $this->assertSame($output, $expected);

    $output = $controller->add();
    $this->assertSame($output, 'MagicPoniesMadeMeDoIt');

    $output = $controller->add();
    $this->assertSame($output[0]['#markup']->getUntranslatedString(), 'No Entity Pilot accounts have been created.<br/>Please <a href=":url">create an account</a> first.');
  }

  /**
   * Tests \Drupal\entity_pilot\Controller\ArrivalController::addForm().
   *
   * @see \Drupal\entity_pilot\Controller\ArrivalController::addForm()
   *
   * @covers ::addForm
   */
  public function testAddForm() {
    $arrival = $this->getMockBuilder('\Drupal\entity_pilot\Entity\Arrival')
      ->disableOriginalConstructor()
      ->getMock();
    $this->arrivalStorage->expects($this->any())
      ->method('create')
      ->will($this->returnValue($arrival));
    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->with('ep_arrival')
      ->will($this->returnValue($this->arrivalStorage));
    $entity_form_builder = $this->createMock('\Drupal\Core\Entity\EntityFormBuilderInterface');
    $entity_form_builder->expects($this->any())
      ->method('getForm')
      ->will($this->returnValue('MagicPoniesMadeMeDoIt'));
    $account = $this->getMockBuilder('\Drupal\entity_pilot\Entity\Account')
      ->disableOriginalConstructor()
      ->getMock();

    $controller = new ArrivalController($this->entityManager, $entity_form_builder);

    $output = $controller->addForm($account);
    $this->assertSame($output, 'MagicPoniesMadeMeDoIt');
  }

}
