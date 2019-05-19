<?php

namespace Drupal\Tests\user_request\Unit;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase as CoreUnitTestCase;
use Drupal\Tests\user_request\Traits\RequestMockTrait;

/**
 * Base class for unit tests.
 *
 * @group user_request
 */
abstract class UnitTestCase extends CoreUnitTestCase {
  use RequestMockTrait;

  protected function mockUser(array $values = []) {
    $account = $this->getMock('\Drupal\user\UserInterface');
    $account
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(
        isset($values['id']) ? $values['id'] : rand()));
    return $account;
  }

  protected function mockStateTransition(array $values = []) {
    $transition = $this->getMockBuilder(
      '\Drupal\state_machine\Plugin\Workflow\WorkflowTransition')
      ->disableOriginalConstructor()
      ->getMock();
    $transition
      ->expects($this->any())
      ->method('getId')
      ->will($this->returnValue(
        isset($values['id']) ? $values['id'] : 'transition' . rand()));
    return $transition;
  }

  protected function mockResponse(array $values = []) {
    // Creates a basic mock.
    $language = $this->getMock('\Drupal\Core\Language\LanguageInterface');
    $language
      ->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));
    $response = $this->getMockBuilder('\Drupal\user_request\Entity\Response')
      ->disableOriginalConstructor()
      ->getMock();
    $response
      ->expects($this->any())
      ->method('language')
      ->will($this->returnValue($language));
    $response
      ->expects($this->any())
      ->method('getCacheContexts')
      ->will($this->returnValue([]));
    $response
      ->expects($this->any())
      ->method('getCacheTags')
      ->will($this->returnValue([]));
    $response
      ->expects($this->any())
      ->method('getCacheMaxAge')
      ->will($this->returnValue(Cache::PERMANENT));
    $response
      ->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('user_request_response'));
    $response
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(isset($values['id']) ? $values['id'] : rand()));
    $response
      ->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue(isset($values['type']) ? $values['type'] : 'user_request_response'));

    // Fills provided values.
    if (isset($values['owner'])) {
      $owner = $values['owner'];
      $response
        ->expects($this->any())
        ->method('getOwner')
        ->will($this->returnValue($owner));
      $response
        ->expects($this->any())
        ->method('getOwnerId')
        ->will($this->returnValue($owner->id()));
    }
    if (isset($values['request'])) {
      $response
        ->expects($this->any())
        ->method('getRequest')
        ->will($this->returnValue($values['request']));
    }

    return $response;
  }

  /**
   * {@inheritdocs}
   */
  protected function setUp() {
    parent::setUp();

    // Sets the service container to prevent errors in the CI server.
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $cache_context_mngr = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_context_mngr
      ->expects($this->any())
      ->method('assertValidTokens')
      ->will($this->returnValue(TRUE));
    $container->set('cache_contexts_manager', $cache_context_mngr);
  }

}
