<?php

/**
 * @file
 * Contains \Drupal\block_page\Event\BlockPageContextEvent.
 */

namespace Drupal\block_page\Event;

use Drupal\block_page\BlockPageInterface;
use Drupal\block_page\Plugin\PageVariantInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a block page for event subscribers.
 */
class BlockPageContextEvent extends Event {

  /**
   * The block page the context is gathered for.
   *
   * @var \Drupal\block_page\BlockPageInterface
   */
  protected $blockPage;

  /**
   * The page variant.
   *
   * @var \Drupal\block_page\Plugin\PageVariantInterface
   */
  protected $pageVariant;

  /**
   * Creates a new BlockPageContextEvent.
   *
   * @param \Drupal\block_page\BlockPageInterface $block_page
   *   The block page.
   * @param \Drupal\block_page\Plugin\PageVariantInterface $page_variant
   *   (optional) The page variant.
   */
  public function __construct(BlockPageInterface $block_page, PageVariantInterface $page_variant = NULL) {
    $this->blockPage = $block_page;
    $this->pageVariant = $page_variant;
  }

  /**
   * Returns the block page for this event.
   *
   * @return \Drupal\block_page\BlockPageInterface
   *   The block page.
   */
  public function getBlockPage() {
    return $this->blockPage;
  }

}
