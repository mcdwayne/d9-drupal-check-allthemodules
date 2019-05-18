<?php

namespace Drupal\search_api_view_modes\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;
use Drupal\search_api\Plugin\views\field\SearchApiFieldTrait;

/**
 * Provides a default handler for rendered entity view modes in Search API Views.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_api_view_mode")
 */
class SearchApiViewMode extends FieldPluginBase implements MultiItemsFieldHandlerInterface {

  use SearchApiFieldTrait;

  /**
   * Overrides render_item to return our markup unescaped.
   * @param $count
   * @param $item
   * @return string
   */
  public function render_item($count, $item) {
    return check_markup($item['value'], 'full_html');
  }

}
