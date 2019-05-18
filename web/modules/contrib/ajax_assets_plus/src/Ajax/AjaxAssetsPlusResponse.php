<?php

namespace Drupal\ajax_assets_plus\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsTrait;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Extended ajax response.
 *
 * Extends \Drupal\Core\Ajax\AjaxResponse by work with libraries. We could not
 * extend the \Drupal\Core\Ajax\AjaxResponse here, else the unwanted attachments
 * processing would be executed at
 * \Drupal\Core\EventSubscriber\AjaxResponseSubscriber::onResponse().
 */
class AjaxAssetsPlusResponse extends CacheableJsonResponse implements AttachmentsInterface {

  use AttachmentsTrait;

  /**
   * The array of ajax commands.
   *
   * @var array
   */
  protected $commands = [];

  /**
   * The array of libraries.
   *
   * @var string[]
   */
  protected $libraries;

  /**
   * The content.
   *
   * @var string
   */
  protected $payload = [];

  /**
   * Gets content.
   *
   * @return string
   *   The content.
   */
  public function getPayload() {
    return $this->payload;
  }

  /**
   * {@inheritdoc}
   */
  public function setContent($content) {
    if (is_array($content)) {
      $html = $this->getRenderer()->renderRoot($content);
      $assets = AttachedAssets::createFromRenderArray($content);
      $attachments = [
        'library' => $assets->getLibraries(),
        'drupalSettings' => $assets->getSettings(),
      ];

      $cache_metadata = CacheableMetadata::createFromRenderArray($content);
      $this->addCacheableDependency($cache_metadata);

      $attachments = BubbleableMetadata::mergeAttachments($this->getAttachments(), $attachments);
      $this->setAttachments($attachments);
      $this->content = $html;
    }
    else {
      $this->content = $content;
    }
  }

  /**
   * Gets all AJAX commands.
   *
   * @return \Drupal\Core\Ajax\CommandInterface[]
   *   Returns all previously added AJAX commands.
   */
  public function &getCommands() {
    return $this->commands;
  }

  /**
   * Add an AJAX command to the response.
   *
   * @param \Drupal\Core\Ajax\CommandInterface $command
   *   An AJAX command object implementing CommandInterface.
   * @param bool $prepend
   *   A boolean which determines whether the new command should be executed
   *   before previously added commands. Defaults to FALSE.
   *
   * @return \Drupal\ajax_assets_plus\Ajax\AjaxAssetsPlusResponse
   *   The current AjaxAssetsPlusResponse.
   */
  public function addCommand(CommandInterface $command, $prepend = FALSE) {
    if ($prepend) {
      array_unshift($this->commands, $command->render());
    }
    else {
      $this->commands[] = $command->render();
    }
    if ($command instanceof CommandWithAttachedAssetsInterface) {
      $assets = $command->getAttachedAssets();
      $attachments = [
        'library' => $assets->getLibraries(),
        'drupalSettings' => $assets->getSettings(),
      ];
      $attachments = BubbleableMetadata::mergeAttachments($this->getAttachments(), $attachments);
      $this->setAttachments($attachments);
    }

    return $this;
  }

  /**
   * Gets Libraries.
   *
   * @return string[]
   *   The array of libraries.
   */
  public function &getLibraries() {
    return $this->libraries;
  }

  /**
   * Sets Libraries.
   *
   * @param string[] $libraries
   *   The libraries.
   */
  public function setLibraries(array $libraries) {
    $this->libraries = $libraries;
  }

  /**
   * Gets the renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  protected function getRenderer() {
    return \Drupal::service('renderer');
  }

}
