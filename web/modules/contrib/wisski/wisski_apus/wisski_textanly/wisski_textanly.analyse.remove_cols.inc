<?php


/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */



/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_remove_cols($op, $ap) {

  switch ($op) {
    
     case 'edit':
      
      // n12n = nominalisation
      if (!$ap->settings) {
        $ap->settings = array(
          'cols' => array(),
        );
      }

      $fieldset = array();
      $fieldset['cols'] = array(
        '#type' => 'textfield',
        '#title' => t('Keys to be removed'),
        '#default_value' => join(',', $ap->settings['cols']),
      );

      return $fieldset;

    case 'submit':
      
      $ap->settings['cols'] = mb_split(' *, *', $ap->settings['cols']);
      return $ap->settings;

    case 'key_info':
      return array(
        'input' => array(
          'tokens',
          'annos',
        ),
        'output' => array(
          'a2t',
        )
      );

    default:

      return NULL;

  }

}





/* Call an external preprocessor for lemma and part-of-speech
*
* @return token array where tokens are enhanced with keys 'lemma' and 'pos'
* where information was available
*
* @author Martin Scholz
*
*/
function wisski_textanly_analyse_remove_cols($analyser, $text_struct, $ticket) {
  
  foreach ($analyser->settings['cols'] as $k) {
    unset($text_struct[$k]);
  }

  return $text_struct;  

}

