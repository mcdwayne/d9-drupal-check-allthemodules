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

      if (!$ap->settings) {
        $ap->settings = array(
          'threshold' => 1,
          'no_overlap' => TRUE,
        );
      }

      $fieldset = array();
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

  $annos = $text_struct['annos'];
  if (!isset($text_struct['annos']) || empty($text_struct['annos'])) return $text_struct;

  // filter annos by rank threshold
  $threshold = isset($analyser->settings['threshold']) ? $analyser->settings['threshold'] : 1;
  foreach ($annos as $k => $a) {
    if (!$a['approved'] && $a['rank'] < $threshold) unset($annos[$k]);
  }

  $annos = array_values($annos);

  // filter annos overlapping others with higher rank
  if ($analyser->settings['no_overlap']) {
    // only select top ranked annotations:
    // sort by priority (first) and length (second)
    usort($annos, '_wisski_textanly_compare_annotations');

    // select the top set of annos that don't overlap
    $top_annos = array();
    for ($i = count($annos) - 1; $i >= 0; $i--) {
      $candidate = $annos[$i];
      $top = TRUE;
      $lj = count($top_annos);
      for ($j = 0; $j < $lj; $j++) {
        $a = $top_annos[$j];
        if ($candidate['range'][1] > $a['range'][0] && $candidate['range'][0] < $a['range'][1]) {
          // annotations overlap => cannot be top annotation
          $top = FALSE;
          break;
        }
      }
      if ($top) {
        //annotation did not overlap with any other top annotation => add to top annos
        $top_annos[] = $candidate;
      }
    }

    $annos = $top_annos;
  }
  
  $text_struct['annos'] = $annos;

  return $text_struct;

}



/** Compares 2 annotations
*
* @author Martin Scholz
*
*/
function _wisski_textanly_compare_annotations($a, $b) {

// approved anno always beats non-approved anno
  if (isset($a['approved']) && $a['approved']) {
    if (!isset($b['approved']) || !$b['approved']) return 1;
  } elseif (isset($b['approved']) && $b['approved']) {
    return -1;
  }

  // if both are not approved, the rank decides. (Also if both are approved, though this should not occur)
  if (isset($a['rank']) && isset($b['rank'])) {

    // the higher ranked anno is greater
    $c = $a['rank'] - $b['rank'];
    if ($c) return $c;
    // if rank is equal, the longer anno is greater
    $l = ($a['range'][1] - $a['range'][0]) - ($b['range'][1] - $b['range'][0]);
    if ($l) return $l;
    // if length is equal, the one that has an 'approved' key is greater (it surely comes from the editor, thus we prefer the 'old' anno)
    return (isset($b['approved'])) ? -1 : 1;

  } elseif (isset($a['rank'])) {

    // annotation without rank is considered higher, because:
    // no rank generally means that the annotation comes from outside
    return 1;

  } elseif (isset($a['rank'])) {
    
    return -1;

  } else {
    
    // both don't have ranks => consider equal
    return 0;

  }

}



