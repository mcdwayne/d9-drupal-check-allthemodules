<?php

namespace Drupal\Tests\big_pipe_sessionless\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;

/**
 * Sessionless BigPipe regression tests.
 *
 * Note: this cannot extend BigPipeRegressionTest because that does not allow
 * for HEAD requests.
 *
 * @group big_pipe_sessionless
 * @see \Drupal\Tests\big_pipe\FunctionalJavascript\BigPipeRegressionTest
 */
class BigPipeSessionlessRegressionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'big_pipe',
    'big_pipe_sessionless',
  ];

  /**
   * HEAD requests must not result in 500 responses.
   *
   * @see https://www.drupal.org/project/big_pipe_sessionless/issues/2942484
   */
  public function testHeadRequestForIssue2942484() {
    /** @var \GuzzleHttp\ClientInterface $client */
    $client = $this->getSession()->getDriver()->getClient()->getClient();

    Cache::invalidateTags(['rendered']);
    $response = $client->request('HEAD', $this->getAbsoluteUrl('/'));
    $this->assertSame(200, $response->getStatusCode());
    $this->assertFalse($response->hasHeader('Surrogate-Control'));
    $this->assertFalse($response->hasHeader('X-Accel-Buffering'));
    $this->assertSame(['MISS'], $response->getHeader('X-Drupal-Cache'));
    $this->assertSame(['MISS'], $response->getHeader('X-Drupal-Dynamic-Cache'));
  }

}
