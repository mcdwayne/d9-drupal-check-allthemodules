<?php

/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */




/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_rerank($op, $ap) {

  switch ($op) {

    case 'edit':

      if (!$ap->settings) {
        $ap->settings = array(
          'overlap_factor' => 0.5,
          'no_uri' => FALSE,
        );
      }

      $fieldset = array();
      $fieldset['overlap_factor'] = array(
        '#type' => 'textfield',
        '#title' => t('Overlap factor'),
        '#default_value' => $ap->settings['overlap_factor'],
        '#element_validate' => array('_wisski_textanly_admin_validate_numeric'),
      );
      $fieldset['no_uri'] = array(
        '#type' => 'checkbox',
        '#title' => t('Include annotations that point to an instance.'),
        '#default_value' => $ap->settings['no_uri'],
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
* @author Martin Scholz
*/
function wisski_textanly_analyse_rerank($analyser, $text_struct, $ticket) {
  
  $overlap_factor = isset($analyser->settings['overlap_factor']) ? $analyser->settings['overlap_factor'] : 0.5;
  $no_uri = isset($analyser->settings['no_uri']) ? $analyser->settings['no_uri'] : FALSE;
  
  // annos affect rank of overlapping annos of same class
  // optionally only annos without uri are taken into account
  $annos = $text_struct['annos'];
  foreach ($annos as $k1 => &$a1) {
    if ($no_uri && !isset($a1['uri'])) continue;
    $rank = $a1['rank'];
    foreach ($annos as $k2 => &$a2) {
      if ($k1 == $k2) continue;
      if ($a1['class'] != $a2['class']) continue;
      
      // determine overlap
      // rank is increased properionally with overlap
      list(, , $overlap) = _wisski_textanly_calculate_overlap($a1['range'][0], $a1['range'][1], $a2['range'][0], $a2['range'][1]);
      if ($overlap == 0) continue; // no overlap

      $rank += $overlap_factor * $a2['rank'] * $overlap;
    }
    $text_struct['annos'][$k1]['rank'] = $rank;
  }

  return $text_struct;

}
