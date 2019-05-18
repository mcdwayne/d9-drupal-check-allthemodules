<?php
/**
 * @file
 * Contains \Drupal\collect\Tests\ProcessingWebTest.
 */

namespace Drupal\collect\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests processing with Collect.
 *
 * @group collect
 */
class ProcessingWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('collect', 'collect_test', 'block');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a user.
    $this->user = $this->drupalCreateUser(['administer collect']);

    // Place tasks block.
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the processing form.
   *
   * The execution of the processors is tested in ProcessingTest.
   */
  public function testProcessingUi() {
    // Login and access the processing form for the test model.
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/structure/collect/model');
    // Find the operations link.
    $this->assertLink(t('Manage processing'));
    $this->clickLink('Edit');
    // Find and click the local task.
    $this->clickLink(t('Processing'));
    $this->assertUrl('admin/structure/collect/model/manage/test_greeting/processing');
    $this->assertText(t('There are no processors yet.'));
    $this->assertText(t('Select a processor plugin to add it to the model.'));
    $this->assertFieldByXPath('//select[@name="processor_add_select"]//option[@selected="selected"]');
    // The entity delete button should be hidden.
    $this->assertNoLink(t('Delete'));

    // Add processors.
    $this->drupalPostAjaxForm(NULL, ['processor_add_select' => 'context_collector'], ['op' => t('Add')]);
    $this->assertText(t('Collects the processing context for tests.'));
    $this->drupalPostAjaxForm(NULL, ['processor_add_select' => 'spicer'], ['op' => t('Add')]);
    $this->assertText(t('Spice up your life.'));
    list($context_collector_uuid, $spicer_uuid) = $this->getProcessorKeys();
    // Swap order to test weight handling.
    $this->drupalPostForm(NULL, [
      'processors[' . $context_collector_uuid . '][weight]' => 1,
      'processors[' . $spicer_uuid . '][weight]' => 0,
      'processors[' . $spicer_uuid . '][settings][spice]' => 'ginger',
    ], t('Save'));

    // Assert resulting state.
    $this->assertEqual(2, count($this->xpath('//main//tbody/tr')));
    $this->assertFieldByName('processors[' . $spicer_uuid . '][weight]', '0');
    $this->assertFieldByName('processors[' . $context_collector_uuid . '][weight]', '1');
    // First row.
    $this->assertEqual(t('Spicer'), $this->xpath('//main//tbody/tr[1]/td[2]')[0]);
    $this->assertEqual(t('Spice up your life.'), $this->xpath('//main//tbody/tr[1]/td[4]')[0]);
    $this->assertFieldByName('processors[' . $spicer_uuid . '][settings][spice]', '');
    // Second row.
    $this->assertEqual(t('Context collector'), $this->xpath('//main//tbody/tr[2]/td[2]')[0]);
    $this->assertEqual(t('Collects the processing context for tests.'), $this->xpath('//main//tbody/tr[2]/td[4]')[0]);

    // Test sub form handlers.
    $this->drupalPostForm(NULL, [
      'processors[' . $spicer_uuid . '][settings][spice]' => 'cumin',
    ], t('Save'));
    $this->assertText(t('The spice must be one of pepper, chili or ginger.'));
    $this->assertNoText(t('The processing has been saved.'));
    $this->drupalPostForm(NULL, [
      'processors[' . $spicer_uuid . '][settings][spice]' => 'ginger',
    ], t('Save'));
    $this->assertFieldByName('processors[' . $spicer_uuid . '][settings][spice]', 'ginger');
    $this->assertText(t('The processing has been saved.'));

    // Test remove button.
    $this->drupalPostAjaxForm(NULL, [], ['remove_' . $spicer_uuid => t('Remove')]);
    $this->assertNoText(t('Spice up your life.'));
    $this->assertText(t('Collects the processing context for tests.'));
    $this->drupalPostAjaxForm(NULL, [], ['remove_' . $context_collector_uuid => t('Remove')]);
    $this->assertNoText(t('Collects the processing context for tests.'));
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText(t('The processing has been saved.'));
    $this->assertText(t('There are no processors yet.'));
  }

  /**
   * Finds the processor keys in the processing form raw content.
   *
   * @return string[]
   *   The processor keys (uuids), in order of weight.
   */
  protected function getProcessorKeys() {
    preg_match_all('/processors\[([^\]]+)\]/', $this->getRawContent(), $matches);
    return array_unique($matches[1]);
  }

}
