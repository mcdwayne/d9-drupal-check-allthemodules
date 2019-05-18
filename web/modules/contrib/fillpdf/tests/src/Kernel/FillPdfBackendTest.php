<?php

namespace Drupal\Tests\fillpdf\Kernel;

/**
 * Tests that backend-related functions work.
 *
 * @group fillpdf
 */
class FillPdfBackendTest extends FillPdfKernelTestBase {

  public function testTestBackend() {
    $backend_manager = $this->container->get('plugin.manager.fillpdf_backend');
    $test_backend = $backend_manager->createInstance('test');
    self::assertTrue(is_a($test_backend, 'Drupal\fillpdf_test\Plugin\FillPdfBackend\TestFillPdfBackend'), 'Test FillPDF Backend was successfully instantiated.');
  }

}
