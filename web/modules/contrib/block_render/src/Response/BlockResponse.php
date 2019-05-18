<?php
/**
 * @file
 * Contains Drupal\block_render\Response\BlockResponse.
 */

namespace Drupal\block_render\Response;

use Drupal\block_render\Content\RenderedContentInterface;
use Drupal\block_render\Immutable;

/**
 * The asset response data.
 */
final class BlockResponse extends Immutable implements BlockResponseInterface {

  /**
   * Assets.
   *
   * @var \Drupal\block_render\Response\AssetResponseInterface
   */
  protected $assets;

  /**
   * Content.
   *
   * @var \Drupal\block_render\Content\RenderedContentInterface
   */
  protected $content;

  /**
   * Create the Block Response object.
   *
   * @param \Drupal\block_render\Response\AssetResponseInterface $assets
   *   An asset response object.
   * @param \Drupal\block_render\Content\RenderedContentInterface $content
   *   A rendered content object.
   */
  public function __construct(AssetResponseInterface $assets, RenderedContentInterface $content) {
    $this->assets = $assets;
    $this->content = $content;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssets() {
    return $this->assets;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return $this->content;
  }

}
