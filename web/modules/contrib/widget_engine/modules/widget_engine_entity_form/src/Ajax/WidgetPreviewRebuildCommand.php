<?php

namespace Drupal\widget_engine_entity_form\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command to rebuild preview image for widget.
 */
class WidgetPreviewRebuildCommand implements CommandInterface {

  /**
   * A unique identifier.
   *
   * @var int
   */
  protected $wid;

  /**
   * Constructs a widget id.
   *
   * @param int $wid
   *   Widget ID.
   */
  public function __construct($wid) {
    $this->wid = $wid;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface::render().
   */
  public function render() {
    return [
      'command' => 'widgetPreviewImageRebuild',
      'wid' => $this->wid,
    ];
  }

}
