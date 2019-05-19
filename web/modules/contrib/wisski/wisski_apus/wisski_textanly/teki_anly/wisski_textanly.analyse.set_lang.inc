<?php


/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */




/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_set_lang($op, $ap) {

  switch ($op) {

    case 'edit':

      if (!$ap->settings) {
        $ap->settings = array(
            'lang' => 'de',
            );
      }

      $fieldset = array();
      $fieldset['lang'] = array(
          '#type' => 'textfield',
          '#title' => t('Language'),
          '#default_value' => $ap->settings['lang'],
          );

      return $fieldset;

    case 'key_info':
      return array(
        'input' => array(
        ),
        'output' => array(
          'lang',
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
*
*/
function wisski_textanly_analyse_set_lang($analyser, $text_struct, $ticket) {
  
  $text_struct['lang'] = $analyser->settings['lang'];
  return $text_struct;

}


