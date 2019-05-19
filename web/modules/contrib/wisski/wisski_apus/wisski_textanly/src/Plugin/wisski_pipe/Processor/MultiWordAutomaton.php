<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Plugin\wisski_pipe\Processor\MultiwordAutomaton.
 */

namespace Drupal\wisski_textanly\Plugin\wisski_pipe\Processor;

use Drupal\wisski_pipe\ProcessorInterface;
use Drupal\wisski_pipe\ProcessorBase;
use Drupal\wisski_pipe\Entity\Pipe;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * @Processor(
 *   id = "textanly_mw_automaton",
 *   label = @Translation("Multiword Pattern"),
 *   description = @Translation("Searches for patterns spanning over multiple words, e.g. person names."),
 *   tags = { "regexp", "text", "analysis" }
 * )
 */
class MultiwordAutomaton extends ProcessorBase {
  use StringTranslationTrait;
  
  /**
   * The prefix for constructing the table name for the patterns
   */
  protected static $TABLE_PREFIX = "wisski_textanly_mw_automaton_";

  /**
  * Regular expression patterns
  * @var array
  */
  protected $patterns;

  
  /**
  * The class for annotations found by this analyser
  * @var string
  */
  protected $clazz;
  
  
  /**
  * Map char characters use in the pattern to types in the db table
  * @var array
  */
  protected $char_map;
  
  
  /**
  * Part-of-speech mapping
  * @var array
  */
  protected $pos_mappings;
  
  
  /**
  * A factor for reranking the annotation that is applied to the match's length
  * @var float
  */
  protected $factor;
  

  /**
  * Flag whether to call the processData() method that acts on the values of 
  * the "data" column of the table for the matching pattern.
  * This variable should be set to TRUE by subclasses that implement a data
  * processing behavior.
  * @var boolean
  */
  protected $process_data = FALSE;
  

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    $this->patterns = $configuration['patterns'];
    $this->clazz = $configuration['clazz'];
    $this->char_map = $configuration['char_map'];
    $this->pos_mappings = $configuration['pos_mappings'];
    $this->factor = $configuration['factor'];
  }


  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $conf = [
        'patterns' => $this->patterns,
        'clazz' => $this->clazz,
        'pos_mappings' => $this->pos_mappings,
        'char_map' => $this->char_map,
        'factor' => $this->factor,
      ] + parent::getConfiguration();
    return $conf;
  }


  public function defaultConfiguration() {
    $p  = "0.9 (p|t|rc|x)?g+(v(uz)?)?s\n";
    $p .= "0.8 (p|t|rc|x)?s,g+\n";
    $p .= "0.6 (p|t|rc|x)?g+\n";
    $p .= "0.7 (p|t|rc|x)?s\n";
    $p .= "0.6 [ptrx]\n";
    $p = $this->parsePatterns($p);
    $c  = "g personnames.givenname\n";
    $c .= "g personnames.surname\n";
    $c .= "g personnames.addendum\n";
    $c .= "v personnames.vo_\n";
    $c .= "u und\n";
    $c .= "z personnames.zu_\n";
    $c .= ", ,\n";
    $c .= "p persontypes.profession\n";
    $c .= "t persontypes.title\n";
    $c .= "r persontypes.relationship\n";
    $c .= "c persontypes.copula\n";
    $c .= "x persontypes.religion\n";
    $c = $this->parseMap($c);
    return array(
      'clazz' => '',
      'pos_mappings' => '0.2 NE y gs',
      'patterns' => $p,
      'char_map' => $c,
      'factor' => 1,
    );
  }


  /**
   * {@inheritdoc}
   */
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
  public function doRun() {

    $text_struct = (array) $this->data;
      
    if (!isset($text_struct['annos'])) $text_struct['annos'] = array();
    $lang = $text_struct['lang'];
    $annos = $text_struct['annos'];
    
    if (!isset($text_struct['tokens'])) return $text_struct;

    $patterns = $this->patterns;
    $char_map = $this->char_map;
    $pos_mappings = trim($this->pos_mappings);
    
    // go thru all tokens and annotate with possible class
    $tokens_len = count($text_struct['tokens']);
    $findings = array_fill(0, $tokens_len, NULL);

    for ($token_pos = 0; $token_pos < $tokens_len; $token_pos++) {
      $token = $text_struct['tokens'][$token_pos];
      $lemma = isset($text_struct['lemmata']) ? $text_struct['lemmata'][$token_pos] : '';

      // for each token get the possible name parts
      // first, get all parts where the language matches
      $finding = array();
      $query = db_select('wisski_textanly_mw_automaton', 'm')->fields('m')->andConditionGroup()->condition('lang', $lang);
      if (!empty($lemma)) {
        $query->orConditionGroup()->condition('name', $lemma);
      }
      $rows = $query->condition('name', $token[0])->orderBy('rank')->execute();
      while ($row = $rows->fetchAssoc()) {
        if ($token[0] != $row['name'] && (empty($lemma) || $lemma != $row['name'])) continue;  // for case sensitivity and diacritics, the db ignores them
        $finding[] = $row;
      }

      // second, get all parts where language does not match
      $query = db_select('wisski_textanly_mw_automaton', 'm')->fields('m')->andConditionGroup()->condition('lang', $lang, '!=');
      if (!empty($lemma)) {
        $query->orConditionGroup()->condition('name', $lemma);
      }
      $rows = $query->condition('name', $token[0])->orderBy('rank')->execute();
      while ($row = $rows->fetchAssoc()) {
        if ($token[0] != $row['name']) continue;  // for case sensitivity
        $finding[] = $row;
      }
      
      // third, get suffixes and test them (we assume suffixes are always lang dependent)
      $tokenlen = min(mb_strlen($token[0]), empty($lemma) ? 300 : mb_strlen($lemma));
      $query = db_select('wisski_textanly_mw_automaton', 'm')->fields('m')->condition('lang', $lang)->condition('name', '-%', 'LIKE');
      $rows = $query->orderBy('rank')->execute();
      while ($row = $rows->fetchAssoc()) {
        $suffix = mb_substr($row['name'], 1);
        $suflen = mb_strlen($suffix);
        if ($suflen >= $tokenlen) continue;
        $token_suffix = mb_substr($token[0], -$suflen);
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
                $finding[] = array('name' => $token[0], 'type' => $t, 'offset' => 0, 'rank' => $match[1], 'lang' => '', 'data' => NULL);
              }
            }
          }
        }
      }
      
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
/*                $genders = array_reduce($info, function(&$r, $a) { $r[$a['gender']]++; return $r; }, array('' => 0));
                if (count($genders) > 1) unset($genders['']);
                arsort($genders);*/
                $a_start = $text_struct['tokens'][$offset][1];
                $a_end = $text_struct['tokens'][$end][2];  // $token_pos is last finding pos + 1!
                $anno = array(
                  'class' => $this->clazz,
                  'rank' => $rank,
                  'range' => array($a_start, $a_end),
                  'args' => array(
                    'pattern' => $fp,
                  ),
                  '_origin' => 'wisski_textanly_mw_automaton',
                );
                if ($this->process_data) {
                  $data = array_map(function($a) { return json_decode($a['data']); }, $info);
                  $anno = $this->processData($anno, $data);
                }
              }
            }
          }
        }

        if ($anno['rank'] > 0) {
          if (isset($this->factor)) $anno['rank'] *= $this->factor;
          $annos[] = $anno;
        }
      }

      // reposition the end pointer
      // start stays the same, as we might build bigger terms
      $end++;

    }
    
    $text_struct['annos'] = $annos;

    $this->data = $text_struct;

  }
  
  
  /** see $this->process_data
  */
  protected function processData($anno, $data) {
    return $anno;
  }


  
  /**
   * {@inheritdoc}
   */
  public function inputFields() {
    return parent::inputFields() + array(
      'tokens' => $this->t('Mandatory. The regular expressions will be applied to the token list.'),
    );
  }
      

  /**
   * {@inheritdoc}
   */
  public function outputFields() {
    return parent::outputFields() + array(
      'annos' => $this->t('Regular expression matches will be converted to annotations which are added to this field'),
    );
  }
      
  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $clazzes = array(
      'person' => 'Person',
      'place' => 'Place',
      'time' => 'Time',
      'org' => 'Organisation',
      'event' => 'Event',
      'rel' => 'Relation',
      'type' => 'Type/Concept/Term',
    );

    $patterns = "";
    foreach ($this->patterns as $p) {
      $patterns .= $p['rank'];
      $patterns .= " ";
      $patterns .= $p['regex'];
      $patterns .= "\n";
    }

    $char_map = "";
    foreach ($this->char_map as $k => $v) {
      $char_map .= "$k $v\n";
    }

    $fieldset = array();
    $fieldset['upload_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Upload URL'),
      '#desciption' => $this->t('Specify the URL of a tsv file that should be added to the table.'),
    );
    $fieldset['clazz'] =  array(
      '#type' => 'select',
      '#title' => $this->t('Class'),
      '#multiple' => false ,
      '#options' => $clazzes,
      '#default_value' => $this->clazz,
    );
    $fieldset['char_map'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Char-to-type mapping'),
      '#desciption' => $this->t('Each line contains a character that is used in the pattern definitions below instead of the type name as given in the database, followed by a whitespace and the type.'),
      '#default_value' => $this->char_map,
    );
    $fieldset['patterns'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Rankings and patterns'),
      '#default_value' => $patterns,
      '#description' => $this->t('Each line contains a pattern (e.g. s = surname, g = givenname, v/u/z/d = name addendum, "," = comma, + = multiple, ? = optional) preceeded by a factor and a whitespace.'),
    );
    $fieldset['pos_mappings'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Part-of-speech to category mapping'),
      '#default_value' => $this->pos_mappings,
      '#description' => $this->t('Each line contains a factor, POS tag, whether lemma is present (y/n), and categories it maps to, separated by whitespace.'),
    );
    $fieldset['factor'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Ranking factor'),
      '#default_value' => $this->factor,
    );

    return $fieldset;     

  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->patterns = $this->parsePatterns($form_state->getValue('patterns'));
    $this->clazz = $form_state->getValue('clazz');
    $this->factor = $form_state->getValue('factor');
    $this->pos_mappings = $form_state->getValue('pos_mappings');
    $this->char_map = $this->parseMap($form_state->getValue('char_map'));
  }


  protected function parsePatterns($str) {
    $patterns = array();
    $lines = preg_split('/\r\n|\r|\n/', $str);
    foreach ($lines as $line) {
      list($rank, $regex) = explode(" ", $line, 2);
      $rank = trim($rank);
      $regex = trim($regex);
      if ($rank == '' || $regex == '') continue;
      $patterns[] = array('rank' => $rank, 'regex' => $regex);
    }
    return $patterns;
  }

  protected function parseMap($str, $delim = ' ') {
    $map = array();
    $lines = preg_split('/\r\n|\r|\n/', $str);
    foreach ($lines as $line) {
      list($k, $v) = explode($delim, $line, 2);
      if ($k == '' || $v == '') continue;
      $map[$k] = $v;
    }
    return $map;
  }

}



