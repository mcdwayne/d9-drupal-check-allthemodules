<?php

namespace Drupal\Tests\user_request\Traits;

use Drupal\Core\Cache\Cache;

/**
 * Methods to mock requests and related fields.
 */
trait RequestMockTrait {

  protected function mockRequestState(array $values = []) {
    $state = $this->getMock(
      '\Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface');
    $state
      ->expects($this->any())
      ->method('getId')
      ->will($this->returnValue(
        isset($values['id']) ? $values['id'] : 'state' . rand()));

    if (isset($values['transitions'])) {
      $transitions = [];
      foreach ($values['transitions'] as $transition) {
        $transitions[$transition->getId()] = $transition;
      }
      $state
        ->expects($this->any())
        ->method('getTransitions')
        ->will($this->returnValue($transitions));
    }
    return $state;
  }

  protected function mockRequestType(array $values = []) {
    $request_type = $this->getMock(
      '\Drupal\user_request\Entity\RequestTypeInterface');
    $request_type
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(isset($values['id']) ? $values['id'] : 'user_request'));
    if (isset($values['response_transitions'])) {
      $request_type
        ->expects($this->any())
        ->method('getResponseTransitions')
        ->will($this->returnValue($values['response_transitions']));
    }
    if (isset($values['deleted_response_transition'])) {
      $request_type
        ->expects($this->any())
        ->method('getDeletedResponseTransition')
        ->will($this->returnValue($values['deleted_response_transition']));
    }
    if (isset($values['messages'])) {
      $request_type
        ->expects($this->any())
        ->method('getMessages')
        ->will($this->returnValue($values['messages']));
    }
    return $request_type;
  }

  protected function mockRequest(array $values = []) {
    // Creates a basic mock.
    $language = $this->getMock('\Drupal\Core\Language\LanguageInterface');
    $language
      ->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));
    $request = $this->getMockBuilder('\Drupal\user_request\Entity\Request')
      ->disableOriginalConstructor()
      ->getMock();
    $request
      ->expects($this->any())
      ->method('language')
      ->will($this->returnValue($language));
    $request
      ->expects($this->any())
      ->method('getCacheContexts')
      ->will($this->returnValue([]));
    $request
      ->expects($this->any())
      ->method('getCacheTags')
      ->will($this->returnValue([]));
    $request
      ->expects($this->any())
      ->method('getCacheMaxAge')
      ->will($this->returnValue(Cache::PERMANENT));
    $request
      ->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('user_request'));
    $request
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(isset($values['id']) ? $values['id'] : rand()));

    // Sets the bundle with either a string or a request type entity.
    if (isset($values['user_request_type'])) {
      // Uses the provided request type entity.
      $request_type = $values['user_request_type'];
    }
    else {
      // Creates a request type entity.
      $request_type = $this->mockRequestType([
        'id' => isset($values['type']) ? $values['type'] : NULL,
      ]);
    }
    $request
      ->expects($this->any())
      ->method('getRequestType')
      ->will($this->returnValue($request_type));
    $request
      ->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue($request_type->id()));

    // Fills passed values.
    if (isset($values['owner'])) {
      $owner = $values['owner'];
      $request
        ->expects($this->any())
        ->method('getOwner')
        ->will($this->returnValue($owner));
      $request
        ->expects($this->any())
        ->method('getOwnerId')
        ->will($this->returnValue($owner->id()));
    }
    if (isset($values['state'])) {
      $state = $values['state'];
      $request
        ->expects($this->any())
        ->method('getState')
        ->will($this->returnValue($state));
      $request
        ->expects($this->any())
        ->method('getStateString')
        ->will($this->returnValue($state->getId()));
    }
    if (isset($values['response'])) {
      $request
        ->expects($this->any())
        ->method('getResponse')
        ->will($this->returnValue($values['response']));
    }
    if (isset($values['removed_response'])) {
      $request
        ->expects($this->any())
        ->method('getRemovedResponse')
        ->will($this->returnValue($values['removed_response']));
    }

    // Sets recipients.
    $recipients = [];
    if (isset($values['recipients'])) {
      foreach ($values['recipients'] as $recipient) {
        $recipient_id = $recipient->id();
        $recipients[$recipient_id] = $recipient;
      }
    }
    $request
      ->expects($this->any())
      ->method('getRecipients')
      ->will($this->returnValue($recipients));

    return $request;
  }

}
