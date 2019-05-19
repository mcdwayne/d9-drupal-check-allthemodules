<?php

namespace Drupal\Tests\simple_a_b\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\block_content\Entity\BlockContent;

/**
 * Base class to make running browser tests easier.
 */
abstract class SimpleAbBrowserTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views',
    'block_content',
    'simple_a_b',
    'simple_a_b_blocks',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an article content type that we will use for testing.
    $block = BlockContent::create([
      'info' => 'block 1',
      'type' => 'basic',
      'langcode' => 'en',
    ]);
    $block->save();
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Follows a link by complete name.
   *
   * Will click the first link found with this link text.
   *
   * If the link is discovered and clicked, the test passes. Fail otherwise.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags.
   * @param int $index
   *   (optional) The index number for cases where multiple links have the same
   *   text. Defaults to 0.
   */
  protected function clickLink($label, $index = 0) {
    $label = (string) $label;
    $links = $this->getSession()->getPage()->findAll('named', ['link', $label]);
    $links[$index]->click();
  }

  /**
   * Fills in field (input, textarea, select) with specified locator.
   *
   * @param string $locator
   *   Input id, name or label.
   * @param string $value
   *   Value.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *
   * @see \Behat\Mink\Element\NodeElement::setValue
   */
  public function fillField($locator, $value) {
    $this->getSession()->getPage()->fillField($locator, $value);
  }

  /**
   * Presses button with specified locator.
   *
   * @param string $locator
   *   Button id, value or alt.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function pressButton($locator) {
    $this->getSession()->getPage()->pressButton($locator);
  }

}
