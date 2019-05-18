<?php

use Drupal\taxonomy\Entity\Term;

function dbpedia_spotlight_batch($content_type, $vocabulary, &$context) {

    $query = db_select('node_field_data', 'n');
    $query->join('node__body', 'nb', 'nb.entity_id = n.nid');
    $query->fields('n', array('nid', 'title'))
        ->fields('nb', array('body_value'))
        ->condition('n.type', $content_type);

    $rows = $query->execute()->fetchAllAssoc('nid');

    foreach ($rows as $row) {
        $node = node_load($row->nid);
        $tags = dbpedia_spotlight_tags_annotation($row->body_value);

        // unset values previously stored
        $node->field_dbpedia->setValue(NULL);
        $tids = array();

        foreach ($tags as $tag) {
            $tids[] = dbpedia_spotlight_term_create($tag, $vocabulary);
        }

        $node->field_dbpedia->setValue(array_unique($tids));
        $node->save();

        $context['results'][] = $row->nid;
        $context['message'] = "Processing... => " + $row->nid;
    }
}

function dbpedia_spotlight_batch_finished_callback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
        $message = \Drupal::translation()->formatPlural(
            count($results),
            'One post processed.', '@count posts processed.'
        );
    }
    else {
        $message = t('Finished with an error.');
    }
    drupal_set_message($message);
    //$_SESSION['disc_migrate_batch_results'] = $results;
}

function dbpedia_spotlight_tags_annotation($text) {
   $service = \Drupal::service('dbpedia_spotlight.default');
   $tags = $service->dbpedia_spotlight_service($text);
   return $tags;
}

function dbpedia_spotlight_term_create($term_name, $vocabulary, array $parent = []) {
  $tid = dbpedia_spotlight_check_taxonomy_term($term_name);
  if (empty($tid)) {
      // Create the taxonomy term.
      $new_term = Term::create([
        'name' => $term_name,
        'vid' => $vocabulary,
        'parent' => $parent,
      ]);
      // Save the taxonomy term.
      $new_term->save();
      // Return the taxonomy term id.
      return $new_term->id();
  }
  else {
    return $tid;
  }
}

function dbpedia_spotlight_check_taxonomy_term($term_name) {
  $query = db_select('taxonomy_term_field_data', 'ttfd')
        ->condition('ttfd.name', $term_name)->fields('ttfd', array('tid'));
  $tid = $query->execute()->fetchField();
  return $tid;
}
