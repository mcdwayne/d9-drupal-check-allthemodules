<?php

namespace Drupal\Tests\big_pipe_sessionless\Functional;

use Drupal\big_pipe\Render\BigPipe;
use Drupal\Tests\big_pipe\Functional\BigPipeTest;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;

/**
 * Tests BigPipe's sessionless support.
 *
 * Extends \Drupal\big_pipe\Tests\BigPipeTest, to ensure that we also run "core"
 * BigPipe's tests, to guarantee that we don't break it.
 *
 * @group big_pipe_sessionless
 * @covers \Drupal\big_pipe_sessionless\Render\Placeholder\BigPipeSessionlessStrategy
 * @covers \Drupal\big_pipe_sessionless\EventSubscriber\HtmlResponseBigPipeSessionlessSubscriber
 * @covers \Drupal\big_pipe_sessionless\Render\BigPipeSessionless
 * @covers \Drupal\big_pipe_sessionless\PageCache\ResponsePolicy\DenyBigPipeSessionlessResponses
 * @covers \Drupal\big_pipe_sessionless\StackMiddleware\BigPipeSessionlessPageCache
 * @covers \Drupal\big_pipe_sessionless\BigPipeSessionlessServiceProvider
 */
class BigPipeSessionlessTest extends BigPipeTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['big_pipe_sessionless', 'big_pipe_sessionless_test'];

  /**
   * Tests BigPipe-delivered HTML responses for requests with no session.
   *
   * Covers:
   * - \Drupal\big_pipe\EventSubscriber\HtmlResponseBigPipeSubscriber
   * - \Drupal\big_pipe\Render\BigPipe
   * - \Drupal\big_pipe\Render\BigPipe::sendNoJsPlaceholders()
   *
   * @see \Drupal\big_pipe_test\BigPipePlaceholderTestCases
   */
  public function testBigPipeNoSession() {
    $cases = [
      'default' => [
        'configured max-age' => 0,
        'page cache hit cache control' => 'must-revalidate, no-cache, private',
      ],
      'max-age=300' => [
        'configured max-age' => 300,
        'page cache hit cache control' => 'max-age=300, public',
      ],
    ];

    foreach ($cases as $case => $expectations) {
      $this->pass("No-session test case: $case");

      // Simulate production.
      $this->config('system.logging')->set('error_level', ERROR_REPORTING_HIDE)->save();
      $this->config('system.performance')->set('cache.page.max_age', $expectations['configured max-age'])->save();

      $this->assertSessionCookieExists(FALSE);
      $this->assertBigPipeNoJsCookieExists(FALSE);

      $this->pass('First request: Page Cache miss, streamed response by BigPipe', 'Debug');
      $this->drupalGet(Url::fromRoute('big_pipe_sessionless_test'));
      $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'), 'No X-Drupal-Cache header.');
      $this->assertBigPipeResponseHeadersPresent();
      $this->assertNoCacheTag('cache_tag_set_in_lazy_builder');
      $this->assertRaw('<a href="' . base_path() . 'big_pipe_sessionless_test" data-drupal-link-system-path="big_pipe_sessionless_test" class="is-active">This should be marked active</a>');
      $this->assertRaw('<a href="' . base_path() . '" data-drupal-link-system-path="&lt;front&gt;">This should be marked inactive</a>');

      $cases = $this->getTestCases();
      $this->assertBigPipeNoJsPlaceholders([
        $cases['edge_case__invalid_html']->bigPipeNoJsPlaceholder => $cases['edge_case__invalid_html']->embeddedHtmlResponse,
        $cases['html_attribute_value']->bigPipeNoJsPlaceholder => '<form class="big-pipe-test-form" data-drupal-selector="big-pipe-test-form" action="' . base_path() . 'big_pipe_sessionless_test"',
        $cases['html']->bigPipeNoJsPlaceholder => NULL,
        $cases['edge_case__html_non_lazy_builder']->bigPipeNoJsPlaceholder => $cases['edge_case__html_non_lazy_builder']->embeddedHtmlResponse,
        $cases['exception__lazy_builder']->bigPipePlaceholderId => NULL,
        $cases['exception__embedded_response']->bigPipePlaceholderId => NULL,
      ]);

      $this->pass('Verifying there are no BigPipe placeholders & replacements…', 'Debug');
      $this->assertEqual('<none>', $this->drupalGetHeader('BigPipe-Test-Placeholders'));
      $this->pass('Verifying BigPipe start/stop signals are absent…', 'Debug');
      $this->assertNoRaw(BigPipe::START_SIGNAL, 'BigPipe start signal absent.');
      $this->assertNoRaw(BigPipe::STOP_SIGNAL, 'BigPipe stop signal absent.');

      $this->pass('Verifying BigPipe assets are absent…', 'Debug');
      $this->assertTrue(empty($this->getDrupalSettings()), 'drupalSettings and BigPipe asset library absent.');
      $this->assertRaw('</body>', 'Closing body tag present.');

      $this->pass('Repeat request: Page Cache hit, BigPipe not involved', 'Debug');
      $this->drupalGet(Url::fromRoute('big_pipe_sessionless_test'));
      $this->assertIdentical('HIT', $this->drupalGetHeader('X-Drupal-Cache'), 'Page cache hit.');
      $this->assertIdentical($expectations['page cache hit cache control'], $this->drupalGetHeader('Cache-Control'));
      $this->assertFalse($this->drupalGetHeader('Surrogate-Control'), 'No Surrogate-Control header.');
      $this->assertFalse($this->drupalGetHeader('X-Accel-Buffering'), 'No X-Accel-Buffering header.');
      $this->assertCacheTag('cache_tag_set_in_lazy_builder');
      $this->assertRaw('<a href="' . base_path() . 'big_pipe_sessionless_test" data-drupal-link-system-path="big_pipe_sessionless_test" class="is-active">This should be marked active</a>');
      $this->assertRaw('<a href="' . base_path() . '" data-drupal-link-system-path="&lt;front&gt;">This should be marked inactive</a>');

      // Clear the Page Cache. Note that we use a cache tag that exists on this
      // response, despite it being absent from both the Page Cache miss
      // response (because it was streamed) and the Page Cache hit response
      // (because it is identical to the original Page Cache miss response,
      // minus the streaming).
      Cache::invalidateTags(['cache_tag_set_in_lazy_builder']);

      // Simulate development.
      $this->pass('Verifying BigPipe provides useful error output when an error occurs while rendering a placeholder if verbose error logging is enabled.', 'Debug');
      $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
      $this->drupalGet(Url::fromRoute('big_pipe_sessionless_test'));
      // The 'edge_case__html_exception' case throws an exception.
      $this->assertRaw('The website encountered an unexpected error. Please try again later');
      $this->assertRaw('You are not allowed to say llamas are not cool!');
      $this->assertNoRaw('</body>', 'Closing body tag absent: error occurred before then.');
      // The exception is expected. Do not interpret it as a test failure.
      unlink(\Drupal::root() . '/' . $this->siteDirectory . '/error.log');

      // Clear the Page Cache.
      Cache::invalidateTags(['rendered']);
    }
  }

}
