<?php

/**
 * @file
 * Contains \Drupal\gnu_terry_pratchett\Tests\Routing\ExceptionHandlingTest.
 */

namespace Drupal\gnu_terry_pratchett\Tests\Headers;

use Drupal\Component\Utility\Html;
use Drupal\simpletest\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the exception handling for various cases.
 *
 * @group Routing
 */
class XClacksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'gnu_terry_pratchett'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests the exception handling for json and 403 status code.
   */
  public function testXClacksHeaders() {
    $request = Request::create('/');

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request);

    $this->assertEqual($response->headers->get('X-Clacks-Overhead'), 'GNU Terry Pratchett');
  }

}
