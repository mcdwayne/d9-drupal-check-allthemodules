<?php

namespace Drupal\Tests\ip2country\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that ip2country settings are properly updated during database updates.
 *
 * @group comment
 */
class Ip2CountryUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.ip2country-update-batch-size-2187895.php',
    ];
  }

  /**
   * Tests ip2country_update_8101().
   *
   * @see ip2country_update_8101()
   */
  public function testHookUpdate8101() {
    // Load the 'ip2country.settings' configuration settings, then check
    // that it does not contain the 'batch_size' setting before the update.
    $config = $this->config('ip2country.settings');
    $this->assertNull($config->get('batch_size'));

    // Run updates.
    $this->runUpdates();

    // Check that 'ip2country.settings' configuration setting 'batch_size'
    // has the default value of 200 after the update.
    $config = $this->config('ip2country.settings');
    $this->assertSame(200, $config->get('batch_size'));
  }

}
