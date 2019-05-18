<?php

namespace Drupal\remodal\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Ajax\CommandWithAttachedAssetsInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsTrait;

/**
 * Defines an AJAX command to open certain content in a remodal dialog.
 *
 * @ingroup ajax
 */
class OpenRemodalCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  use CommandWithAttachedAssetsTrait;

  /**
   * The title of the dialog.
   *
   * @var string
   */
  protected $title;

  /**
   * The content for the dialog.
   *
   * Either a render array or an HTML string.
   *
   * @var string|array
   */
  protected $content;

  /**
   * Stores dialog-specific options passed directly to Remodal dialogs.
   *
   * Any Remodal option can be used. See https://github.com/VodkaBears/Remodal.
   *
   * @var array
   */
  protected $dialogOptions;

  /**
   * Constructs an OpenRemodalCommand object.
   *
   * @param string $title
   *   The title of the dialog.
   * @param string|array $content
   *   The content that will be placed in the dialog, either a render array
   *   or an HTML string.
   * @param array $dialog_options
   *   TBD.
   */
  public function __construct($title, $content, array $dialog_options) {
    $title = PlainTextOutput::renderFromHtml($title);
    $this->title = $title;
    $this->content = $content;
    $this->dialogOptions = $dialog_options;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    $this->content = [
      '#content' => $this->content,
      '#theme' => 'remodal_content_wrapper',
    ];
    return array(
      'command' => 'openRemodal',
      'title' => $this->title,
      'content' => $this->getRenderedContent(),
      'options' => $this->dialogOptions,
    );
  }

}
