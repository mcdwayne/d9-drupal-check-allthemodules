<?php

/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */




/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_sentence_splitter($op, $ap) {

  switch ($op) {

    case 'edit':

      if (!$ap->settings) {
        $ap->settings = array(
          'sentence_tags' => '$\.',
          'mode' => 3
        );
      }

      $fieldset = array();
      $fieldset['sentence_tags'] =  array(
        '#type' => 'textfield',
        '#title' => t('POS tag'),
        '#description' => t('A regular expression pattern matching all POS tags that identify sentence boundaries.'),
        '#default_value' => $ap->settings['sentence_tags'],
      );
      $fieldset['mode'] = array(
        '#type' => 'radios',
        '#title' => t('Mode'),
        '#options' => array(
          '1' => t('Parallel to POS tag list ("sentence" key)'),
          '2' => t('Sentence intervals ("sentences" key)'),
          '3' => t('Both ("sentence" and "sentences" keys)'),
        ),
        '#default_value' => $ap->settings['mode'],
        '#description' => t('Determines the format in which the sentence info will be passed on.'),
      );

      return $fieldset;

    case 'key_info':
      return array(
        'input' => array(
          'pos',
        ),
        'output' => array(
          'sentence',
          'sentences',
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
function wisski_textanly_analyse_sentence_splitter($analyser, $text_struct, $ticket) {

  $posses = $text_struct['pos'];
  if (!isset($text_struct['pos']) || empty($text_struct['pos'])) return $text_struct;
  
  $tags = $analyser->settings['sentence_tags'];
  
  $state = 'E';
  $last = count($posses) - 1;
  foreach ($posses as $i => $pos) {
    // the token is a sentence's end if it either matches the ending tag
    // or it's the last token of the text.
    // (the last sentence of a text may end without mark.)
    $end = $i == $last || preg_match("/^($tags)$/u", $pos);
    $sentence[] = $state = ($end) ? 'E' : (($state == 'E') ? 'B' : 'I');
  }
  if (((int) $analyser->settings['mode']) & 1) {
    $text_struct['sentence'] = $sentence;
  }
  if(((int) $analyser->settings['mode']) & 2) {
    $sentences = array();
    $start === NULL;
    foreach ($sentence as $i => $s) {
      if ($s == 'B') {
        $start = $i;
      } elseif ($s == 'E' && $start !== NULL) {
        $sentences[] = array($start, $i);
        $start = NULL;
      }
    }
    $text_struct['sentences'] = $sentences;
  }

  return $text_struct;

}


function _wisski_textanly_sentence_get_start($i, $text_struct) {
  while ($i > 0 && $text_struct['sentence'][$i] != "B") $i--;
  return $i;
}


function _wisski_textanly_sentence_get_end($i, $text_struct) {
  $l = count($text_struct['sentence']);
  while ($i < $l && $text_struct['sentence'][$i] != "E") $i++;
  return $i;
}

