<?php

/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */



/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_mw_automaton($op, $ap) {

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
          'db_name' => db_prefix_tables('{wisski_textanly_mw_automaton}'),
          'pos_mappings' => '0.2 NE y gs',
        );
        $patterns = "0.9 (p|t|rc|x)?g+(v(uz)?)?s\n0.8 (p|t|rc|x)?s,g+\n0.6 (p|t|rc|x)?g+\n0.7 (p|t|rc|x)?s\n0.6 [ptrx]";
      } else {
        $patterns = "";
        foreach ($ap->settings['patterns'] as $p) {
          $patterns .= $p['rank'];
          $patterns .= " ";
          $patterns .= $p['regex'];
          $patterns .= "\n";
        }
      }	    

      $fieldset = array();
      $fieldset['class'] =  array(
        '#type' => 'select',
        '#title' => t('Group'),
        '#multiple' => false ,
        '#options' => $pb_groups,
        '#default_value' => $ap->settings['class'],
      );
      $fieldset['db_name'] = array(
        '#type' => 'textfield',
        '#title' => t('Database table name'),
        '#default_value' => $ap->settings['db_name'],
      );
      $fieldset['patterns'] = array(
        '#type' => 'textarea',
        '#title' => t('Rankings and patterns'),
        '#default_value' => $patterns,
        '#description' => t('Each line contains a pattern (e.g. s = surname, g = givenname, v/u/z/d = name addendum, "," = comma, + = multiple, ? = optional) preceeded by a factor and a whitespace.'),
      );    
      $fieldset['pos_mappings'] = array(
        '#type' => 'textarea',
        '#title' => t('Part-of-speech to category mapping'),
        '#default_value' => $ap->settings['pos_mappings'],
        '#description' => t('Each line contains a factor, POS tag, whether lemma is present (y/n), and categories it maps to, separated by whitespace.'),
      );    

      return $fieldset;

    case 'submit':

      $patterns = array();
      $lines = preg_split('/\r\n|\r|\n/', $ap->settings['patterns']);
      foreach ($lines as $line) {
        list($rank, $regex) = explode(" ", $line, 2);
        $rank = trim($rank);
        $regex = trim($regex);
        if ($rank == '' || $regex == '') continue;
        $patterns[] = array('rank' => $rank, 'regex' => $regex);
      }

      $ap->settings['patterns'] = $patterns;

      return $ap->settings;

    case 'key_info':
      return array(
        'input' => array(
          'tokens',
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



/** Annotate text ranges that follow a certain token pattern
* This is the analyse operation for analysis component type vocab
* 
* First marks all tokens according to a list of words, then
* executes a regex on that annotations. Tokens that are in a match will be
* annotated
*
* @author Martin Scholz
*
*/
function wisski_textanly_analyse_mw_automaton($analyser, $text_struct, $ticket) {
 
  if (!isset($text_struct['annos'])) $text_struct['annos'] = array();
  $lang = $text_struct['lang'];
  $annos = $text_struct['annos'];
  
  if (!isset($text_struct['tokens'])) return $text_struct;

  $settings = $analyser->settings;
  $patterns = $settings['patterns'];
  $pos_mappings = trim($settings['pos_mappings']);
  
  // go thru all tokens and annotate with possible class
  $tokens_len = count($text_struct['tokens']);
  $findings = array_fill(0, $tokens_len, NULL);

  for ($token_pos = 0; $token_pos < $tokens_len; $token_pos++) {
    $token = $text_struct['tokens'][$token_pos];
    $lemma = isset($text_struct['lemmata']) ? $text_struct['lemmata'][$token_pos] : '';

    // for each token get the possible name parts
    // first, get all parts where the language matches
    $finding = array();
    if (empty($lemma)) {
      $rows = db_query("SELECT * FROM %s WHERE name = '%s' AND lang = '%s' ORDER BY rank", $settings['db_name'], $token[0], $lang);
    } else {
      $rows = db_query("SELECT * FROM %s WHERE (name = '%s' OR name = '%s') AND lang = '%s' ORDER BY rank", $settings['db_name'], $token[0], $lemma, $lang);
    }
    while ($row = db_fetch_array($rows)) {
      if ($token[0] != $row['name'] && (empty($lemma) || $lemma != $row['name'])) continue;  // for case sensitivity and diacritics, the db ignores them
      $finding[] = $row;
    }

    // second, get all parts where language does not match
    if (empty($lemma)) {
      $rows = db_query("SELECT * FROM %s WHERE name = '%s' AND lang != '%s' ORDER BY rank", $settings['db_name'], $token[0], $lang);
    } else {
      $rows = db_query("SELECT * FROM %s WHERE (name = '%s' OR name = '%s') AND lang != '%s' ORDER BY rank", $settings['db_name'], $token[0], $lemma, $lang);
    }
    while ($row = db_fetch_array($rows)) {
      if ($token[0] != $row['name']) continue;  // for case sensitivity
      $finding[] = $row;
    }
    
    // third, get suffixes and test them (we assume suffixes are always lang dependent)
    $rows = db_query("SELECT * FROM %s WHERE name LIKE '-%%' AND CHAR_LENGTH(name) <= %d AND lang = '%s' ORDER BY rank", $settings['db_name'], min(mb_strlen($token[0]), empty($lemma) ? 300 : mb_strlen($lemma)), $lang);
    while ($row = db_fetch_array($rows)) {
      $suffix = mb_substr($row['name'], 1);
      $token_suffix = mb_substr($token[0], - mb_strlen($suffix));
      if ($suffix != $token_suffix) continue;  // either the suffix is not in the token or the suffix is not at its end
      $finding[] = $row;
    }

    // fourth, add for certain pos and unknown lemma
    if (!empty($pos_mappings) && isset($text_struct['pos']) && !empty($text_struct['pos'][$token_pos])) {
      $pos = preg_quote($text_struct['pos'][$token_pos]);
      $lemmayn = $lemma != '' ? 'y' : 'n';
      if (preg_match_all("/^ *(0|1|0\\.[0-9]+) +$pos +$lemmayn +(.+) *$/mu", $pos_mappings, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
          foreach (str_split(trim($match[2])) as $t) {
            $found = FALSE;
            foreach ($finding as &$f) {
              if ($f['type'] == $t) {
                $f['rank'] += $match[1];
                $found = TRUE;
              }
            }
            if (!$found) {
              $finding[] = array('name' => $token[0], 'type' => $t, 'offset' => 0, 'rank' => $match[1], 'lang' => '', 'gender' => '');
            }
          }
        }
      }
    }
if ($token[0] == 'Juden') dpm($finding);
    
    // store each possible finding in the grand findings table
    foreach ($finding as $f) {
      // annotate the right token with the class
      // it doesnt matter if we get out of range, it will be ignored
      if ($findings[$token_pos + $f['offset']] === NULL) $findings[$token_pos + $f['offset']] = array();
      $findings[$token_pos + $f['offset']][] = $f;
    }

  }

  // go through all findings
  $start = 0;
  $end = 0;

  while ($end < $tokens_len) {

    // if we don't find anything, go ahead
    if ($findings[$end] == NULL) {
      $end++;
      $start = $end;
      continue;
    }

    // test each token substring with each pattern
    $find_patt = array('' => array());
    for ($offset = $end; $offset >= $start; $offset--) {

      $anno = array('rank' => 0);

      // construct finding patterns from 
      $new_find_patt = array();
      foreach ($findings[$offset] as $f) {
        foreach ($find_patt as $fp => $info) {
          array_unshift($info, $f);
          $new_find_patt[$f['type'][0] . $fp] = $info;
        }
      }
      $find_patt = $new_find_patt;

      foreach ($patterns as $pattern) {
        $p = $pattern['regex'];
        foreach ($find_patt as $fp => $info) {
          if (preg_match("/^$p$/u", $fp)) {
            $rank = 0.0;
            foreach ($info as $i) $rank += $i['rank'];
            $rank /= sqrt(strlen($fp));
            $rank *= ($end - $offset + 1);// sqrt($end - $offset + 1); 
            $rank *= $pattern['rank'];
            if ($rank > $anno['rank']) {
              $genders = array_reduce($info, function(&$r, $a) { $r[$a['gender']]++; return $r; }, array('' => 0));
              if (count($genders) > 1) unset($genders['']);
              arsort($genders);
              $a_start = $text_struct['tokens'][$offset][1];
              $a_end = $text_struct['tokens'][$end][2];  // $token_pos is last finding pos + 1!
              $anno = array(
                  'class' => $settings['class'],
                  'rank' => $rank,
                  'range' => array($a_start, $a_end),
                  'args' => array(
                    'pattern' => $fp,
                    'gender' => key($genders),
                  ),
                  '_origin' => 'wisski_textanly_mw_automaton',
                  );
            }
          }
        }
      }

      if ($anno['rank'] > 0) {
        if (isset($settings['factor'])) $anno['rank'] *= $settings['factor'];
        $annos[] = $anno;
      }
    }

    // reposition the end pointer
    // start stays the same, as we might build bigger terms
    $end++;

  }
  
  $text_struct['annos'] = $annos;
  return $text_struct;

}



