<?php
/**
 * @file
 */
class views_join_term_edge extends views_join {
  function build_join($select_query, $table, $view_query) {
    $found = FALSE;

    // If there's a condition on parent, then let it be unless it's blank.
    // If it's blank we set it to the root of the tree.
    foreach ($view_query->where as $data) {
      foreach ($data['conditions'] as $condition) {
        if ($condition['field'] == $table['alias'] . '.parent') {
          if ($condition['value'] == '') {
            $condition['value'] = 0;
          }
          else {
            $found = TRUE;
            break;
          }
        }
      }
    }

    // If no condition on parent was found, add the root pid as a condition.
    if (!$found) {
      $view_query->add_where_expression(0, $table['alias'] . '.parent = :parent', array(':parent' => 0));
    }
    parent::build_join($select_query, $table, $view_query);
  }
}
