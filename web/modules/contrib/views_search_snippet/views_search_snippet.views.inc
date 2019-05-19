<?php
/*
 *Describe data tables and fields (for field snippet) to Views.
 */
function views_search_snippet_views_data() {
  $data['node_search_index']['snippet'] = array(
    'title' => t('Snippet'),
    'help' => t('A snippet from the content with search keywords highlighted. This will not be used if the search filter is not also present.'),
    'field' => array(
      'id' => 'views_search_snippet',
      'no group by' => TRUE,
    ),
  );
  $data['node_search_index']['keys'] = array(
    'title' => t('Search Keywords'),
    'help' => t('The keywords to search for.')
  );
  return $data;

}