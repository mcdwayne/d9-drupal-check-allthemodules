<?php

namespace Drupal\amp\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for an amp-sidebar element.
 *
 * Properties:
 * - #id: The id of the sidebar (used by the toggle and close buttons).
 * - #tabindex: The number of the tabindex.
 * - #attributes: Attributes for the container.
 * - #close_attributes: Attributes for the close button.
 *
 * @RenderElement("amp_sidebar")
 */
class AmpSidebar extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme_wrappers' => [
        'amp_sidebar',
      ],
    ];
  }

}
