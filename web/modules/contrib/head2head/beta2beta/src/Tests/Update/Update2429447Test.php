<?php

/**
 * Contains \Drupal\beta2beta\Tests\Update\Update2429447Test.
 */

namespace Drupal\beta2beta\Tests\Update;

/**
 * Test the update path for issue #2429447 (use field data tables in views).
 *
 * @group beta2beta
 */
class Update2429447Test extends Beta2BetaUpdateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $startingBeta = 7;

  /**
   * Turn off strict config schema checking.
   *
   * This has to be turned off since there are multiple update hooks that update
   * views. Since only the final view save will be compliant with the current
   * schema, an exception would be thrown on the first view to be saved if this
   * were left on.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   *
   * Load the starting database that contains a view with a taxonomy filter.
   */
  protected function setDatabaseDumpFiles() {
    $file = __DIR__ . '/../../../tests/fixtures/drupal-8.2429447.standard.beta7.php.gz';
    if (!file_exists($file)) {
      throw new \RuntimeException(SafeMarkup::format('Database dump file @file not found', ['@file' => $file]));
    }
    // This database should be the very first to be loaded.
    array_unshift($this->databaseDumpFiles, $file);
    $this->databaseDumpFiles = array_unique($this->databaseDumpFiles);

    // Enable Head2Head and Beta2Beta.
    $this->databaseDumpFiles[] = __DIR__ . '/../../../tests/fixtures/drupal-8.enable-h2h.php';
  }

  /**
   * Test that plugins are properly updated to use field data table.
   */
  public function testUpdate2429447() {
    // Check the view plugin is in the expected starting state.
    $config_factory = \Drupal::configFactory();
    $view = $config_factory->getEditable('views.view.content');
    $this->assertIdentical($view->getRawData()['display']['default']['display_options']['filters']['term_node_tid_depth']['table'], 'node');

    $this->runUpdates();

    $config_factory = \Drupal::configFactory();
    $view = $config_factory->getEditable('views.view.content');
    $this->assertIdentical($view->getRawData()['display']['default']['display_options']['filters']['term_node_tid_depth']['table'], 'node_field_data');
  }

}
