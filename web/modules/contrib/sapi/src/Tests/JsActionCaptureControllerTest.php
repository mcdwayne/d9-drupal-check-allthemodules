<?php

namespace Drupal\sapi\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the sapi JS action controller.
 *
 * @group sapi
 */
class JsActionCaptureControllerTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('sapi');

  /**
   * A user with the 'sapi capture js actions' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Path to JS action capturer.
   *
   * @var string $path
   */
  protected $path = 'sapi/js/capture';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->webUser = $this->drupalCreateUser(array('sapi capture js actions'));
  }

  /**
   * Tests capture with empty parameters.
   */
  public function testCaptureWithInvalidParams() {
    $this->drupalLogin($this->webUser);
    $this->drupalPost($this->path, '', array());
    $this->assertResponse(400);
  }

  /**
   * Tests capture with missing parameters.
   */
  public function testCaptureWithMissingParams() {
    $this->drupalLogin($this->webUser);
    $this->drupalPost($this->path, '', array('action' => 'click'));
    $this->assertResponse(400);
  }

  /**
   * Tests a valid capture.
   */
  public function testValidCapture() {
    $this->drupalLogin($this->webUser);
    $this->drupalPost($this->path, '', array('action' => 'click', 'uri' => 'http://www.example.com'));
    $this->assertResponse(200);
  }

  /**
   * Tests a valid capture.
   */
  public function testUnreachableSapiService() {
    // @todo Invoke controller class directly and try to fail the service call.
    // $this->assertResponse(500, 'Response is 500 if SAPI service is not reachable.');
  }

  /**
   * Tests capture with non-authorized user.
   */
  public function testCaptureWithNonAuthorizedUser() {
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalPost('sapi/js/capture', '', array('action' => 'click', 'uri' => 'http://www.example.com'));
    $this->assertResponse(403);
  }

}
