<?php

/**
 * Views field handler
 */
class views_handler_field_community_tasks_state extends views_handler_field_markup {


  function query() {
    $this->ensure_my_table();

    $this->add_additional_fields([
      'nid' =>  ['table' => 'node', 'field' => 'nid']
    ]);
  }

  function render($row) {
    return [
      '#type' => 'community_task_state',
      '#nid' => $row->nid
    ];
  }
}