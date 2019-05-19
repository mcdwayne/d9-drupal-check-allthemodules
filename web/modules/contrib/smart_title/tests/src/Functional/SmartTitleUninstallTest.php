<?php

namespace Drupal\Tests\smart_title\Functional;

/**
 * Tests the module's title hide functionality.
 *
 * @group smart_title
 */
class SmartTitleUninstallTest extends SmartTitleBrowserTestBase {

  /**
   * Tests that Smart Title related things are wiped out from display entity.
   */
  public function testSmartTitleDisplayCleanup() {
    // Visible Smart Title for default display.
    \Drupal::entityTypeManager()->getStorage('entity_view_display')
      ->load('node.test_page.default')
      ->setThirdPartySetting('smart_title', 'enabled', TRUE)
      ->setThirdPartySetting('smart_title', 'settings', [
        'smart_title__tag' => 'h2',
        'smart_title__classes' => ['node__title'],
        'smart_title__link' => TRUE,
      ])
      ->setComponent('smart_title')
      ->trustData()
      ->save();

    // Hidden Smart Title for teaser display.
    \Drupal::entityTypeManager()->getStorage('entity_view_display')
      ->load('node.test_page.teaser')
      ->setThirdPartySetting('smart_title', 'enabled', FALSE)
      ->removeComponent('smart_title')
      ->trustData()
      ->save();

    $this->container->get('module_installer')->uninstall(['smart_title']);

    foreach (['default', 'teaser'] as $view_mode) {
      $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')
        ->load('node.test_page.' . $view_mode);
      $smart_title_settings = $display->getThirdPartySettings('smart_title');
      $active_smart_title = $display->getComponent('smart_title');
      $this->assertEquals([], $smart_title_settings);
      $this->assertTrue($active_smart_title === NULL);
    }
  }

}
