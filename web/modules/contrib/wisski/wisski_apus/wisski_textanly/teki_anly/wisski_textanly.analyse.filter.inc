<?php

/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */




/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_filter($op, $ap) {

  switch ($op) {

    case 'edit':

      drupal_load('module', 'wisski_pathbuilder');
      module_load_include('inc', 'wisski_pathbuilder');

      module_load_include('inc', 'wisski_textanly', 'wisski_textanly.analyse.common');
      list($classes, $groups) = _wisski_textanly_get_classes_list();
      $class_options = array_merge(array('*' => t('<All>'), '' => t('<Manual>')), $groups, $classes);

      if (!$ap->settings) {
        $ap->settings = array(
          'class' => '*',
          'threshold' => 1,
          'no_overlap' => TRUE,
        );
      }

      $fieldset = array();
      $fieldset['class'] =  array(
        '#type' => 'select',
        '#title' => t('Affected class'),
        '#multiple' => FALSE,
        '#options' => $class_options,
        '#default_value' => array_key_exists($ap->settings['class'], $class_options) ? $ap->settings['class'] : '',
      );
      $fieldset['class_manual'] = array(
        '#type' => 'textfield',
        '#title' => t('Manual class'),
        '#default_value' => $ap->settings['class_manual'],
        '#description' => t('This field is only considered if field Group is %m. You can set a class manually or multiple classes separated by whitespace. RDF prefixes are expanded.', array('%m' => t('<Manual>'))),
      );
      $fieldset['threshold'] =  array(
        '#type' => 'textfield',
        '#title' => t('Filter by rank threshold'),
        '#description' => t('A numeric value. 0 disables threshold filter.'),
        '#default_value' => $ap->settings['threshold'],
        '#element_validate' => array('_wisski_textanly_admin_validate_numeric'),
      );
      $fieldset['no_overlap'] = array(
        '#type' => 'checkbox',
        '#title' => t('Filter overlapping annotations'),
        '#default_value' => $ap->settings['no_overlap'],
        '#description' => t('Keeps only the highest-ranked annotation if annotations overlap.'),
      );

      return $fieldset;

    case 'submit':
      
      $ap->settings['class'] = $ap->settings['class'] == '' ? $ap->settings['class_manual'] : $ap->settings['class']; 

      return $ap->settings;

    case 'key_info':
      return array(
        'input' => array(
          'annos',
        ),
        'output' => array(
          'annos',
        )
      );

    default:

      return NULL;

  }

}



/* ***************** *
*  ANALYSER
** ***************** */


/**
* sort out annos under a certain threshold
*
* @author Martin Scholz
*/
function wisski_textanly_analyse_filter($analyser, $text_struct, $ticket) {

  if (!isset($text_struct['annos']) || empty($text_struct['annos'])) return $text_struct;
  $annos = $text_struct['annos'];

  $classes = preg_split('/ +/u', $analyser->settings['class'], PREG_SPLIT_NO_EMPTY);
  foreach ($classes as &$c) {
    if ($c != '*') $c = wisski_store_getObj()->wisski_ARCAdapter_delNamespace($c);
  }

  // filter annos by rank threshold
  $threshold = isset($analyser->settings['threshold']) ? $analyser->settings['threshold'] : 1;
  $selected = array();
  foreach ($annos as $k => $a) {
    if ((isset($classes[0]) && $classes[0] == '*') || in_array($a['class'], $classes)) {
      unset($annos[$k]);
      if ($a['approved'] || $a['rank'] >= $threshold) $selected[$k] = $a;
    }
  }

  // filter annos overlapping others with higher rank
  if ($analyser->settings['no_overlap']) {
    
    // we need the compare function
    include_once(dirname(__FILE__) . '/wisski_textanly.analyse.common.inc');

    // only select top ranked annotations:
    // sort by priority (first) and length (second)
    uasort($selected, '_wisski_textanly_compare_annotations');
    $selected = array_reverse($selected, TRUE);

    // select the top set of annos that don't overlap
    $top_annos = array();
    foreach ($selected as $i => $candidate) {
      $top = TRUE;
      foreach ($top_annos as $j => $a) {
        if ($candidate['range'][1] > $a['range'][0] && $candidate['range'][0] < $a['range'][1]) {
          // annotations overlap => cannot be top annotation
          $top = FALSE;
          break;
        }
      }
      if ($top) {
        //annotation did not overlap with any other top annotation => add to top annos
        $top_annos[$i] = $candidate;
      }
    }
    $selected = $top_annos;
  }
  
  $text_struct['annos'] = array_merge($annos, $selected);

  return $text_struct;

}

