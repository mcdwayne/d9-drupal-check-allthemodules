<?php
/**
 * @file
 * Contains \Drupal\widget_block\Renderable\WidgetMarkup.
 */

namespace Drupal\widget_block\Renderable;

use Psr\Http\Message\ResponseInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Render\MarkupInterface;
use Drupal\widget_block\Entity\WidgetBlockConfigInterface;
use Drupal\widget_block\Utility\AssetsHelper;

/**
 * Default widget markup implementation.
 */
class WidgetMarkup implements WidgetMarkupInterface {

  /**
   * Unique widget identifier.
   *
   * @var string
   */
  protected $id;

  /**
   * Mode used for inclusion.
   *
   * @var int
   */
  protected $includeMode;

  /**
   * Flag which indicates whether the markup is cacheable.
   *
   * @var bool
   */
  protected $cacheable;

  /**
   * Unix timestamp which represents the created time.
   *
   * @var int
   */
  protected $created;

  /**
   * Unix timestamp which represents the modified time.
   *
   * @var int
   */
  protected $modified;

  /**
   * Unix timestamp which represents the refreshed time.
   *
   * @var int
   */
  protected $refreshed;

  /**
   * Language used to generate the markup.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Markup which represents the content.
   *
   * @var \Drupal\Core\Render\Markup
   */
  protected $content;

  /**
   * An associative array which contains the markup related assets.
   *
   * @var array
   */
  protected $assets;

  /**
   * Create a WidgetMarkup object.
   *
   * @param string $id
   *   Unique widget identifier.
   * @param string $mode
   *   Type of inclusion mode used for the markup.
   * @param string $langcode
   *   Language used to generated the markup.
   * @param \Drupal\Core\Render\MarkupInterface $content
   *   Content the widget block markup.
   * @param array $assets
   *   An associative array which contains the markup assets.
   * @param bool $cacheable
   *   Flag which indicates whether the markup is cacheable.
   * @param int $created
   *   Unix timestamp which represents the created time.
   * @param int $modified
   *   Unix timestamp which represents the modified time.
   * @param int $refreshed
   *   Unix timestamp which represents the refreshed time.
   */
  public function __construct($id, $mode, $langcode, MarkupInterface $content, array $assets, $cacheable, $created, $modified, $refreshed) {
    // Setup object members.
    $this->id = $id;
    $this->includeMode = $mode;
    $this->langcode = $langcode;
    $this->content = $content;
    $this->assets = $assets;
    $this->cacheable = $cacheable;
    $this->created = $created;
    $this->modified = $modified;
    $this->refreshed = $refreshed;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getIncludeMode() { 
    return $this->includeMode;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangCode() { 
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return $this->content;
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
  public function isCacheable() {
    return $this->cacheable;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated() { 
    return $this->created;
  }

  /**
   * {@inheritdoc}
   */
  public function getModified() { 
    return $this->modified;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefreshed() {
    return $this->refreshed;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [
      // Create dependency with Widget Block and Language configuration:
      "config:widget_block.config.{$this->id()}",
      "config:language.entity.{$this->getLangCode()}",
      // Generate Widget Block Markup related tags:
      "widget_block_markup:{$this->id()}-{$this->getLangCode()}",
      "widget_block_markup_id:{$this->id()}",
      "widget_block_markup_language:{$this->getLangCode()}",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Only allow cache invalidation using cache tags.
    return $this->isCacheable() ? Cache::PERMANENT : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    // Create the default renderable array structure for this markup.
    $renderable = [
      '#id' => "{$this->id()}:{$this->getIncludeMode()}:{$this->getLangCode()}",
      '#markup' => $this->getContent(),
      /* @todo Provide more advanced integration support with Drupal 8 and WidgetApi.
      '#attached' => [
        // Include the integration API to allow better Drupal to Widget
        // integration and support.
        'library' => ['widget_block/integration'],
      ],
      */
      '#cache' => [
        'contexts' => $this->getCacheContexts(),
        'tags' => $this->getCacheTags(),
        'max-age' => $this->getCacheMaxAge(),
      ],
    ];

    // Get the markup related assets.
    $assets = $this->getAssets();
    // Apply the assets to the renderable array.
    AssetsHelper::applyAssetsToRenderArray($renderable['#id'], $assets, $renderable);

    return $renderable;
  }

}
