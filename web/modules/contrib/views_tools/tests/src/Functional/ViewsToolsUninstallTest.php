<?php

namespace Drupal\Tests\views_tools\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that uninstalling views_tools does not remove other module's views_tools.
 *
 * @group views_tools
 */
class ViewsToolsUninstallTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['views_tools'];

  /**
   * Tests views_tools uninstall.
   */
  public function testViewsToolsUninstall() {
    \Drupal::service('module_installer')->uninstall(['views_tools']);
    $this->drupalGet('/admin/structure/views-tools');
    //$this->assertResponse(404);
    $this->assertSession()->statusCodeEquals(404);
  }

}
