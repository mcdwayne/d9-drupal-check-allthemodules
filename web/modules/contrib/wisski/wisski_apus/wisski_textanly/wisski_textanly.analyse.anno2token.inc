<?php


/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */



/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_anno2token($op, $ap) {

  switch ($op) {

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
function wisski_textanly_analyse_anno2token($analyser, $text_struct, $ticket) {
  
  if ( !isset($text_struct['annos']) || empty($text_struct['annos'])
    || !isset($text_struct['tokens']) || empty($text_struct['tokens'])) 
    return $text_struct;
  
  $tokens = $text_struct['tokens'];
  $l = count($tokens);
  $a2t = array_fill(0, $l, NULL);

  foreach ($text_struct['annos'] as $i => $a) {
    list($s, $e) = $a['range'];
    for ($j = 0; $j < $l; $j++) {
      if ($tokens[$j][2] > $s) {
        if ($a2t[$j] === NULL) $a2t[$j] = array();
        $a2t[$j][] = $i;
        break;
      }
    }
    for (++$j; $j < $l; $j++) {
      if ($tokens[$j][1] >= $e) {
        continue 2; // with next anno
      }
      if ($a2t[$j] === NULL) $a2t[$j] = array();
      $a2t[$j][] = $i;
    }
  }
  
  $text_struct['a2t'] = $a2t;
      
  return $text_struct;  

}

