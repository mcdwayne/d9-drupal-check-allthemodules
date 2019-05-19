<?php

/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */

define('WISSKI_TEXTANLY_MAGICYEAR', 1900);

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
  
  if (!isset($text_struct['text'])) return $text_struct;
  if (!isset($text_struct['annos'])) $text_struct['annos'] = array();
  $settings = $analyser->settings;

  $settings['rank_length_factor'] = 0.03;
  $settings['month_names'] = array(
    'Jan' => 1,
    'Jän' => 1,
    'Feb' => 2,
    'Mär' => 3,
    'Mar' => 3,
    'Apr' => 4,
    'Mai' => 5,
    'May' => 5,
    'Jun' => 6,
    'Jul' => 7,
    'Aug' => 8,
    'Sep' => 9,
    'Okt' => 10,
    'Oct' => 10,
    'Nov' => 11,
    'Dez' => 12,
    'Dec' => 12,
  );


  $settings['modify']['front'] = array(
    'rank' => 1,
    'op' => 'portion',
    'args' => array(
      'default' => array(0.33, 0),
    ),
  );
  $settings['modify']['mid'] = array(
    'rank' => 1,
    'op' => 'portion',
    'args' => array(
      'default' => array(0.33, 0.33),
    ),
  );
  $settings['modify']['back'] = array(
    'rank' => 1,
    'op' => 'portion',
    'args' => array(
      'default' => array(0.33, 0.66),
    ),
  );
  $settings['modify']['around'] = array(
    'rank' => 1,
    'op' => 'stretch',
    'args' => array(
      'default' => array(-0.3, 0.3),
      'day' => array(-1, 1),
      'year' => array(-1, 1),
    ),
  );

  $formats = array(
    array(
      'label' => 'Day Month Year',
      'pattern' => array(
        '((?<day>[0-2]?\d|30|31)(\.|th|rd|st|nd)?\s+)?(?<month>Jan(\.|uary)|Feb(\.|ruary)|Mar(\.|ch)|Apr(\.|il)|May|Jun[\.e]|Jul[\.y]|Aug(\.|ust)|Sep(\.|tember)|Oct(\.|ober)|Nov(\.|ember)|Dec(\.|ember))(\s+(?<year>\'?\d+))',
      ),
      'callback' => '_wisski_textanly_analyse_datetime_callback_validate_date',
      'lang' => 'en',
    ),
    array(
      'label' => 'Tag Monat Jahr',
      'pattern' => array(
        '([Dd]e[rsnm]\s+)?((?<day>[0-2]?\d|30|31|)\.?)?\s*(?<month>Jan(\.|uar)|Jän(\.|ner)|Feb(\.|ruar)|Mär(\.|z)|Apr(\.|il)|Mai|Jun[\.i]|Jul[\.i]|Aug(\.|ust)|Sep(\.|tember)|Okt(\.|ober)|Nov(\.|ember)|Dez(\.|ember)|1[0-2]\.|0?\d\.)(\s*(?<year>\'?\d+))',
      ),
      'callback' => '_wisski_textanly_analyse_datetime_callback_validate_date',
      'lang' => 'de',
    ),
    array(
      'label' => 'von ... bis ...',
      'pattern' => array(
        '([Vv]o[nm]|[Aa]b|[Ss]eit)?\s*',
        array(
          'name' => 'simple1',
          'groups' => 'simple',
        ),
        '\s+bis\s+|\s*-\s*',
        array(
          'name' => 'simple2',
          'groups' => 'simple',
        ),
      ),
      'callback' => '_wisski_textanly_analyse_datetime_callback_combine',
      'lang' => 'de',
    ),
    array(
      'label' => 'zwischen ... und ...',
      'pattern' => array(
        '[Zz]w(\.|ischen)\s+',
        array(
          'name' => 'simple1',
          'groups' => 'simple',
        ),
        '\s+und\s+',
        array(
          'name' => 'simple2',
          'groups' => 'simple',
        ),
      ),
      'callback' => '_wisski_textanly_analyse_datetime_callback_combine', 
      'lang' => 'de',
    ),
    array(
      'label' => 'Anfang ...',
      'pattern' => array(
        '([Dd]e[rnm]\s+)?Anfang\s+',
        array(
          'name' => 'span',
          'groups' => 'simple',
        ),
      ),
      'callback' => array('_wisski_textanly_analyse_datetime_callback_modify', 'front'),
      'lang' => 'de',
    ),
    array(
      'label' => 'um ...',
      'pattern' => array(
        '([Uu]m|[Cc]a\.?)\s+',
        array(
          'name' => 'span',
          'groups' => 'simple',
        ),
      ),
      'callback' => array('_wisski_textanly_analyse_datetime_callback_modify', 'around'),
      'lang' => 'de',
    ),
  );
  
  $spans = array();
  while ($format = array_shift($formats)) {
    if (   !isset($text_struct['lang'])
        || !isset($format['lang'])
        || strpos($text_struct['lang'], $format['lang']) === 0) {
      $spans = _wisski_textanly_analyse_datetime_match_format($text_struct['text'], $format, $spans, $settings);
    }
  }
  
  foreach ($spans as $span) {
    $span['type'] = 'timespan';
    $span['class'] = $settings['class'];
    $span['rank'] += ($span['range'][1] - $span['range'][0]) * $settings['rank_length_factor'];
    $span['_origin'] = 'wisski_textanly_datetime';
    $text_struct['annos'][] = $span;
  }

  return $text_struct;

}


function _wisski_textanly_analyse_commons_span_matcher($text, $spans, $pattern) {
  
  $l = count($pattern);
  
  if ($l == 0) return array();

  if ($l == 1) {
    if (!preg_match_all('/' . $pattern[0] . '/us', $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
      return array();
    } else {
      $finished = array();
      foreach ($matches as $match) {
        // workaround: preg returns the offset in bytes, not chars!
        $range = array(mb_strlen(substr($text, 0, $match[0][1])));
        $range[1] = $range[0] + mb_strlen(trim($match[0][0]));
        $vars = array();
        foreach ($match as $key => $val) {
          if (is_string($key)) $vars[$key] = $val[0];
        }
        $finished[] = array('range' => $range, 'vars' => $vars);
      }
      return $finished;
    }
  }

  $finished = array();
  $matches = array(array('pattern' => $pattern, 'vars' => array()));
  
  foreach ($spans as $si => $span) {

    foreach ($matches as $mi => $match) {
      
      $regex_patt = $pattern[0];
      $span_patt = $pattern[1];

      if (count(array_diff((array) $span['groups'], (array) $span_patt['groups'])) != 0) {
        
        $start = isset($match['range']) ? $match['range'][1] : 0;
        $start_cond = $start == 0 ? '/' : '/^';
        $end = $span['range'][0];

        if (preg_match($start_cond . $regex_patt . '$/us', mb_substr($text, $start, $end), $regex_matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
          
        }

      }

    foreach  



  }

  return $finished;
//  return array of array('range' => array($start, $end), 'vars' => $matched_vars_and_spans);

}


function _wisski_textanly_analyse_datetime_match_format($text, $format, $spans, $settings, $start) {
  
  $args = (array) $format['callback'];
  $callback = array_shift($args);
  $l = count($pattern);
  


  for ($i = 1; $i < $l; $i += 2) {
    
    $regex = $format['pattern'][$i-1];
    $span_pattern = $format['pattern'][$i];

    
    
    if (preg_match_all("/$regex/us", $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {

      foreach ($matches as $match) {
        
        $vars = array();
        
        // check the placeholders
        // we go through the already existing spans and look for
        // those already
        if (isset($format['placeholders'])) {
          foreach ($format['placeholders'] as $var => $info) {
            $match_len = mb_strlen(trim($match[$var][0]));
            $match_pos = mb_strlen(substr($text, 0, $match[$var][1]));
            
            $vars[$var] = array();
            $groups == (array) $info['groups'];
            foreach ($spans as $i => $span) {
              if ($match_pos = $span['range'][0]) {
                $span_len = $span['range'][1] - $span['range'][0];
                if ($match_len == $span_len) {
                  if ($groups == NULL || array_intersect($groups, $span['groups'])) {
                    if (   !isset($format['lang'])
                        || !isset($span['lang'])
                        || strpos($span['lang'], $format['lang']) === 0) {
                      $vars[$var][] = $i;
                    }
                  }
                }
              }
            }
            unset($match[$var]);
          }
        }
        
        // workaround: preg returns the offset in bytes, not chars!
        $range = array(mb_strlen(substr($text, 0, $match[0][1])));
        $range[1] = $range[0] + mb_strlen(trim($match[0][0]));

        foreach ($match as $key => $val) {
          if (is_string($key)) $vars[$key] = $val[0];
        }
        
        $my_args = array($settings, $format, $spans, $range, $vars, $args);
        $spans = call_user_func_array($callback, $my_args);

      }
    } 
dpm($spans);    
  }
  
  return $spans;

}


function _wisski_textanly_analyse_datetime_callback_validate_date($settings, $format, $spans, $range, $vars, $args) {
  
  // get day, month, year: the three ingredients that
  // this function checks
  $day = isset($vars['day']) ? (int) $vars['day'] : NULL;
  $month = isset($vars['month']) ? $vars['month'] : NULL;
  $year = isset($vars['year']) ? $vars['year'] : NULL;
  if (is_string($month)) {
    $month = $settings['month_names'][mb_substr($month, 0, 3)];
  }
  if ($year[0] == "'") {
    $year = substr($year, 1);
    if (strlen($year) != 2) return $spans;
    if ($year < 20) $year = "20$year";
    else $year = "19$year";
  }

  $span = array(
    'range' => $range,
    'rank' => $settings['initial_rank'],
    'groups' => array('simple'),
    'lang' => $format['lang'],
  );

  // we have to check 5 sensible possibilities:
  // day+month+year
  // day+month
  // month+year
  // month
  // year

  if ($day !== NULL && $month !== NULL) {
    // day+month+year
    // day+month

    // if there is no year we fake a leap year
    // to see if its possibly a valid date
    $fakeyear = $year === NULL;
    if ($fakeyear) $year = 2000;

    $is_valid = checkdate($month, $day, $year);

    if (!$is_valid) return $spans;  // this is no valid date

    $span['rank'] += $fakeyear ? 2 : 3;
    if ($fakeyear) {
      $span['from'] = sprintf('X-%02d-%02d', $month, $day);
      $span['to'] = sprintf('X0-%02d-%02d', $month, $day);
    } else {
      $span['from'] = sprintf('%04d-%02d-%02d', $year, $month, $day);
      $span['to'] = sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
    $span['granularity'] = 'day';

  } elseif ($month !== NULL && $year !== NULL) {
    // month+year
    
    $maxday = 31;
    while (!checkdate($month, $maxday, $year)) $maxday--;

    $span['rank'] += 2;
    $span['from'] = sprintf('%04d-%02d-01', $year, $month);
    $span['to'] = sprintf('%04d-%02d-%02d', $year, $month, $maxday);
    $span['granularity'] = 'month';
    
  } elseif ($month !== NULL) {
    // month
    
    $maxday = 31;
    while (!checkdate($month, $maxday, 2001)) $maxday--;
    
    $span['rank'] += 1;
    $span['from'] = sprintf('X-%02d-01', $year, $month);
    $span['to'] = sprintf('X0-%02d-%02d', $year, $month, $maxday);
    $span['granularity'] = 'month';
    
  } elseif ($year !== NULL) {
    // year
    
    $span['rank'] += 1;
    $span['from'] = sprintf('%04s-01-01', $year);
    $span['to'] = sprintf('%04s-12-31', $year);
    $span['granularity'] = 'year';

  }

  $spans[] = $span;

  return $spans;

}


function _wisski_textanly_analyse_datetime_callback_combine($settings, $format, $spans, $range, $vars, $args) {
  

  return $spans;

}


function _wisski_textanly_analyse_datetime_callback_modify($settings, $format, $spans, $range, $vars, $args) {

  $modify = $args[0];
  if (isset($settings['modify'][$modify])) {
    $modify = $settings['modify'][$modify];
  } else {
    return $spans;
  }
  
  foreach ($vars['span'] as $i) {
    $span = $spans[$i];

    $from_to = call_user_func('_wisski_textanly_analyse_datetime_callback_modify_' . $modify['op'], $span['from'], $span['to'], $span['granularity'], $modify['args']);
    if ($from_to == NULL) continue;

    $newspan = $span;
    $newspan['range'] = $range;
    $newspan['rank'] += 1;
    $newspan['from'] = $from_to[0];
    $newspan['to'] = $from_to[1];
    $newspan['groups'][] = 'modified';
    $newspan['modifies'] = $i;
    $newspan['lang'] = $format['lang'];
  
    $spans[] = $newspan;

  }

  return $spans;

}


function _wisski_textanly_analyse_datetime_callback_modify_portion($from, $to, $gran, $args) {
  
  if (!is_array($args)) return NULL;
  if (isset($args[$gran])) {
    $args = $args[$gran];
  } elseif (isset($args['default'])) {
    $args = $args['default'];
  } else {
    return NULL;
  }
  
  list($from, $to, $mode) = _wisski_textanly_analyse_datetime_handle_magicyears($from, $to);
  
  $ts_to = date_timestamp_get(date_create_from_format('Y-m-d', $to));
  $ts_from = date_timestamp_get(date_create_from_format('Y-m-d', $from));
  
  $duration = $ts_to - $ts_from;
  $new_duration = (int) ($duration * $args[0]);
  $new_ts_from = $ts_from + (int) ($duration * $args[1]);
  $new_ts_to = $new_ts_from + $new_duration;

  $new_from = date_format(date_create("@$new_ts_from"), 'Y-m-d');
  $new_to = date_format(date_create("@$new_ts_to"), 'Y-m-d');
  
  list($new_from, $new_to, $mode) = _wisski_textanly_analyse_datetime_handle_magicyears($new_from, $new_to, $mode);

  return array($new_from, $new_to);

}


function _wisski_textanly_analyse_datetime_callback_modify_stretch($from, $to, $gran, $args) {
  
  if (!is_array($args)) return NULL;
  if (isset($args[$gran])) {
    $args = $args[$gran];
  } elseif (isset($args['default'])) {
    $args = $args['default'];
  } else {
    return NULL;
  }
  
  list($from, $to, $mode) = _wisski_textanly_analyse_datetime_handle_magicyears($from, $to);

  $ts_from = date_timestamp_get(date_create_from_format ('Y-m-d', $from));
  $ts_to = date_timestamp_get(date_create_from_format('Y-m-d', $to));
  
  $amount = 3600 * 24; // gran = day, default
  if ($gran == 'month') {
    $amount = 3600 * 24 * 30; // average month
  } elseif ($gran == 'year') {
    $amount = 3600 * 24 * 30 * 12;
  } elseif ($gran == 'years') {
    // count the number of trailing 0's
    // this will be the granularity
    $from_year = (int) substr($from, 0, strpos($from, '-'));
    $to_year = (int) substr($to, 0, strpos($to, '-'));
    $diff = $to_year - $from_year;
    $amount = 10;
    while ($diff % $amount = 0) $amount *= 10;
    $amount *= 360 * 24 * 30 * 12;  // 360 is correct, as we must divide by 10
  }
  
dpm(array($ts_from, $ts_to ,$amount , $args),'b');
  $new_ts_from = $ts_from + ($amount * $args[0]); 
  $new_ts_to = $ts_to + ($amount * $args[1]); 
  
  $new_from = date_format(date_create("@$new_ts_from"), 'Y-m-d');
  $new_to = date_format(date_create("@$new_ts_to"), 'Y-m-d');
  
  list($new_from, $new_to, $mode) = _wisski_textanly_analyse_datetime_handle_magicyears($new_from, $new_to, $mode);

  return array($new_from, $new_to);

}


function _wisski_textanly_analyse_datetime_handle_magicyears($from, $to, $mode = NULL) {
  
  if ($mode === NULL) {
    if ($from[0] == 'X') {
      // replace by a magic year
      $fakeyear = TRUE;
      $from = WISSKI_TEXTANLY_MAGICYEAR . strstr($from, '-');
      $to = (WISSKI_TEXTANLY_MAGICYEAR + (int) substr($to, 1, strpos($to, '-'))) . strstr($to, '-');
      return array($from, $to, 'relative');
    } elseif ($from[0] == '-') {
      // the date_parse function does not handle negative dates
      $negative = TRUE;
      $from = substr($from, 1);
      $negative = 0 - (int) substr($from, strpos($from, '-'));
      $from = WISSKI_TEXTANLY_MAGICYEAR . strstr($from, '-');
      $to = (WISSKI_TEXTANLY_MAGICYEAR - $negative - (int) substr($to, 0, strpos($from, '-', 1))) . substr($to, strpos($to, '-', 1));
      return array($from, $to, 'negative');
    }
  } elseif ($mode == 'relative') {
    
    $diff = WISSKI_TEXTANLY_MAGICYEAR - substr($from, 0, strpos($from, '-'));
    $diff -= WISSKI_TEXTANLY_MAGICYEAR - substr($to, 0, strpos($to, '-'));

    $from = 'X' . strstr($from, '-');
    $to = "X$diff" . strstr($to, '-');
    return array($from, $to, 'relative');

  } elseif ($mode == 'negative') {
    
    $from = ($negative - WISSKI_TEXTANLY_MAGICYEAR + (int) substr($from, 0, strpos($from, '-'))) . strstr($from, '-');
    $to = ($negative - WISSKI_TEXTANLY_MAGICYEAR + (int) substr($to, 0, strpos($to, '-'))) . strstr($to, '-');
    return array($from, $to, 'negative');

  }

  return array($from, $to, 'normal');

}
