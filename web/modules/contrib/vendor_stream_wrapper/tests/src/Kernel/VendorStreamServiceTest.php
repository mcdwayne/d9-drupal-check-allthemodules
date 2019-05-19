<?php

namespace Drupal\Tests\vendor_stream_wrapper\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\vendor_stream_wrapper\Service\VendorStreamWrapperService
 *
 * @group vendor_stream_wrapper
 */
class VendorStreamWrapperServiceTest extends KernelTestBase {

  protected static $modules = ['vendor_stream_wrapper'];

  /**
   * Tests a path output from createFromUri().
   *
   * @covers::creatUrlFromUri
   */
  public function testCreatUrlFromUri() {

    $service = $this->container->get('vendor_stream_wrapper.service');
    $path = $service->creatUrlFromUri('vendor://aceme/anvil/heavy.png');
    $this->assertEquals('http://localhost/vendor_files/aceme/anvil/heavy.png', $path);
  }

}
