<?php

namespace Drupal\menu_link_content_visibility\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;

/**
 * @FieldType(
 *   label = @Translation("Menu link visibility"),
 *   id = "menu_link_content_visibility",
 *   default_widget = "menu_link_content_visibility",
 *   no_ui = TRUE
 * )
 */
class MenuLinkContentVisibilityItem extends StringLongItem {

}
