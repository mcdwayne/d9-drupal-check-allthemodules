<?php

namespace Drupal\activity\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Link;

/**
 * Field handler to delete the action.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("delete_action")
 */
class DeleteAction extends FieldPluginBase {

  /**
   * Leave empty to avoid a query on this field.
   *
   * @{inheritdoc}
   */
  public function query() {
  }

  /**
   * Define the available options.
   *
   * @return array
   *   Return options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  /**
   * Add link to delete action.
   *
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $id = $values->action_id;
    $link = Link::fromTextAndUrl(t('Delete'), URL::fromUri('internal:/admin/activity/action/delete/' . $id))->toString();
    return $link;
  }

}
