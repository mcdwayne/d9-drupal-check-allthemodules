<?php

namespace Drupal\Tests\amazon_sns\Unit;

/**
 * Trait to assist in loading the plain text message fixture.
 */
trait PlainTextMessageTrait {

  /**
   * Return $_SERVER variables for this fixture.
   *
   * @return array
   *   The $_SERVER variables.
   */
  protected function getFixtureServer() {
    return require __DIR__ . '/../../fixtures/plain-text-message-server.php';
  }

  /**
   * Return the body of the fixture response.
   *
   * @return string
   *   The response body.
   */
  protected function getFixtureBody() {
    return file_get_contents(__DIR__ . '/../../fixtures/plain-text-message.json');
  }

}
