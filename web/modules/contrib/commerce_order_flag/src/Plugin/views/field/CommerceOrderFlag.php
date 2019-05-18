<?php

namespace Drupal\commerce_order_flag\Plugin\views\field;

use Drupal\Core\Database\Database;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_order_flag")
 */
class CommerceOrderFlag extends FieldPluginBase {

  protected $flags = [];

  /**
   * Left default
   *
   * @{inheritdoc}
   */
  public function query() {
  }

  /**
   * Define output of field
   *
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity;

    if (empty($this->flags)) {
      $db = Database::getConnection();
      $cof = $db->select('commerce_order_flag', 'cof');
      $cof->fields('cof');
      $flags = $cof->execute()->fetchAll(\PDO::FETCH_ASSOC);

      foreach ($flags as $flag_value) {
        $this->flags[$flag_value['order_id']] = $flag_value['value'];
      }
    }

    $status = (isset($this->flags[$entity->id()]) && $this->flags[$entity->id()] == 1 ? t('Yes') : t('No'));
    return $status;
  }

}
