<?php

namespace Drupal\Tests\social_auth_mastodon\Functional;

use Drupal\Tests\social_auth\Functional\SocialAuthTestBase;

/**
 * Test that path to authentication route exists in Social Auth Login block.
 *
 * @group social_auth
 *
 * @ingroup social_auth_mastodon
 */
class SocialAuthMastodonLoginBlockTest extends SocialAuthTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'social_auth_mastodon'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->provider = 'mastodon';
  }

  /**
   * Test that the path is included in the login block.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testLinkExistsInBlock() {
    $this->checkLinkToProviderExists();
  }

}
