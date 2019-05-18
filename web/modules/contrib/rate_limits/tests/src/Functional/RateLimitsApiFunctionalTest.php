<?php

namespace Drupal\Tests\rate_limits\Functional;

use Drupal\rate_limits\Entity\JsonapiResourceConfig;
use Drupal\rate_limits\Entity\RateLimitConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * The test class for the main functionality.
 *
 * @group rate_limits
 */
class RateLimitsApiFunctionalTest extends BrowserTestBase {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminAccount;

  public static $modules = [
    'rate_limits',
    'rate_limits_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $config = RateLimitConfig::create([
      'label' => $this->randomGenerator->name(),
      'id' => $this->randomGenerator->name(),
      'user_flood_route' => [
        'uid_only' => FALSE,
        'ip_limit' => 1,
        'ip_window' => 100,
        'user_limit' => 1000000000,
        'user_window' => 1,
      ],
      'user_flood_global' => [
        'uid_only' => FALSE,
        'ip_limit' => 1000000000,
        'ip_window' => 1,
        'user_limit' => 1000000000,
        'user_window' => 1,
      ],
      'tags' => ['first_tag', 'second_tag'],
    ]);
    $config->save();
    $config = RateLimitConfig::create([
      'label' => $this->randomGenerator->name(),
      'id' => $this->randomGenerator->name(),
      'user_flood_route' => [
        'uid_only' => FALSE,
        'ip_limit' => 1000000000,
        'ip_window' => 1,
        'user_limit' => 1,
        'user_window' => 100,
      ],
      'user_flood_global' => [
        'uid_only' => FALSE,
        'ip_limit' => 1000000000,
        'ip_window' => 1,
        'user_limit' => 1000000000,
        'user_window' => 1,
      ],
      'tags' => ['third_tag', 'fourth_tag'],
    ]);
    $config->save();
    $config = RateLimitConfig::create([
      'label' => $this->randomGenerator->name(),
      'id' => $this->randomGenerator->name(),
      'user_flood_route' => [
        'uid_only' => FALSE,
        'ip_limit' => 1000000000,
        'ip_window' => 1,
        'user_limit' => 1000000000,
        'user_window' => 1,
      ],
      'user_flood_global' => [
        'uid_only' => TRUE,
        'ip_limit' => 2,
        'ip_window' => 100,
        'user_limit' => 1000000000,
        'user_window' => 1,
      ],
      'tags' => ['fifth_tag'],
    ]);
    $config->save();

    $this->account = $this->createUser(['access content']);
    $this->adminAccount = $this->createUser([
      'access content',
      'skip rate limit checks',
    ]);
    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), ['access content']);
  }

  /**
   * Test the GET method.
   */
  public function testRateLimits() {
    // This tests the IP limits for the per-route limits.
    $this->drupalGet('/rate-limits/test1');
    $this->assertSession()->statusCodeEquals(204);
    $this->drupalGet('/rate-limits/test1');
    $this->assertSession()->statusCodeEquals(429);

    // This tests the user limits for the per-route limits with anonymous.
    $this->drupalGet('/rate-limits/test2');
    $this->assertSession()->statusCodeEquals(204);
    $this->drupalGet('/rate-limits/test2');
    // The per user-limits is correctly ignored.
    $this->assertSession()->statusCodeEquals(204);

    // This tests the user limits for the per-route limits with authenticated.
    $this->drupalLogin($this->account);
    $this->drupalGet('/rate-limits/test2');
    $this->assertSession()->statusCodeEquals(204);
    $this->drupalGet('/rate-limits/test2');
    $this->assertSession()->statusCodeEquals(429);

    // Test the global limits. These 2 routes share a tag that is configured to
    // support 2 hits.
    $this->drupalGet('/rate-limits/test3');
    $this->assertSession()->statusCodeEquals(204);
    $this->drupalGet('/rate-limits/test4');
    $this->assertSession()->statusCodeEquals(204);
    $this->drupalGet('/rate-limits/test4');
    $this->assertSession()->statusCodeEquals(429);
  }

}
