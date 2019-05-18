<?php

namespace Drupal\Tests\consistent_uid\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests blockchain.
 *
 * @group blockchain
 * @group legacy
 */
class ConsistentUidFunctionalTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'consistent_uid',
    'consistent_uid_test'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    parent::setUp();
  }

  /**
   * Tests async user creation.
   */
  public function testAsyncUserCreation() {

    $users = User::loadMultiple();
    $this->assertCount(2, $users, "User count is 2");
    $i = 0;
    $usersCount = 100;
    $requests = [];
    while ($i < $usersCount) {
      $requests[] = $this->drupalGetAsync('/consistent_uid_test/user_create_handler/user-' . $i . '/' . sha1('keep_calm_dude'));
      $i++;
    }
    \GuzzleHttp\Promise\all($requests)->wait();
    $users = User::loadMultiple();
    $this->assertCount($usersCount + 2, $users, "User count is {$usersCount}");
    $i = 0;
    foreach ($users as $user) {
      $this->assertEquals($i, $user->id(), 'Uid consistence ensured.');
      $i++;
    }
  }

  /**
   * Retrieves a Drupal path or an absolute path.
   *
   * @param string|\Drupal\Core\Url $path
   *   Drupal path or URL to load into Mink controlled browser.
   * @param array $options
   *   (optional) Options to be forwarded to the url generator.
   * @param string[] $headers
   *   An array containing additional HTTP request headers, the array keys are
   *   the header names and the array values the header values. This is useful
   *   to set for example the "Accept-Language" header for requesting the page
   *   in a different language. Note that not all headers are supported, for
   *   example the "Accept" header is always overridden by the browser. For
   *   testing REST APIs it is recommended to obtain a separate HTTP client
   *   using getHttpClient() and performing requests that way.
   *
   * @see \Drupal\Tests\BrowserTestBase::getHttpClient()
   *
   *
   * @return \GuzzleHttp\Promise\PromiseInterface
   *   Promise.
   */
  protected function drupalGetAsync($path, array $options = [], array $headers = []) {

    $options['absolute'] = TRUE;
    $url = $this->buildUrl($path, $options);

    return $this->getHttpClient()->requestAsync('GET', $url);
  }

}
