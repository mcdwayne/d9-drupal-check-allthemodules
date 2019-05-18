<?php

namespace Drupal\akismet\Tests;

/**
 * Tests that local fake Akismet server responses match expectations.
 * @group akismet
 */
class ResponseLocalTest extends ResponseTest {
  protected $useLocal = TRUE;
}
