<?php

namespace Drupal\Tests\formatter_suggest\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * A test for Twig extension.
 *
 * @group newcity_twig
 */
class FormatterSuggestTest extends BrowserTestBase {

  // ignore schema errors
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'formatter_suggest',
    'formatter_suggest_test',
    'text',
    'node',
  ];

  /**
   * Enables Twig debugging.
   */
  protected function debugOn() {
    // Enable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    if (!$parameters['debug']) {
      $parameters['debug'] = TRUE;
      $this->setContainerParameter('twig.config', $parameters);
      $this->rebuildContainer();
      $this->resetAll();
    }
  }

  /**
   * Disables Twig debugging.
   */
  protected function debugOff() {
    // Disable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    if ($parameters['debug']) {
      $parameters['debug'] = FALSE;
      $this->setContainerParameter('twig.config', $parameters);
      $this->rebuildContainer();
      $this->resetAll();
    }
  }


  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // turn on a theme
    \Drupal::service('theme_handler')->install(['bartik']);

    // add a node
    $node = $this->createNode(['title' => 'Page', 'type' => 'page']);

    // populate the two fields
    $node->set('field_text', 'Text in the regular field');
    $node->set('field_test_custom', 'Text in the custom field');
    $node->save();

    $this->debugOn();
  }

  /**
   * Tests field template suggestion
   */
  public function testFieldTemplate() {
    $this->drupalGet('/node/1');

    $this->assertByXpath('//div[@class = "custom-wrapper"]');
  }

  /**
   * Checks that an element specified by a the xpath exists on the current page.
   */
  public function assertByXpath($xpath) {
    $this->assertSession()->elementExists('xpath', $xpath);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->debugOff();
  }

}
