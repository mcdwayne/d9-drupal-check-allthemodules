<?php

namespace Drupal\paragraphs_collection\Tests;

use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests the invalidation of caches in Paragraphs Collection.
 *
 * @group paragraphs_collection
 */
class ParagraphsCollectionCacheTest extends ParagraphsExperimentalTestBase {

  /**
   * Tests if affected caches are invalidated upon installation of new modules.
   */
  public function testCacheUpdatesForNewModules(){
    $this->loginAsAdmin([
      'administer paragraphs types',
    ]);

    // Install Paragraphs Collection which has no grid layouts or styles.
    \Drupal::service('module_installer')->install(['paragraphs_collection']);

    // Check that no styles are available.
    $this->drupalGet('admin/reports/paragraphs_collection/styles');
    $tds = $this->xpath('//table[contains(@class, :class)]//td', [
      ':class' => 'paragraphs-collection-overview-table',
    ]);
    $this->assertTrue(!isset($tds[0]), 'No styles are available.');

    // Check that no grid layouts are available.
    $this->drupalGet('admin/reports/paragraphs_collection/layouts');
    $tds = $this->xpath('//table[contains(@class, :class)]//td', [
      ':class' => 'paragraphs-collection-overview-table',
    ]);
    $this->assertTrue(!isset($tds[0]), 'No grid layouts are available.');

    // Install a module with new grid layouts and styles styles.
    \Drupal::service('module_installer')->install(['paragraphs_collection_test']);

    // Check that styles are now available.
    $this->drupalGet('admin/reports/paragraphs_collection/styles');
    $tds = $this->xpath('//table[contains(@class, :class)]//td', [
      ':class' => 'paragraphs-collection-overview-table',
    ]);
    $this->assertTrue(isset($tds[0]), 'Styles are now available.');

    // Check that grid layouts are now available.
    $this->drupalGet('admin/reports/paragraphs_collection/layouts');
    $tds = $this->xpath('//table[contains(@class, :class)]//td', [
      ':class' => 'paragraphs-collection-overview-table',
    ]);
    $this->assertTrue(isset($tds[0]), 'Grid layouts  are now available.');
  }

}
