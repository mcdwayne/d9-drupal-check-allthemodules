<?php

namespace Drupal\medium_posts\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Medium Publish Event.
 *
 * @package Drupal\medium_posts\Event
 */
class MediumPublishEvent extends Event {

  /**
   * This event is fired everytime a node content is successfully.
   *
   * Pushed to medium.com.
   *
   * @Event
   */
  const POST_PUSHED = 'medium_posts.post_pushed';

  /**
   * The node entity pushed to Medium.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The medium.com post url for that node.
   *
   * @var string
   */
  protected $url;

  /**
   * Creates a new MediumPublishEvent instance.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node entity pushed to Medium.
   * @param string $url
   *   The medium.com post url for that node.
   */
  public function __construct(NodeInterface $node, $url) {
    $this->node = $node;
    $this->url = $url;
  }

  /**
   * Get url.
   *
   * @return string
   *   The medium.com post url for that node.
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Get node entity.
   *
   * @return \Drupal\node\NodeInterface
   *   The node entity.
   */
  public function getNode() {
    return $this->node;
  }

}
