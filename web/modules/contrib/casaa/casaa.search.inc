<?php

function casaa_search_settings_callback($js = FALSE) {
	$title = t('Search Mapping Settings');
	
	ctools_include('ajax');
	ctools_include('modal');
	
	$output .= drupal_get_form('casaa_search_form');
	
	if ($js) {
		ctools_modal_render($title, $output);
	}
	else {
		return $output;
	}
}

/**
 * Build the form for search mappings in the system.
 */ 
function casaa_search_form() {
	$form = array();
	
	$form['casaa_search'] = array(
		'#type' => 'fieldset',
	);
		$form['casaa_search']['casaa_type_select'] = array(
			'#type' => 'select',
			'#title' => t('Select Type'),
			'#description' => t('You can filter the type of mapping to search by.'),
			'#options' => array('all' => t('All'), 'term' => t('Term Mapping'), 'path' => t('Path Mapping')),
		);
		$form['casaa_search']['casaa_search'] = array(
			'#type' => 'textfield',
			'#title' => 'Search',
			'#description' => t('Search for a taxonomy term or url path mapping.'),
		);
		
	$form['casaa_search_submit'] =array(
		'#type' => 'submit',
		'#value' => t('Search'),
	);
	
	// hidden form elements
	$form['casaa_domain'] = array(
		'#type' => 'hidden',
		'#value' => arg(6),
	);
	
	return $form;
}

/**
 * Validate the form for any errors or haxors 
 */
function casaa_search_form_validate(&$form, $form_state) {
	return true;
}

/**
 * Get the results from the user's search query.
 */ 
function casaa_search_form_submit($form, $form_state) {
	ctools_include('ajax');
	ctools_include('modal');
	
	$values = $form_state['values'];
	
	if ($values['op'] == 'Search') {
		$table = casaa_search_build_results($values['casaa_type_select'], $values['casaa_search'], $values['casaa_domain']);
		ctools_modal_render('title', $table);
	}
}


/**
 * Fetch the results from the db and build a table to display them.
 */
function casaa_search_build_results($type, $search, $domain) {
	
	$header = array(t('Mapping Type'), t('Name/Path'), t('Options'));
 	if ($type == 'term' || $type == 'all') {
   	$query = db_query("SELECT DISTINCT t.name FROM {casaa_terms} c 
			LEFT JOIN {term_data} t ON c.casaa_tid=t.tid
			LEFT JOIN {casaa_context_mapper} m ON m.aid=c.aid
			WHERE t.name LIKE '%s%' AND m.casaa_domain=%d", $search, $domain);
    while ($terms = db_fetch_object($query)) {
      $col = '';
      $col[] = t('Term');
      $col[] = $terms->name;
      $aid_query = db_query("SELECT c.casaa_tid FROM {casaa_terms} c LEFT JOIN {term_data} t ON c.casaa_tid=t.tid WHERE t.name LIKE '%s'", $terms->name);
      $result = db_fetch_object($aid_query);
      $col[] = l('configure', 'admin/build/casaa/mappings/nojs/edit/' . $domain . '/term/' . $result->casaa_tid, array('attributes' => array('class' => 'ctools-use-ajax')))
							 . ' / '
							 . l('delete', 'admin/build/casaa/mappings/nojs/delete/' . $domain . '/term/' . $result->casaa_tid, array('attributes' => array('class' => 'ctools-use-ajax')));
      $rows[] = $col;
    }
  }
  if ($type == 'all' || $type == 'path') {
   	if ($search == "front" || $search == 'frontpage') {
      $search = "[front]";
    }
    else if ($search == '404') {
      $search = "[404]";
    }
    $query = db_query("SELECT DISTINCT p.casaa_path FROM {casaa_paths} p
			LEFT JOIN {casaa_context_mapper} c ON c.aid=p.aid
 			WHERE casaa_path LIKE '%s%' AND c.casaa_domain=%d", $search, $domain);
    while ($path = db_fetch_object($query)) {
      $col = '';
      $col[] = t('Path');
      $col[] = $path->casaa_path;
      $aid_query = db_query("SELECT aid FROM {casaa_paths} WHERE casaa_path='%s'", $path->casaa_path);
      $result = db_fetch_object($aid_query);
      $col[] = l('configure', 'admin/build/casaa/mappings/nojs/edit/' . $domain . '/path/' . $result->aid, array('attributes' => array('class' => 'ctools-use-ajax')))
							 . ' / '
							 . l('delete', 'admin/build/casaa/mappings/nojs/delete/' . $domain . '/path/' . $result->aid, array('attributes' => array('class' => 'ctools-use-ajax')));
      $rows[] = $col;
    }
  }
  if (empty($rows) && $search) {
    $output .= "<strong>No results were found for \"" . $search . "\"</strong>";
  }
  else if (!empty($rows)) {
    $output .= theme_table($header, $rows);
  }

 	return $output;

}



