<?php

namespace Drupal\Tests\agreement\Functional;

use Drupal\agreement\Entity\Agreement;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the agreement functionality.
 */
abstract class AgreementTestBase extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'user',
    'filter',
    'views',
    'agreement',
  ];

  /**
   * A page node to test with.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * An alternate page node to test.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $otherNode;

  /**
   * Agreement to test with.
   *
   * @var \Drupal\agreement\Entity\Agreement
   */
  protected $agreement;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set front page to "node".
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('page.front', '/node')
      ->save(TRUE);

    // Create page type.
    $this->createContentType(['type' => 'page', 'name' => 'Page']);
    $this->container->get('router.builder')->rebuild();

    // Create a page nodes.
    /* @var \Drupal\node\Entity\Node node */
    $this->node = $this->createNode();

    /* @var \Drupal\node\Entity\Node node */
    $this->otherNode = $this->createNode();

    // Load the default agreement.
    $this->agreement = $this->container
      ->get('entity_type.manager')
      ->getStorage('agreement')
      ->load('default');

    // Set the usual default for the test.
    $settings = $this->agreement->getSettings();
    $settings['visibility']['pages'] = ['<front>'];
    $this->agreement->set('settings', $settings);
    $this->agreement->save();
    $this->assertEquals($settings, $this->agreement->getSettings());
  }

  /**
   * Create a privileged user account.
   *
   * @return \Drupal\user\Entity\User|false
   *   The user account.
   */
  public function createPrivilegedUser() {
    return $this->createUser([
      'administer agreements',
      'bypass node access',
      'access administration pages',
      'administer site configuration',
      'access content',
    ]);
  }

  /**
   * Create an unprivileged user account.
   *
   * @return \Drupal\user\Entity\User|false
   *   The user account.
   */
  public function createUnprivilegedUser() {
    return $this->createUser(['access content']);
  }

  /**
   * Create a user account that can bypass agreements.
   *
   * @return \Drupal\user\Entity\User|false
   *   The user account.
   */
  public function createBypassUser() {
    return $this->createUser(['access content', 'bypass agreement']);
  }

  /**
   * Create a user account that can revoke own agreement.
   *
   * @return \Drupal\user\Entity\User|false
   *   The user account.
   */
  public function createRevokeUser() {
    return $this->createUser(['access content', 'revoke own agreement']);
  }

  /**
   * Assert that the current page is the agreement page.
   *
   * @param \Drupal\agreement\Entity\Agreement $agreement
   *   The agreement entity to assert.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function assertAgreementPage(Agreement $agreement) {
    $settings = $agreement->getSettings();

    $this->assertStringEndsWith($agreement->get('path'), $this->getUrl(), 'URL is agreement page.');
    $this->assertSession()->titleEquals($settings['title'] . ' | Drupal');
    $this->assertSession()->pageTextContains($settings['checkbox']);
  }

  /**
   * Assert that the current page is not the agreement page.
   *
   * @param \Drupal\agreement\Entity\Agreement $agreement
   *   The agreement entity to assert.
   */
  public function assertNotAgreementPage(Agreement $agreement) {
    $this->assertStringEndsNotWith($agreement->get('path'), $this->getUrl(), 'URL is not agreement page.');
  }

  /**
   * Asserts that the current page is the front page.
   *
   * @param string $message
   *   The message to display for the assertion.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function assertFrontPage($message = 'Page is the front page.') {
    $body = $this->assertSession()->elementExists('xpath', 'body');
    $this->assertTrue($body->hasClass('path-frontpage'), $message);
  }

  /**
   * Asserts that the current page is a user's profile.
   *
   * @param int $uid
   *   The user id.
   */
  public function assertUserProfilePage($uid = 0) {
    $this->assertStringEndsWith('/user/' . $uid, $this->getUrl());
  }

  /**
   * Asserts that the current page is a user's profile.
   *
   * @param int $uid
   *   The user id.
   */
  public function assertUserProfileEditPage($uid = 0) {
    $this->assertStringEndsWith('/user/' . $uid . '/edit', $this->getUrl());
  }

  /**
   * Asserts that the user has not agreed to the agreement.
   *
   * @param \Drupal\agreement\Entity\Agreement $agreement
   *   The agreement to agree to.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function assertNotAgreed(Agreement $agreement) {
    $settings = $agreement->getSettings();
    $this->drupalPostForm($agreement->get('path'), [], $settings['submit']);
    $this->assertSession()->pageTextContains($settings['failure']);
  }

  /**
   * Asserts that the user has agreed to the agreement.
   *
   * @param \Drupal\agreement\Entity\Agreement $agreement
   *   The agreement to agree to.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function assertAgreed(Agreement $agreement) {
    $settings = $agreement->getSettings();
    $this->drupalPostForm($agreement->get('path'), ['agree' => 1], $settings['submit']);

    // Check for redirects. It's odd that drupalPostForm doesn't handle this but
    // drupalGet does.
    if ($this->checkForMetaRefresh()) {
      $this->metaRefreshCount = 0;
    }

    $this->assertSession()->pageTextContains($settings['success']);
    $this->assertNotAgreementPage($agreement);
  }

}
