<?php

/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */



/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_person($op, $ap) {

  switch ($op) {

    case 'edit':
      
      if (empty($ap->settings)) {
        $ap->settings = array(
          'cat' => 'person',
          'class' => 'ecrm:E21_Person',
          'class_manual' => '',
          'db_name' => db_prefix_tables('{wisski_textanly_person}'),
          'pos_mappings' => '0.2 NE y gs',
          'patterns' => array(
          // 0.9 (p|t|rc|x|)(g+(v(uz)?)?s) 1\n0.8 (p|t|rc|x|)(s,g+) 1\n0.6 (p|t|rc|x|)(g+) 1\n0.7 (p|t|rc|x|)(s) 1\n0.6 [ptrx]
            array(
              'rank' => 0.9,
              'regex' => '[ptrx]?g+(vuz|v|z|vd)?s',
              'mgroup' => 0,
            ),
            array(
              'rank' => 0.8,
              'regex' => '[ptrx]?s,g+',
              'mgroup' => 0,
            ),
            array(
              'rank' => 0.6,
              'regex' => '[ptrx]?g+',
              'mgroup' => 0,
            ),
            array(
              'rank' => 0.7,
              'regex' => '[ptrx]?s',
              'mgroup' => 0,
            ),
            array(
              'rank' => 0.6,
              'regex' => '[ptrx]',
              'mgroup' => 0,
            ),
          ),
          '_person_names' => array(
            'surname' => array('', '! s!', ''),
            'givenname' => array('', '! g!', ''),
            'name' => array('', '! gvuzdsIDAS!', ''),
            'profession' => array('', '! p!', ''),
            'title' => array('', '! t!', ''), 
          ),
          '_person_classes' => array('ecrm:E21_Person'),
        );
      }


      $fieldset = array();
      $fieldset['detection'] = array(
        '#type' => 'fieldset',
        '#title' => t('Person Name Detection'),
      );

      module_load_include('inc', 'wisski_textanly', 'wisski_textanly.analyse.mw_automaton');
      $fieldset['detection'] += wisski_textanly_admin_analyse_mw_automaton($op, $ap);

      module_load_include('inc', 'wisski_textanly', 'wisski_textanly.analyse.common');
      list($classes, $groups) = _wisski_textanly_get_classes_list();
      $class_options = array_merge(array('' => t('<Manual>')), $groups, $classes);


      $is_classes_manual = in_array('', $ap->settings['_person_classes']) || count(array_intersect($ap->settings['_person_classes'], array_keys($class_options))) != count($ap->settings['_person_classes']);
      
      $names = '';
      foreach ($ap->settings['_person_names'] as $k => $v) {
        $names .= "$k: " . join('', $v);
        $names  .= "\n";
      }

      $fieldset['enrichment'] = array(
        '#type' => 'fieldset',
        '#title' => t('Adjust and Enrich Person Annotation'),
      );
      $fieldset['enrichment']['_person_classes'] = array(
        '#type' => 'select',
        '#title' => t('Person classes'),
        '#multiple' => TRUE,
        '#default_value' => $ap->settings['_person_classes'],
        '#options' => $class_options, 
      );
      $fieldset['enrichment']['_person_classes_manual'] = array(
        '#type' => 'textfield',
        '#title' => t('Manual classes'),
        '#default_value' => join(' ', $is_classes_manual ? $ap->settings['_person_classes'] : (array) $ap->settings['_person_classes_manual']),
        '#description' => t('This field is only considered if field Person classes is %m. You can set classes manually, separated by whitespace. RDF prefixes are expanded.', array('%m' => t('<Manual>'))),
      );
      $fieldset['enrichment']['_person_names'] = array(
        '#type' => 'textarea',
        '#title' => t('Annotation argument definitions'),
        '#default_value' => $names,
        '#description' => t('Here you can define annotation arguments that are composed from the pattern information or other arguments. Each line defines one argument. The line starts with the argument name followed by ": " and the definition. In the definition the patterns enclosed by "%" or "!" will be replaced. For "%", the pattern content is interpreted as a single argument and its value will be concatenated (if an array) and used as replacement. For "!", the pattern content is interpreted as a set of pattern characters and the tokens of the annotation that match one of the chars are concatenated and used for replacement. The first character after the !/% not part of the pattern but is the delimiter used to concatenate.'),
      );

      return $fieldset;

    case 'submit': 
      
      $ap->settings += $ap->settings['detection'];
      $ap->settings += $ap->settings['enrichment'];
      unset($ap->settings['detection']);
      unset($ap->settings['enrichment']);

      module_load_include('inc', 'wisski_textanly', 'wisski_textanly.analyse.mw_automaton');
      $ap->settings += wisski_textanly_admin_analyse_mw_automaton($op, $ap);

      if (in_array('', $ap->settings['_person_classes'])) {
        $ap->settings['_person_classes'] = preg_split('/ +/u', $ap->settings['_person_classes_manual'], PREG_SPLIT_NO_EMPTY);
      }

      $names = array();
      $has_exc = FALSE;
      if (preg_match_all('/^(\S+): (.*)$/um', $ap->settings['_person_names'], $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
          if (strpos($m[2], '!') !== FALSE) $has_exc = TRUE;
          $names[$m[1]] = preg_split('/(%[^%]*%|![^!]*!)/u', $m[2], 0, PREG_SPLIT_DELIM_CAPTURE);
        }
      }
      $ap->settings['_person_names'] = $names;
      $ap->settings['_person_names_uses_pattern'] = $has_exc;
      $ap->settings['cat'] = 'person';  // also see edit op

      return $ap->settings;

    case 'key_info':
      return array(
        'input' => array(
          'tokens',
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
function wisski_textanly_analyse_person($analyser, $text_struct, $ticket) {
  
  // first, we run the multiword pattern analyser
  include_once(dirname(__FILE__) . '/wisski_textanly.analyse.common.inc');
  include_once(dirname(__FILE__) . '/wisski_textanly.analyse.mw_automaton.inc');
  $text_struct = wisski_textanly_analyse_mw_automaton($analyser, $text_struct, $ticket);
 
  // the rest works on existing annotations
  // so if there are none, we are done
  if (!isset($text_struct['annos'])) return $text_struct;

  $classes = $analyser->settings['_person_classes'];
  foreach ($classes as $i => $c) {
    $classes[$i] = wisski_store_getObj()->wisski_ARCAdapter_delNamespace($c);
  }
  
  $names = $analyser->settings['_person_names'];
  $names_uses_pattern = $analyser->settings['_person_names_uses_pattern'];

  // for every anno that is considered a person class we do
  // a) determine the gender by adding up gender information of each token
  // b) determine the name parts by stripping 
  $has_gender = array();
  $gender_pattern = array();
  $gender_query = array();
  foreach ($text_struct['annos'] as $i => $anno) {
    if (!in_array($anno['class'], $classes)) continue;
    
    // gender

    if (isset($anno['args']) && isset($anno['args']['gender']) && is_string($anno['args']['gender'])) {
      // we can use the gender information from the multiword pattern
      $has_gender[] = $i;

    } elseif (isset($anno['args']) && isset($anno['args']['gender']) && is_array($anno['args']['gender'])) {
      // we can use the gender information from the multiword pattern
      $gender_pattern[] = $i;

    } elseif (!isset($anno['args']) || !array_key_exists('gender', $anno['args'])) {
      // we have to query gender info
      $gender_query[] = $i;

    }

    // name parts

    if (isset($anno['args']['pattern'])) {
      $name_pattern[] = $i;
    }

  }
  

  // -- gender --
  
  // first we compute the gender where we have pattern and gender info
  foreach ($gender_pattern as $i) {
    $anno = $text_struct['annos'][$i];

    $gender_count = array_count_values($anno['args']['gender']);

    if (!empty($gender_count)) {

      if (count($gender_count) == 1) {

        $gender = $gender_count[0];

      } else {
        
        // the genders in 'gender' are in pattern order.
        // the first gender in the pattern is doubled => higher rank.
        // this reflects the heuristic that the first token with gender info
        // strongly indicates the gender, being either the first givenname
        // or a gender-aware title/profession
        $gender_count[$anno['args']['gender'][0]]++;

        // we want the array of most frequent genders
        $genders = array_keys($gender_count, max($gender_count));
        
        if (count($genders) == 1) {

          $gender = $genders[0];

        } else {
          
          // the genders in 'gender' are in pattern order.
          // so we go through the pattern,
          // picking the highest-ranked gender that appears first in the pattern
          // this implements the heuristic that the first token with gender
          // info is either the first givenname or a title/profession/etc. that
          // both have a strong indication (the gender is also doubled, s. above)
          foreach ($anno['args']['gender'] as $g) {
            if (in_array($g, $genders)) {
              $gender = $g;
              break;
            }
          }

        }
      }

      $anno['args']['gender'] = $gender;

    }

    // we add it to the list of all annos that have a gender
    // so that we can look it up in the next step
    $has_gender[] = $i;

    $text_struct['annos'][$i] = $anno;
  
  }

  // then we try to deduce a gender for annos where there is no gender info
  foreach ($gender_query as $i) {
    $anno = $text_struct['annos'][$i];
    
    // this is the default: set the gender to undefined
    $anno['args']['gender'] = NULL;

    // we assume that this anno is equal/similar to the best-fitting
    // lexicon-based anno.
    // first, we compare the anno to all annos with gender info and same class.
    // the genders of the ones with highest overlap are counted, the highest 
    // count wins.
    $overlap = 0.25; // we should have at least 25% overlap
    $genders = array();
    foreach ($has_gender as $j) {
      $annoj = $text_struct['annos'][$j];
      if ($annoj['class'] == $anno['class']) {
        list($rel, , $ol) = _wisski_textanly_calculate_overlap($anno['range'][0], $anno['range'][1], $annoj['range'][0], $annoj['range'][1]);
        if ($ol > $overlap) {
          $overlap = $ol;
          $genders = array();
          $genders[$annoj['args']['gender']] = 1;
        } elseif ($ol == $overlap) {
          if (isset($genders[$annoj['args']['gender']])) {
            $genders[$annoj['args']['gender']]++;
          } else {
            $genders[$annoj['args']['gender']] = 1;
          }
        }
      }
    }

    // get the highest rated gender
    // if there are more than one or none, we leave it undefined :(
    if (!empty($genders)) {
      $genders = array_keys($genders, max($genders));
      if (count($genders) == 1) {
        $anno['args']['gender'] = $genders[0];
      }
    }

    $text_struct['annos'][$i] = $anno;

  }

  // -- end gender --


  // -- name parts --

  if (!empty($names)) {

    foreach ($name_pattern as $i) {
      $anno = $text_struct['annos'][$i];
      
      // we calculate the first token and length of the whole pattern for 
      if ($names_uses_pattern) {
        if (!isset($anno['tokens'])) {
          $anno['tokens'] = _wisski_textanly_get_tokens_for_anno($text_struct, $anno);
          if ($anno['tokens'] == NULL) continue;
        }

        if (isset($anno['args']['whole_pattern'])) {
          // this only works if the actual annotation pattern is the only or
          // first occurence of this pattern within the whole pattern
          $anno_pattern = $anno['args']['whole_pattern'];
          $offset = $anno['tokens'][0] - mb_strpos($anno_pattern, $anno['args']['pattern']);
          $length = mb_strlen($anno_pattern);
        } else {
          $anno_pattern = $anno['args']['pattern'];
          $offset = $anno['tokens'][0];
          $length = mb_strlen($anno_pattern);
        }
      }

      // we produce each name schema
      foreach ($names as $label => $pattern) {
        // we only set the name if it is not already there
        if (isset($anno['args'][$label])) continue;

        // we accumulate the strings in $name
        $name = $pattern[0];
        $c = count($pattern);
        for ($p = 1; $p < $c; $p += 2) {
          
          if (mb_strlen($pattern[$p]) == 2) {
          
            // escape sequence
            $name .= $pattern[$p][0];
          
          } else {
            // a real pattern

            $type = mb_substr($pattern[$p], 0, 1);
            $delim = mb_substr($pattern[$p], 1, 1);
            $arg = mb_substr($pattern[$p], 2, -1);
            
            if ($type == '!') {
              // "!": take all tokens of the anno pattern that have a char that
              // occurs in $arg. 
              $first_add = TRUE;
              for ($o = 0; $o < $length; $o++) {
                if (mb_strpos($arg, mb_substr($anno_pattern, $o, 1)) !== FALSE) {
                  if (!$first_add) $name .= $delim;
                  else $first_add = FALSE;
                  $name .= $text_struct['tokens'][$o + $offset][0];
                }
              }
            } else {
              if (isset($anno['args'][$arg]) && !empty($anno['args'][$arg])) {
                $name .= join($delim, (array) $anno['args'][$arg]);
              }
            }

          }
          $name .= $pattern[$p + 1];
        }
        $name = preg_replace('/ +/u', ' ', trim($name));
        
        if ($name != '') $anno['args'][$label] = $name;
        
      }
      
      $text_struct['annos'][$i] = $anno;

    }
    
  }
  
  return $text_struct;

}



