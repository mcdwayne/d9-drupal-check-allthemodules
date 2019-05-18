<?php
namespace Drupal\proconcom;

class proconcom_views_handler_argument_parent_nid extends views_handler_argument_numeric {
	
  function proconcom_views_arguments() {
    $arguments = array(
      'argumented_node' => array(
        'name' => t("proconcom: Parent Node ID"),
        'handler' => "proconcom_views_handler_arg_argumented_node",
      ),
    );
    return $arguments;
  }

  function proconcom_views_handler_arg_argumented_node($op, &$query, $argtype, $arg = '') {
    switch ($op) {
      case 'summary' :
        $query->ensure_table("proconcom");
        $query->add_field("parent_nid");
        $fieldinfo['field'] = "proconcom.parent_nid";
        return $fieldinfo;
      case 'filter' :
        $query->ensure_table("proconcom");
        $query->add_where("parent_nid = '$arg'");
        $query->add_where("proconcom.nid = node.nid");
        break;
      case 'link' :
        return l($query->title, "$arg/$query->nid");
      case 'title' :
        if ($query) {
          $term = db_query("SELECT title FROM {node} WHERE nid = '%d'", $query)->fetchObject();
          return $term->title;
        }
    }
  }
}
