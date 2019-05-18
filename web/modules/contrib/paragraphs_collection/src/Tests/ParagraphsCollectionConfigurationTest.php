<?php

namespace Drupal\paragraphs_collection\Tests;

use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests configuration handling within Paragraphs Collection.
 *
 * @group paragraphs_collection
 * @requires module paragraphs
 */
class ParagraphsCollectionConfigurationTest extends ParagraphsExperimentalTestBase {

  /**
   * Tests if reinstalling module delete configuration files.
   */
  public function testReinstallingModule(){
    \Drupal::service('module_installer')->install(['paragraphs_collection']);
    \Drupal::service('module_installer')->uninstall(['paragraphs_collection']);
    \Drupal::service('module_installer')->install(['paragraphs_collection']);
  }

}
