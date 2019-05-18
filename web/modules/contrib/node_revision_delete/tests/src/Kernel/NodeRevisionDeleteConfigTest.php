<?php

namespace Drupal\Tests\node_revision_delete\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node_revision_delete\Traits\NodeRevisionDeleteTestTrait;
use Drupal\Component\Utility\DiffArray;

/**
 * Test the module configurations related to the node_revision_delete service.
 *
 * @group node_revision_delete
 */
class NodeRevisionDeleteConfigTest extends KernelTestBase {

  use NodeRevisionDeleteTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node_revision_delete'];

  /**
   * The configuration file name.
   *
   * @var string
   */
  protected $configurationFileName;

  /**
   * The editable configuration file.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $editableConfigurationFile;

  /**
   * A test track array.
   *
   * @var array
   */
  protected $testTrackArray;

  /**
   * The node_revision_delete service.
   *
   * @var Drupal\node_revision_delete\NodeRevisionDeleteInterface
   */
  protected $nodeRevisionDelete;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->configurationFileName = 'node_revision_delete.settings';
    // Installing the configuration file.
    $this->installConfig(self::$modules);

    // Getting the node revision delete service.
    $this->nodeRevisionDelete = $this->container->get('node_revision_delete');

    // Setting values for test.
    $this->testTrackArray = $this->getNodeRevisionDeleteTrackArray();

    // Getting the editable configuration.
    $this->editableConfigurationFile = $this->container->get('config.factory')->getEditable($this->configurationFileName);
  }

  /**
   * Tests the NodeRevisionDelete::updateTimeMaxNumberConfig method.
   */
  public function testUpdateTimeMaxNumberConfig() {
    // The new values.
    $minimum_age_to_delete = [5, 3, 1];
    $when_to_delete = [10, 8, 4];

    // Getting the total of tests.
    $count_minimum_age_to_delete = count($minimum_age_to_delete);

    for ($i = 0; $i < $count_minimum_age_to_delete; $i++) {
      // Saving the values.
      $this->editableConfigurationFile->set('node_revision_delete_track', $this->testTrackArray)->save();

      // Updating the values with the updateTimeMaxNumberConfig method.
      $this->nodeRevisionDelete->updateTimeMaxNumberConfig('minimum_age_to_delete', $minimum_age_to_delete[$i]);
      $this->nodeRevisionDelete->updateTimeMaxNumberConfig('when_to_delete', $when_to_delete[$i]);

      // Getting the node_revision_delete_track variable.
      $node_revision_delete_track = $this->editableConfigurationFile->get('node_revision_delete_track');

      // Asserting the values.
      foreach ($node_revision_delete_track as $content_type_info) {
        $this->assertLessThanOrEqual($minimum_age_to_delete[$i], $content_type_info['minimum_age_to_delete']);
        $this->assertLessThanOrEqual($when_to_delete[$i], $content_type_info['when_to_delete']);
      }
    }
  }

  /**
   * Tests the NodeRevisionDelete::saveContentTypeConfig method.
   */
  public function testSaveContentTypeConfig() {

    foreach ($this->testTrackArray as $content_type => $content_type_info) {
      // Creating an array to save in the config.
      $values_without_save_element = $this->testTrackArray;
      unset($values_without_save_element[$content_type]);

      // Saving the array to have values in the config.
      $this->editableConfigurationFile->set('node_revision_delete_track', $values_without_save_element)->save();

      // Saving the configuration for a content type.
      $this->nodeRevisionDelete->saveContentTypeConfig($content_type, $content_type_info['minimum_revisions_to_keep'], $content_type_info['minimum_age_to_delete'], $content_type_info['when_to_delete']);

      // Getting the node_revision_delete_track variable.
      $node_revision_delete_track = $this->editableConfigurationFile->get('node_revision_delete_track');

      // Asserting.
      $this->assertEquals($this->testTrackArray, $node_revision_delete_track);
    }
  }

  /**
   * Tests the NodeRevisionDelete::deleteContentTypeConfig method.
   */
  public function testDeleteContentTypeConfig() {

    foreach ($this->testTrackArray as $content_type => $content_type_info) {
      // Saving the values.
      $this->editableConfigurationFile->set('node_revision_delete_track', $this->testTrackArray)->save();

      // Deleting the configuration for a content type.
      $this->nodeRevisionDelete->deleteContentTypeConfig($content_type);

      // Getting the node_revision_delete_track variable.
      $node_revision_delete_track = $this->editableConfigurationFile->get('node_revision_delete_track');

      // Getting the difference between arrays.
      $difference = DiffArray::diffAssocRecursive($this->testTrackArray, $node_revision_delete_track);
      // Asserting.
      $this->assertEquals($this->testTrackArray[$content_type], $difference[$content_type]);
    }
  }

}
