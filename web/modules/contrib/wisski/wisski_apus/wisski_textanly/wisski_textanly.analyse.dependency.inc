<?php


/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */



/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_dependency($op, $ap) {

  switch ($op) {

    case 'edit':

      if (!$ap->settings) {
        $ap->settings = array(
          'cmd' => '',
          'keys' => array('tokens' => 1, 'lemmata' => 1, 'pos' => 1),
        );
      }

      $items['infotext'] = array(
        '#type' => 'markup',
        '#value' => t('Calls an external dependency parser. The parser must be able to digest plain text as input and produce CONLL 09 compliant output.'),
      );
      $items['cmd'] = array(
        '#type' => 'textfield',
        '#title' => t('Preprocessor command'),
        '#description' => t('with optional arguments. data will be streamed to stdin and read from stdout.'),
        '#default_value' => $ap->settings['cmd'],
      );
      $items['keys'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Fields to be passed on'),
        '#description' => t('Further output fields of the parser that shall be passed on in the pipeline.'),
        '#options' => array('tokens' => t('Tokens'), 'lemmata' => t('Lemmata'), 'pos' => t('POS tags'), 'cats' => t('Word categories'), 'flex' => t('Morphological Information')),
        '#default_value' => $ap->settings['keys'],
      );
      return $items;

    case 'submit':
      
      $settings = $ap->settings;
      $settings['keys'] = array_filter($settings['keys']);
      return $settings;

    case 'key_info':
      return array(
        'input' => array(
          'text',
          'tokens'
        ),
        'output' => array(
          'tokens',
          'lemmata',
          'pos',
          'cats',
          'flex',
          'deps',
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
function wisski_textanly_analyse_dependency($analyser, $text_struct, $ticket) {
  
  $lang = $text_struct['lang'];
  if (substr($lang, 0, 2) != 'de') {
    return $text_struct;
  }

  $cmd = $analyser->settings['cmd'];
  $keys = $analyser->settings['keys'];
  $unknown_lemma = $analyser->settings['unknown_lemma'];

  // build parser input
  // take tokens if possible, otherwise plain text
  if (!$fields['tokens'] && isset($text_struct['tokens']) && !empty($text_struct['tokens'])) {
    // build from tokens: one word per line
    $str = "";
    foreach ($text_struct['tokens'] as $t) $str .= $t[0] . "\n";
  } else {
    $str = $text_struct['text'];
  }
  if (trim($str) == '') return $text_struct;

  // prepare process call
  $descriptorspec = array(
    0 => array("pipe", "r"),  // STDIN ist eine Pipe, von der das Child liest
    1 => array("pipe", "w"),  // STDOUT ist eine Pipe, in die das Child schreibt
    2 => array("pipe", "a"),
  );
  
  // start process
  $ph = proc_open($cmd, $descriptorspec, $pipes, dirname($cmd));
  if (!is_resource($ph)) {
    wisski_textanly_log($ticket, "Dependency parser error: Could not call parser.", "err");
    return $text_struct;
  }
  
  // send tokens
  fwrite($pipes[0], $str);
  fflush($pipes[0]);
  fclose($pipes[0]);
  
  // get result and finish process
  // in case the program will not terminate (swipl bug...)
  // we wait for some seconds and then break as gracefully as we can...
  // see http://stackoverflow.com/questions/5309900/php-process-execution-timeout
#  stream_set_timeout($pipes[1], 30);
#  $lines = '';
#  $lines = fread($pipes[1], 1000);
#  $info = stream_get_meta_data($pipes[1]);
#  if ($info['timed_out']) {
#    watchdog('wisski_textanly', "Dep parser timed out on ticket $ticket", NULL, WATCHDOG_ERROR);
#    proc_close($ph);
#    return $text_struct;
#  }

  $read = array($pipes[1]);
  $write = NULL;
  $except = NULL;
  if (FALSE === stream_select($read, $write, $except, 30)) {
    wisski_textanly_log($ticket, 'stream_select() failed when calling dep parser', 'err');
    watchdog('wisski_textanly', "stream_select() failed when calling dep parser on ticket $ticket", NULL, WATCHDOG_ERROR);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($ph);
    return $text_struct;
  }
  if (empty($read)) {
    wisski_textanly_log($ticket, 'Dep parser timed out', 'err');
    watchdog('wisski_textanly', "Dep parser timed out on ticket $ticket", NULL, WATCHDOG_ERROR);
#    fclose($pipes[1]);
#    fclose($pipes[2]);
#    proc_close($ph);
    wisski_textanly_log($ticket, 'Resuming...', 'err');
    return $text_struct;
  }

  $lines = stream_get_contents($pipes[1]);
  fclose($pipes[1]);
  $err = stream_get_contents($pipes[2]);
  fclose($pipes[2]);
  proc_close($ph);
  if ($err) { 
    wisski_textanly_log($ticket, 'Dependency parser error: ' . $err, 'err');
    return $text_struct;
  }

  // parse output
  $cols = array_flip(array('id', 'tokens', 'lemmata', 'categories', 'pos', 'flex', 'dep_head', 'dep_role'));
  $table = array();
  $lines = mb_split("\r\n|\r|\n", $lines);
//wisski_textanly_log($ticket, $lines, 'dbg');
  foreach ($lines as $l) {
    if (trim($l) == '') continue;
    $table[] = mb_split('\t', $l);
  }
  
  if (empty($table)) {
    wisski_textanly_log($ticket, 'Dependency parser error: Got empty result for non-empty text.', 'err');
    return $text_struct;
  }

  // key "deps"
  // we recalc the dep head offsets to be relative to the current token.
  // this way we don't have to worry about sentence offsets.
  // a dep head of 0 (root) stays 0. there is no conflict as reflexive
  // dependencies are not allowed.
  $deps = array_fill(0, count($table), array('deps' => array()));
  $col_head = $cols['dep_head'];
  $col_role = $cols['dep_role'];
  foreach ($table as $i => $r) {
    $head = $r[$col_head] + 0;
    if ($head != 0) {
      $head = $i + ($head - $r[$cols['id']]);
      $deps[$head]['deps'][] = $i;
    }
    $deps[$i]['head'] = $head;
    $deps[$i]['role'] = $r[$col_role];
  }
  $text_struct['deps'] = $deps;
  
  if ($keys['tokens']) {
    $tokens = array();
    $col_tokens = $cols['tokens'];
    $s = 0;
    foreach ($table as $r) {
      $token = $r[$col_tokens];
      if ($token == '') {
        wisski_textanly_log($ticket, array('Parser error: output contains no token or even no table', $table), 'err');
        return $text_struct;
      }
      $s = mb_strpos($str, $token, $s);
      if ($s === FALSE) {
        wisski_textanly_log($ticket, 'Parser error: output token does not match input', 'err');
        return $text_struct;
      }
      $e = $s + mb_strlen($token);
      $tokens[] = array($token, $s, $e);
      $s = $e;
    }      
    $text_struct['tokens'] = $tokens;
    unset($keys['tokens']);
  }
  
  foreach ($keys as $k) {
    $field = array();
    foreach ($table as $r) {
      $field[] = $r[$cols[$k]];
    }
    $text_struct[$k] = $field;
  }

  return $text_struct;  

}



/** Helper function for your convenience that returns the path of roles
* from one dependent to its head. If there is no dependence relation, return NULL.
*
*/
function _wisski_textanly_dependency_get_dependents($text_struct, $master) {
  
  if (!isset($text_struct['deps']) || empty($text_struct['deps'])) return array();
  $deps = $text_struct['deps'];

  $descendants = array();
  $masters = array();

  do {
    if (!isset($deps[$master]) || !isset($deps[$servant])) continue;
    $history = isset($descendants[$master]) ? $descendants[$master] : array('head' => array(), 'roles' => array());
    $servants = $deps[$master]['deps'];
    foreach ($servants as $s) {
      $masters[] = $s;
      $h = $history;
      $h['head'][] = $master;
      $h['roles'][] = $deps[$s]['role'];
      $descendants[$s] = $h;
    }
  } while ($master = array_shift($masters) != NULL);

  return $descendants;
  
}



/** Helper function for your convenience that returns the path of roles
* from one dependent to its head. If there is no dependence relation, return NULL.
*
*/
function _wisski_textanly_dependency_get_path($text_struct, $master, $servant) {
  
  if (!isset($text_struct['deps']) || empty($text_struct['deps'])) return NULL;
  $deps = $text_struct['deps'];

  $path = array();

  while ($master != $servant) {
    if (!isset($deps[$master]) || !isset($deps[$servant])) return NULL;
    $dh = $deps[$servant]['head'];
    if ($dh == 0) return NULL;
    $path[] = $deps[$servant]['role'];
    $servant += $dh;
  }

  return $path;
  
}



