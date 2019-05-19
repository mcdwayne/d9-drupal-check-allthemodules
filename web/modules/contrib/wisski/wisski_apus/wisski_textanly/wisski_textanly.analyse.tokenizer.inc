<?php


/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */



/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_tokenizer($op, $ap) {

  if ($op == 'key_info') {
    
    return array(
      'input' => array(
        'text',
        'char_offset',
        'lang',
      ),
      'output' => array(
        'tokens',
      )
    );

  } else {
    return NULL;
  }

}




/* ***************** *
*  ANALYSER
** ***************** */




/** A tokenizer
* returns a sequence of tokens each being an array of the form
* array(<surface>, <start>, <end>).
* <surface> will be equal to mb_substring($text, $token[1], $token[2])
*
* @author Martin Scholz
*
*/
function wisski_textanly_analyse_tokenizer($analyser, $text_struct, $ticket) {
  
  if (!isset($text_struct['text']) || empty($text_struct['text'])) return $text_struct;

  $lang = isset($text_struct['lang']) ? $text_struct['lang'] : '';
  $char_offset = isset($text_struct['char_offset']) ? $text_struct['char_offset'] : 0;
  
  $abbr = _wisski_textanly_analyse_tokenizer_load_abbrev($analyser->aid, $lang);

  if (!preg_match_all('/[^\\p{Z}\\p{Cc}\\p{Cf}]+/u', $text_struct['text'], $matches, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE)) return $text_struct;

  $tokens = array();
  foreach ($matches[0] as $m) {

    // offset capture broken with utf8. workaround: 
    $m[1] = mb_strlen(substr($text_struct['text'], 0, $m[1]));

    $m[2] = $m[1] + mb_strlen($m[0]); // end offset of match

    if (isset($abbr[$m[0]]) ||
        preg_match('/^[\\p{L}\\p{N}]+(\\.[\\p{L}\\p{N}]+)*$/u', $m[0]) ||
        preg_match_all('/\\p{P}+/u', $m[0], $punct, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE) == 0) {

      // no punctuation marks in match => take it as a whole token
      if (trim($m[0]) != '') $tokens[] = $m;

    } else {

      // split match into tokens according to puncts
      $last_offset = 0;
      foreach ($punct[0] as $p) {

        $p[1] = mb_strlen(substr($m[0], 0, $p[1]));

        $token = array(  // token before this punct
          mb_substr($m[0], $last_offset, $p[1] - $last_offset),
          $m[1] + $last_offset + $char_offset,
          $m[1] + $p[1] + $char_offset,
        );
        if (trim($token[0]) != '') $tokens[] = $token;

        // one token for each char
        $l = mb_strlen($p[0]);
        for ($i = 0; $i < $l; $i++) {
          $token = array(
            mb_substr($p[0], $i, 1),
            $m[1] + $p[1] + $i + $char_offset,
            $m[1] + $p[1] + $i + 1 + $char_offset,
          );
          if (trim($token[0]) != '') $tokens[] = $token;
        }

        $last_offset = $p[1] + $l;

      }

      if ($m[2] != $m[1] + $last_offset) {
        $token = array(
          mb_substr($m[0], $last_offset, mb_strlen($m[0])),
          $m[1] + $last_offset + $char_offset,
          $m[2] + $char_offset,
        );
        if (trim($token[0]) != '') $tokens[] = $token;
      }

    }
  }
  
  // remove empty tokens
  // use unset and array_values instead of array_splice (performance)
  foreach ($tokens as $k => $t) {
    if (trim($t[0]) == '') unset($tokens[$k]);
  }
  $text_struct['tokens'] = array_values($tokens);

  return $text_struct;

}



function _wisski_textanly_analyse_tokenizer_load_abbrev($aid, $lang) {
  
  $aids = array("$aid.", '');
  preg_match('/^((([A-Za-z]+[-_])?[A-Za-z]+[-_])?[A-Za-z]+)$/u', $lang, $langs);
  array_shift($langs);
  $langs[] = 'default';
  
  $dir = dirname(__FILE__);
  foreach ($aids as $id) {
    foreach ($langs as $lang) {
      $file = dir . "/tokenizer.abbrev.$id$lang.txt";
      if (file_exists($file)) {
        $abbr = array();
        foreach (explode('\n', file_get_contents($file)) as $line) {
          $i = strpos($line, ' #');
          if ($i !== FALSE) $line = substr($line, 0, $i);
          if (trim($line) != '')
            $abbr[] = $line;
        }
        return $abbr;
      }
    }
  }
  return array();
}




