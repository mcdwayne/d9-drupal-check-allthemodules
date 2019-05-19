<?php

namespace Drupal\sl_base\Entity;

use Drupal\node\Entity\Node as BaseNode;

class SlNodeEntity extends BaseNode {

  /**
   * Used to show correct label as administrative title
   * @return mixed
   */
  function label() {

    $is_admin = \Drupal::service('router.admin_context')->isAdminRoute();

    if ($is_admin) {
      $bundle = $this->bundle();
      $nodes_with_admin_titles = [
        'sl_team',
        'sl_competition_edition',
        'sl_competition'
      ];
      if (in_array($bundle, $nodes_with_admin_titles)) {
        return $this->id() . ' - ' . $this->field_sl_administrative_title->value;
      }
    }

    return parent::label();
  }
}