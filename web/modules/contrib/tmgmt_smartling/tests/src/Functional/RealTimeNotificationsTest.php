<?php

namespace Drupal\Tests\tmgmt_smartling\Functional;

/**
 * Real time notifications tests.
 *
 * @group tmgmt_smartling
 */
class RealTimeNotificationsTest extends SmartlingTestBase {

  /**
   * Permission test.
   *
   * Firebase scripts are included to the page if user has permission
   * nad feature is enabled.
   */
  public function testScriptsAreIncludedIfUserHasPermission() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);

      // Without permission scripts are not included.
      $this->loginAsAdmin();
      $output = $this->drupalGet('/admin/tmgmt/translators');
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-app.js') === FALSE);
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-auth.js') === FALSE);
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-database.js') === FALSE);
      $this->assertTrue(strpos($output, '/modules/contrib/tmgmt_smartling/js/notifications.js') === FALSE);
      $this->assertTrue(strpos($output, '/modules/contrib/tmgmt_smartling/css/notifications.css') === FALSE);
      $this->drupalLogout();

      // With permission scripts are included.
      $this->loginAsAdmin([
        'see smartling messages',
      ]);
      $output = $this->drupalGet('/admin/tmgmt/translators');
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-app.js') !== FALSE);
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-auth.js') !== FALSE);
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-database.js') !== FALSE);
      $this->assertTrue(strpos($output, '/modules/contrib/tmgmt_smartling/js/notifications.js') !== FALSE);
      $this->assertTrue(strpos($output, '/modules/contrib/tmgmt_smartling/css/notifications.css') !== FALSE);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Disabled feature test.
   *
   * Firebase scripts are not included to the page if user has permission
   * and feature is disabled.
   */
  public function testScriptsAreNotIncludedIfUserHasPermissionAndFeatureIsDisabled() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->smartlingPluginProviderSettings['settings[enable_notifications]'] = FALSE;
      $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);

      // With permission but with disabled feature scripts are not included.
      $this->loginAsAdmin([
        'see smartling messages',
      ]);
      $output = $this->drupalGet('/admin/tmgmt/translators');
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-app.js') === FALSE);
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-auth.js') === FALSE);
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-database.js') === FALSE);
      $this->assertTrue(strpos($output, '/modules/contrib/tmgmt_smartling/js/notifications.js') === FALSE);
      $this->assertTrue(strpos($output, '/modules/contrib/tmgmt_smartling/css/notifications.css') === FALSE);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * At least one provider has enabled feature test.
   *
   * Firebase scripts are included to the page if user has permission
   * and at least one provider has disabled feature.
   */
  public function testScriptsAreIncludedIfUserHasPermissionAndAtLeastOneProviderHasDisabledFeature() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $this->smartlingPluginProviderSettings['settings[enable_notifications]'] = FALSE;
      $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);

      $this->smartlingPluginProviderSettings['settings[enable_notifications]'] = TRUE;
      $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);

      $this->loginAsAdmin([
        'see smartling messages',
      ]);
      $output = $this->drupalGet('/admin/tmgmt/translators');
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-app.js') !== FALSE);
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-auth.js') !== FALSE);
      $this->assertTrue(strpos($output, 'https://www.gstatic.com/firebasejs/5.2.0/firebase-database.js') !== FALSE);
      $this->assertTrue(strpos($output, '/modules/contrib/tmgmt_smartling/js/notifications.js') !== FALSE);
      $this->assertTrue(strpos($output, '/modules/contrib/tmgmt_smartling/css/notifications.css') !== FALSE);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

}
