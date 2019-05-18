<?php

/**
 *  A replacement page for the test swarm test detail page - it appends all the specific elements
 *  that had problems on that page.
 */
function accessibility_testswarm_test_details_tests($caller, $id) {
  module_load_include('inc', 'testswarm', 'testswarm.pages');
  $output = testswarm_test_details_tests($caller, $id);
  $tests = testswarm_defined_tests();
  if (!empty($tests)) {
    $test = $tests[$caller];
    if (!$test || $test['module'] != 'accessibility_testswarm') {
    	return $output;
    }
  }
  $acessibility_testswarm_output = '<h2>'. t('Accessibility information') .'</h2>';
  $header = array(t('hook'), t('template'), t('function'), t('element'));
  $tests = db_select('accessibility_testswarm_test_detail', 'd')
  				->fields('d')
  				->condition('tri', (int)$id)
  				->execute()
  				->fetchAll();
  $rows = array();

  foreach($tests as $error) {
    $rows[] = array(htmlspecialchars($error->hook),
                      ($error->type != 'function' ? $error->theme_item : ''),
  									  ($error->type == 'function' ? $error->theme_item : ''),
  									  '<pre>' . htmlspecialchars($error->element) . '</pre>');
  }
  
  $acessibility_testswarm_output .= theme('table', array('header' => $header, 'rows' => $rows));
  return $output . $acessibility_testswarm_output;
}