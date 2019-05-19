<?php

namespace Drupal\Tests\blazy\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;
use Drupal\Tests\blazy\Traits\BlazyCreationTestTrait;

/**
 * Tests the Blazy JavaScript using PhantomJS, or Chromedriver.
 *
 * @group blazy
 */
class BlazyJavaScriptTest extends WebDriverTestBase {

  use BlazyUnitTestTrait;
  use BlazyCreationTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'filter',
    'image',
    'node',
    'text',
    'blazy',
    'blazy_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpVariables();

    $this->entityManager          = $this->container->get('entity.manager');
    $this->entityFieldManager     = $this->container->get('entity_field.manager');
    $this->formatterPluginManager = $this->container->get('plugin.manager.field.formatter');
    $this->blazyAdmin             = $this->container->get('blazy.admin');
    $this->blazyManager           = $this->container->get('blazy.manager');
  }

  /**
   * Test the Blazy element from loading to loaded states.
   */
  public function testFormatterDisplay() {
    $data['settings']['blazy'] = TRUE;
    $data['settings']['ratio'] = '';
    $data['settings']['image_style'] = 'thumbnail';

    $this->setUpContentTypeTest($this->bundle);
    $this->setUpFormatterDisplay($this->bundle, $data);
    $this->setUpContentWithItems($this->bundle);
    $session = $this->getSession();
    $page = $session->getPage();
    $image_path = $this->getImagePath(TRUE);

    $this->drupalGet('node/' . $this->entity->id());

    // Ensures Blazy is not loaded on page load.
    $this->assertSession()->elementNotExists('css', '.b-loaded');

    // Capture the initial page load moment.
    $this->createScreenshot($image_path . '/1_blazy_initial.png');
    $this->assertSession()->elementExists('css', '.b-lazy');

    // Trigger Blazy to load images by scrolling down window.
    $session->executeScript('window.scrollTo(0, document.body.scrollHeight);');

    // Capture the loading moment after scrolling down the window.
    $this->createScreenshot($image_path . '/2_blazy_loading.png');

    // Wait a moment.
    $session->wait(1000);

    // Let's get busy by scrolling up and down window.
    $session->executeScript('window.scrollTo(0, 0);');
    $session->executeScript('window.scrollTo(0, document.body.scrollHeight);');
    $session->executeScript('window.scrollTo(0, 300);');

    // Wait a moment, likely repo bot is slower than local.
    $session->wait(1000);

    // Check what we are at the middle of viewport.
    $this->createScreenshot($image_path . '/3_blazy_progress.png');
    $session->executeScript('window.scrollTo(0, document.body.scrollHeight);');

    // Verifies that the image is there once loaded.
    // @todo works local, but failed on repo.
    $this->assertSession()->waitForElementVisible('css', '.b-loaded', 3000);
    $loaded = $page->find('css', '.b-loaded');
    $this->assertTrue($loaded->isVisible(), '.b-loaded should be visible, bot!');

    // Capture the loaded moment.
    // The screenshots are at sites/default/files/simpletest/blazy.
    $this->createScreenshot($image_path . '/4_blazy_loaded.png');
  }

}
