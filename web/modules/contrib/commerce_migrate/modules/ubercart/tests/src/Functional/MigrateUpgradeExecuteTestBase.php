<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Functional;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase as CoreMigrateUpgradeExecuteTestBase;

/**
 * Base class for testing a migration run with the UI.
 */
abstract class MigrateUpgradeExecuteTestBase extends CoreMigrateUpgradeExecuteTestBase {

  /**
   * Executes all steps of migrations upgrade.
   */
  public function testMigrateUpgradeExecute() {
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $session = $this->assertSession();

    $driver = $connection_options['driver'];
    $connection_options['prefix'] = $connection_options['prefix']['default'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    $drivers = drupal_get_database_types();
    $form = $drivers[$driver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    $edit = [
      $driver => $connection_options,
      'source_private_file_path' => $this->getSourceBasePath(),
      'version' => $version,
    ];
    if ($version == 6) {
      $edit['d6_source_base_path'] = $this->getSourceBasePath();
    }
    else {
      $edit['source_base_path'] = $this->getSourceBasePath();
    }
    if (count($drivers) !== 1) {
      $edit['driver'] = $driver;
    }
    $edits = $this->translatePostValues($edit);

    // Start the upgrade process.
    $this->drupalGet('/upgrade');

    $this->drupalPostForm(NULL, [], t('Continue'));
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    $this->drupalPostForm(NULL, $edits, t('Review upgrade'));
    $session->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [], t('I acknowledge I may lose data. Continue anyway.'));
    $session->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [], t('Perform upgrade'));
    $session->pageTextContains(t('Congratulations, you upgraded Drupal!'));
    $this->assertMigrationResults($this->getEntityCounts(), $version);
  }

}
