<?php

namespace Drupal\suggest_similar_titles\Controller;

use Drupal\Core\Url;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class SuggestionController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   */
  public function suggest_similar_titles_ajax_title() {
    $post_title = trim($_POST['title']);
    $node_array = array();
    $data = '';

    if ($post_title) {
	  // Number of nodes to display as suggestion.
	  $no_of_nodes = \Drupal::state()->get("suggest_similar_titles_noof_nodes") ?: '5';
      $check_permissions = \Drupal::state()->get("suggest_similar_titles_node_access") ?: "no";
      $type = "article";
      $percentage = \Drupal::state()->get("suggest_similar_titles_percentage") ?: 75;
      $ignored = \Drupal::state()->get("suggest_similar_titles_ignored") ?: "the,is,a";
      $desired_ratio = 100 / $percentage;
      // Splitting string into array.
      $exp_ignored = explode(",", $ignored);
      // Splitting title string into array.
      $exp_title = explode(" ", $post_title);  
      // Removing ignored words from node title.
      $title_minus_ignore = array_diff($exp_title, $exp_ignored);
      // Preparing like clause for each word of title.
      $like_clause = '(';
      // Arguments array to pass db_query function.
      $query_args = array(':type' => $type);
      // Variable to increment pattern count (depends on number of title words)
      $pattern_count = 1;
	
      foreach ($title_minus_ignore as $value) {
        $pattern = ':pattern' . $pattern_count;
        if ($like_clause == '(') {
          $like_clause .= 'nfr.title LIKE ' . $pattern;
        }
        else {
          $like_clause .= ' OR nfr.title LIKE ' . $pattern;
        }
        $query_args[$pattern] = '%' . db_like($value) . '%';
        $pattern_count++;
      }
      $like_clause .= ')';
	
      $results = db_query("
	    SELECT nfr.title as title, n.nid as nid
	    FROM node AS n , node_field_revision nfr
        WHERE n.nid = nfr.nid AND n.type = :type AND " . $like_clause, $query_args
	    )->fetchAll();
	
      foreach ($results as $res) {
        $exp_exist_title = explode(" ", $res->title);
        $explode = array_diff($exp_exist_title, $exp_ignored);
        if (count($explode) >= count($exp_title)) {
          $count = count($exp_title);
          $diff = count(array_diff($exp_title, $explode));
          $words_match = $count - $diff;
        }
        else {
          $count = count($explode);
          $diff = count(array_diff($explode, $exp_title));
          $words_match = $count - $diff;
        }
        if ($words_match > 0) {
          $ratio = $count / $words_match;
          if ($ratio <= $desired_ratio) {
            $node = node_load($res->nid);
            $node->link = Url::fromUri('internal:/node/' . $res->nid, array('attributes' => array('target' => '_blank')));
            // Whether to check node permission before node display.
            if ($check_permissions == 'yes') {
              // Check if user has access to view node.
              if (node_access('view', $node)) {
                $node_array[$res->nid] = $node;
              }
            }
            else {
              $node_array[$res->nid] = $node;
            }
          }
        }
        if (count($node_array) == $no_of_nodes) {
          break;
        }
      }
	
      if (count($node_array) > 0) {
        // Calling theme function and passing nodes array.
        $data = array(
				  '#theme' => 'suggest_similar_titles',
				  '#node' => $node_array,
			    );
      }
	
  }	
  // Returning data to ajax call.
  $html = \Drupal::service('renderer')->renderRoot($data);
  $response = new Response();
  $response->setContent($html);
  
  return $response;

  }

}
