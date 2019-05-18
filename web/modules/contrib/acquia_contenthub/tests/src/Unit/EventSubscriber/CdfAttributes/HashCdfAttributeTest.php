<?php

namespace Drupal\Tests\acquia_contenthub\Unit\EventSubscriber\CdfAttributes;

use Acquia\ContentHubClient\CDFAttribute;
use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\CdfAttributesEvent;
use Drupal\acquia_contenthub\EventSubscriber\CdfAttributes\HashCdfAttribute;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class HashCdfAttributeTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Unit\EventSubscriber\CdfAttributes
 *
 * @covers \Drupal\acquia_contenthub\EventSubscriber\CdfAttributes\HashCdfAttribute
 */
class HashCdfAttributeTest extends UnitTestCase {

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->dispatcher = new EventDispatcher();
    $this->dispatcher->addSubscriber(new HashCdfAttribute());
  }

  /**
   * Tests 'hash' attribute population.
   *
   * @param array $data
   *   Fields data.
   *
   * @dataProvider onPopulateAttributesProvider
   */
  public function testOnPopulateAttributes(array $data) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getMockBuilder(ContentEntityInterface::class)
      ->disableOriginalConstructor()
      ->setMethods([])
      ->getMockForAbstractClass();
    $entity->method('toArray')->willReturn($data);

    $cdf = $this->getMockBuilder(CDFObject::class)
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $event = new CdfAttributesEvent($cdf, $entity);
    $this->dispatcher->dispatch(AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES, $event);

    $hash_attribute = $event->getCdf()->getAttribute('hash');

    $this->assertEquals(CDFAttribute::TYPE_STRING, $hash_attribute->getType());

    $expected = [
      CDFObject::LANGUAGE_UNDETERMINED => sha1(json_encode($data)),
    ];
    $this->assertArrayEquals($expected, $hash_attribute->getValue());
  }

  /**
   * Data provider for testOnPopulateAttributes.
   *
   * @return array
   *   Data sets.
   */
  public function onPopulateAttributesProvider() {
    return [
      [['title' => 'title']],
      [['title' => 'title', 'field_id' => 'value']],
    ];
  }

}
