<?php

namespace Drupal\Tests\feeds_tamper\FunctionalJavascript;

/**
 * Tests adding/editing/removing tamper plugins using the UI.
 *
 * @group feeds_tamper
 */
class UiCrudTest extends FeedsTamperJavascriptTestBase {

  /**
   * A feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * The url to the tamper listing page.
   *
   * @var string
   */
  protected $url;

  /**
   * The manager for FeedTypeTamperMeta instances.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperManager
   */
  protected $feedTypeTamperManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add body field.
    node_add_body_field($this->nodeType);

    // Add a feed type with mapping to body.
    $this->feedType = $this->createFeedType([
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'body',
          'map' => [
            'summary' => 'description',
            'value' => 'content',
          ],
        ],
      ]),
    ]);

    $this->url = $this->feedType->toUrl('tamper');

    $this->feedTypeTamperManager = \Drupal::service('feeds_tamper.feed_type_tamper_manager');
  }

  /**
   * Tests adding a Tamper plugin using the UI using javascript.
   */
  public function testAddTamperInstance() {
    // Go to the tamper listing.
    $this->drupalGet($this->url);

    // Click link for adding a tamper plugin to the source 'description'.
    $this->getSession()
      ->getPage()
      ->find('css', '#edit-description-add-link')
      ->click();

    // Select plugin and wait for config form to show up.
    $this->getSession()->getPage()->selectFieldOption('tamper_id', 'trim');
    $this->assertSession()->waitForElementVisible('css', '#plugin-config');

    // Configure plugin.
    $edit = [
      'plugin_configuration[side]' => 'ltrim',
    ];
    $this->submitForm($edit, 'Submit');

    // And assert that the tamper plugin was added.
    $this->feedType = $this->reloadEntity($this->feedType);
    $plugin_collection = $this->feedTypeTamperManager
      ->getTamperMeta($this->feedType, TRUE)
      ->getTampers();
    $this->assertCount(1, $plugin_collection);

    $tamper = $plugin_collection->getIterator()->current();
    $this->assertEquals('trim', $tamper->getPluginId());
    $this->assertEquals('ltrim', $tamper->getSetting('side'));
    $this->assertEquals('description', $tamper->getSetting('source'));

    // Assert that no PHP errors were generated.
    $this->assertNoPhpErrorsInLog();
  }

}
