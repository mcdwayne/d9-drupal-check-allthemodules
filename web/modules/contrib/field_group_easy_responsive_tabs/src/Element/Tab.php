<?php

namespace Drupal\field_group_easy_responsive_tabs\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for tabs.
 *
 * @FormElement("field_group_easy_responsive_tab")
 */
class Tab extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#type'           => 'field_group_easy_responsive_tab',
      '#theme_wrappers' => array('field_group_easy_responsive_tab'),
    );
  }

}
