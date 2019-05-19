<?php

/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */




/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_anno_ids($op, $ap) {

  switch ($op) {

    case 'edit':

      $fieldset = array();
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
function wisski_textanly_analyse_anno_ids($analyser, $text_struct, $ticket) {
  
  if (!isset($text_struct['annos']) || empty($text_struct['annos'])) return $text_struct;
  $annos = array();
  foreach ($text_struct['annos'] as $a) {
    if (!isset($a['id'])) {
      $a['id'] = _wisski_textanly_generate_id();
    }
    $annos[$a['id']] = $a;
  }

  $text_struct['annos'] = $annos;
  return $text_struct;

}
