<?php

/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */


/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_datetime($op, $ap) {

  switch ($op) {

    case 'edit':

      drupal_load('module', 'wisski_pathbuilder');
      include_once(drupal_get_path('module', 'wisski_pathbuilder') . '/wisski_pathbuilder.inc');

      //for pb_group: display every top group of the import PB section
      $pb_groups = array();
      foreach (wisski_pathbuilder_getEnabledGroups(3) as $id) {
        $info = wisski_pathbuilder_getPathData($id);
        if ($info['group_id'] < 1) $pb_groups["$id"] = $info['name'];
      }


      if (!$ap->settings) {
        $ap->settings = array(
            'class' => '',
            );
      }

      $fieldset = array();
      $fieldset['class'] = array(
          '#type' => 'select',
          '#title' => t('group time'),
          '#multiple' => false,
          '#options' => $pb_groups,
          '#default_value' =>  $ap->settings['class'],
          );

      return $fieldset;

    case 'key_info':
      return array(
        'input' => array(
          'tokens',
          'lang',
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



/** Find annotations that have a certain date syntax
* This is the analyse operation for analysis component type vocab
* 
*
* @author Martin Scholz
*
*/
function wisski_textanly_analyse_datetime($analyser, $text_struct, $ticket) {

  if (!isset($text_struct['annos'])) $text_struct['annos'] = array();
  $tokens = $text_struct['tokens'];
  $class = $analyser->settings['class'];

  if (empty($tokens)) return $text_struct;

  $formats = array(
      DateTime::ISO8601,
      'Y',
      'd#m#y',
      'j#n#y',
      'm#y',
      'd#m#Y',
      'j#n#Y',
      'n#Y',
      'm#Y',
      'M Y',
      'd M Y',
      'd. M Y',
      'Ymd',

      );

  $tokens_len = count($tokens);
  for ($s = 0; $s < $tokens_len; $s++) {
    for ($e = $s; $e < $tokens_len; $e++) {
      
      $str = mb_substr($text_struct['text'], $tokens[$s][1], $tokens[$e][2] - $tokens[$s][1]);
      
      if (mb_strlen($str) < 3) continue;  // year with only 2 ciphers are ruled out. too many false positives
      
      // use the php date parsing function
      foreach($formats as $f) {
        $date = date_create_from_format($f, $str);
        if ($date !== FALSE) {
          $text_struct['annos'][] = array(
            'rank' => ($e - $s + 1) * 2,
            'class' => $class,
            'range' => array($tokens[$s][1], $tokens[$e][2]),
            'datetime' => date_format($date, DateTime::ISO8601),
            '_origin' => 'wisski_textanly_datetime',
          );
        }
      }
      
      // some selfdefined regexes
      if (preg_match('/^(([012]?\d|30|31)\.?)?\s*(Jan(\.|uar)|Feb(\.|ruar)|Mär(\.|z)|Apr(\.|il)|Mai|Jun[\.i]|Jul[\.i]|Aug(\.|ust)|Sep(\.|tember)|Okt(\.|ober)|Nov(\.|ember)|Dez(\.|ember))\s+\d+$/u', $str)) {
        $text_struct['annos'][] = array(
          'rank' => ($e - $s + 1) * 2,
          'class' => $class,
          'range' => array($tokens[$s][1], $tokens[$e][2]),
          '_origin' => 'wisski_textanly_datetime',
        );
      }

      if (preg_match('/^(([012]?\d|30|31)(\.|th|rd|st|nd)?\s+)?(Jan(\.|uary)|Feb(\.|ruary)|Mar(\.|ch)|Apr(\.|il)|May|Jun[\.e]|Jul[\.y]|Aug(\.|ust)|Sep(\.|tember)|Oct(\.|ober)|Nov(\.|ember)|Dec(\.|ember))(,?\s+\d+)?$/u', $str)) {
        $text_struct['annos'][] = array(
          'rank' => ($e - $s + 1) * 2,
          'class' => $class,
          'range' => array($tokens[$s][1], $tokens[$e][2]),
          '_origin' => 'wisski_textanly_datetime',
        );
      }

      if (preg_match('/^(Jan(\.|uary)|Feb(\.|ruary)|Mar(\.|ch)|Apr(\.|il)|May|Jun[\.e]|Jul[\.y]|Aug(\.|ust)|Sep(\.|tember)|Oct(\.|ober)|Nov(\.|ember)|Dec(\.|ember))(\s+the)?\s+(([012]?\d|30|31)(\.|th|rd|st|nd)?)?(,?\s+\d+)?$/u', $str)) {
        $text_struct['annos'][] = array(
          'rank' => ($e - $s + 1) * 2,
          'class' => $class,
          'range' => array($tokens[$s][1], $tokens[$e][2]),
          '_origin' => 'wisski_textanly_datetime',
        );
      }


    }
  }
  
  return $text_struct;

}


