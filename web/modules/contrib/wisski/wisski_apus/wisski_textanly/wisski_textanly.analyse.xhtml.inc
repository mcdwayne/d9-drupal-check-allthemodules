<?php


/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */




/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_xhtml($op, $ap) {

  switch ($op) {

    case 'edit':

      if (!$ap->settings) {
        $ap->settings = array(
          'default_lang' => '',
          'lang_dep_profile' => '',
          'lang_indep_profile' => '',
        );
      }

      $profiles = variable_get('wisski_textanly_profiles', array('default'));
      $profiles = array_combine($profiles, $profiles);
      $profiles[''] = t('<none>');

      $fieldset = array();
      $fieldset['default_lang'] =  array(
        '#type' => 'textfield',
        '#title' => t('Default language'),
        '#maxlength' => 11,
        '#default_value' => $ap->settings['default_lang'],
        '#description' => t('An ISO language code such as "en", "de", etc.'),
      );
      $fieldset['lang_dep_profile'] = array(
        '#type' => 'select',
        '#title' => t('Language dependent profile'),
        '#options' => $profiles,
        '#default_value' => $ap->settings['lang_dep_profile'],
        '#description' => t('This profile will be applied to the text first. Take care that there are no loops between the profiles!'),
      );
      $fieldset['lang_indep_profile'] = array(
        '#type' => 'select',
        '#title' => t('Language independent profile'),
        '#options' => $profiles,
        '#default_value' => $ap->settings['lang_indep_profile'],
        '#description' => t('This profile will be applied to the text second. Take care that there are no loops between the profiles!'),
      );

      return $fieldset;

    case 'key_info':
      return array(
        'input' => array(
          'text',
        ),
        'output' => array(
          'annos',
          'char_shifts',
          'char_offset',
          'lang',
          'lang_ranges',
          'plain_text',
          'text',
          'xhtml',
        )
      );

    default:

      return NULL;

  }

}



/* ***************** *
*  ANALYSER
** ***************** */



/** Analyses a (X)HTML fragment. Preprocesses the HTML and then runs the
* analysers of the specified profile.
*
* @parameter $text the html fragment
* @parameter $lang the default document language
* @parameter $profile the profile of the analysis configuration
*   (this currentyl is not supported by the admin interface)
*
* @return a list of proposed annotations
*
* @author Martin Scholz
*
*/
function wisski_textanly_analyse_xhtml($analyser, $text_struct, $ticket) {

  $default_lang = isset($text_struct['lang']) ? $text_struct['lang'] : $analyser->settings['default_lang'];
  
  // copy the original xhtml text
  // and transform the xhtml to plain text
  $text_struct['xhtml'] = $text_struct['text'];
  unset($text_struct['text']);
  $text_struct += _wisski_textanly_strip_tags($text_struct['xhtml'], $default_lang);
  // $text_struct['text'] now contains plain text (with additional ws injected)
  
  // first run the language dependent profile on all language spans
  // each analyser only sees the current span
  if (!empty($analyser->settings['lang_dep_profile'])) {
    $profile = $analyser->settings['lang_dep_profile'];

    // determine keys that we will not take in our text_struct
    $keys_ignore = array_keys($text_struct);
    $keys_ignore[] = 'char_offset';
    $keys_ignore[] = 'lang';

    wisski_textanly_log($ticket, t('Running language dependent profile @p', array('@p' => $profile)));
    
    // sort lang spans according to text order
    $langs = array();
    foreach ($text_struct['lang_ranges'] as $lang => $ranges) {
      foreach ($ranges as $range) {
        $langs[$range[0]] = array($lang, $range[0], $range[1]);
      }
    }
    ksort($langs);

    foreach ($langs as $range) {
      
      list($lang, $start, $end) = $range;
      $text_struct2 = array(
        'char_offset' => $start,
        'lang' => $lang,
        'text' => mb_substr($text_struct['text'], $start, $end - $start),
      );
      
      $text_struct2 = wisski_textanly_analyse($profile, $text_struct2, $ticket);
      
      // we do not provide any adjustment of character offsets here
      // analysers that rely on or store offsets must be sensitive to char_offset.
      // for the analysers tokenizer and regexp this is the case.
      // alternatively output could be postprocessed by an adjustment analyser.

      // merge in keys
      foreach ($text_struct2 as $k => $v) {
        if (in_array($k, $keys_ignore)) continue;
        if (is_array($v)) $text_struct[$k] = array_merge($text_struct[$k], $v);
        if (is_string($v)) $text_struct[$k] .= $v;
        if (is_numeric($v)) $text_struct[$k] += $v;
      }

    } // end for lang_ranges

  } // end if lang_dep_profile


  // then run the language independent profile on the whole text
  if (!empty($analyser->settings['lang_indep_profile'])) {
    $profile = $analyser->settings['lang_indep_profile'];
    wisski_textanly_log($ticket, t('Running language independent profile @p', array('@p' => $profile)));
    $text_struct = wisski_textanly_analyse($profile, $text_struct, $ticket);
  }


  // adjust ranges
  $annos = $text_struct['annos'];
  foreach ($annos as $k => &$anno) {
    $s = $anno['range'][0]; // + $start;
    $e = $anno['range'][1]; // + $start;
    if ($s == 0 && $e == 0) continue; // pos 0 always shifts back to 0!
    foreach ($text_struct['char_shifts'] as $shift => $offset) {
      if ($s != 0 && $s < $offset) {
        $anno['range'][0] = $s - $shift;
        $s = 0;
      }
      if ($e < $offset) {
        $anno['range'][1] = $e - $shift;
        break;
      }
    }
  }
  
  $text_struct['annos'] = $annos;
  $text_struct['plain_text'] = $text_struct['text'];
  $text_struct['text'] = $text_struct['xhtml'];

  return $text_struct;

}



/** Takes a fragment of HTML and extracts text information
*
* @parameter $text the HTML fragment
* @parameter $default_lang the document defualt language

* @return an array of text information containing
* - text: The pure text contents.
    UTF8 encoded; use multibyte methods mb_* or //u flag in preg_*!
    Some HTML tags are replaces by a whitespace character to separate words
    (e.g. <br/>, <p>, ...)
* - char_shifts: due to ws-insertion for tags, the (P)CDATA character position
    in the HTML doc may vary from the position in pure text string.
    This is an assoc array with pairs
    <#shifted chars> => <max text pos with that shift (excluded)>
    e.g. an array(0 => 12, 1 => 14, 2 => 34) says that all char positions until
    excluding char 12 have to be shifted left 0, until excluding char 14
    shifted left 1, etc. to obtain the char position in the HTML
* - lang_ranges: an array with keys being language labels and values being each
*   a list of text intervals that are marked in this language.
*   Intervals are encoded as array(start, end). E.g.
*   array(
        'en' => array(array(10, 20)),
        'de' => array(array(0, 10), array(20, 30)))
* - annos: an array of annotations found in the text
*
* @author Martin Scholz
*/
function _wisski_textanly_strip_tags($xhtml, $default_lang = '') {

  $ws_replacements = array('br', 'p', '/p', 'div', '/div');

  $xhtml = "<div>$xhtml</div>"; // encapsulate: text may be xml/html snippet (leading/trailing chars or multiple root tags)
  $doc = DOMDocument::loadXML($xhtml, LIBXML_NOERROR);
  if (!$doc) {
    $doc = DOMDocument::loadHTML('<?xml encoding="UTF-8">' . $xhtml);
  }
  if (!$doc) {
    return NULL;
  }

  list($text, $textlen, $char_shifts, $lang_ranges, $annos) = _wisski_textanly_strip_tags_walk($doc->documentElement, $ws_replacements, '', 0, array(), array(), $default_lang, array());

  $lang_ranges = _wisski_textanly_join_lang_ranges($lang_ranges);

  return array('text' => $text, 'lang_ranges' => $lang_ranges, 'char_shifts' => $char_shifts, 'annos' => $annos);

}


/** Helper function for _wisski_textanly_strip_tags()
* that walks the DOM tree collecting the information
*
* @author Martin Scholz
*
*/
function _wisski_textanly_strip_tags_walk($element, $replacements, $text, $textlen, $char_shifts, $langs, $cur_lang, $annos) {

  if ($element->hasAttribute('lang')) $cur_lang = $element->getAttribute('lang');
  if ($element->hasAttribute('xml:lang')) $cur_lang = $element->getAttribute('xml:lang');

  if (in_array(strtolower($element->tagName), $replacements)) {
    $text .= ' ';
    $langs[$cur_lang][] = array($textlen, $textlen + 1);
    $textlen += 1;
    $char_shifts[] = $textlen;
  }

  $anno = NULL;
  if ($element->hasAttribute('class') && strpos($element->getAttribute('class'), 'wisski_anno') !== FALSE) {
    $anno = array('approved' => FALSE, 'range' => array($textlen));
    foreach(explode(' ', $element->getAttribute('class')) as $class) {
      if (substr($class, 0, 19) == 'wisski_anno_deleted') $anno['deleted'] = TRUE;
      if (substr($class, 0, 18) == 'wisski_anno_class_') $anno['class'] = urldecode(substr($class, 18));
      if (substr($class, 0, 16) == 'wisski_anno_uri_') $anno['uri'] = urldecode(substr($class, 16));
      if (substr($class, 0, 18) == 'wisski_anno_vocab_') $anno['voc'] = urldecode(substr($class, 18));
      if (substr($class, 0, 17) == 'wisski_anno_rank_') $anno['rank'] = urldecode(substr($class, 17));
      if (substr($class, 0, 20) == 'wisski_anno_new') $anno['new'] = TRUE;
      if (substr($class, 0, 20) == 'wisski_anno_proposed') $anno['approved'] = FALSE;
      if (substr($class, 0, 20) == 'wisski_anno_approved') $anno['approved'] = TRUE;
      if (substr($class, 0, 16) == 'wisski_anno_rel_') {
        $rel = explode(':', substr($class, 16));
        $anno['rel'][urldecode($rel[0])][] = urldecode($rel[1]);
      }
      if (substr($class, 0, 16) == 'wisski_anno_rev_') {
        $rel = explode(':', substr($class, 16));
        $anno['rev'][urldecode($rel[0])][] = urldecode($rel[1]);
      }
    }
  }

  $child = $element->firstChild;
  while ($child) {

    switch ($child->nodeType) {
      case XML_TEXT_NODE:
      case XML_CDATA_SECTION_NODE:

        $l = $textlen;
        $text .= _wisski_textanly_normalize_space($child->textContent);
        $textlen += mb_strlen($child->textContent);
        $langs[$cur_lang][] = array($l, $textlen);
        break;

      case XML_ELEMENT_NODE:

        list($text, $textlen, $char_shifts, $langs, $annos) = _wisski_textanly_strip_tags_walk($child, $replacements, $text, $textlen, $char_shifts, $langs, $cur_lang, $annos);
        break;

    }

    $child = $child->nextSibling;

  }

  if ($anno != NULL) {
    $anno['range'][1] = $textlen;
    $annos[] = $anno;
  }

  if (in_array('/' . strtolower($element->tagName), $replacements)) {
    $text .= ' ';
    $langs[$cur_lang][] = array($textlen, $textlen + 1);
    $textlen += 1;
    $char_shifts[] = $textlen;
  }

  return array($text, $textlen, $char_shifts, $langs, $annos);

}


/**
* @author Martin Scholz
*/
function _wisski_textanly_normalize_space($text) {

  $spaces = mb_convert_encoding("\f\n\r\t&nbsp;", "UTF-8", "HTML-ENTITIES");
  return mb_ereg_replace("/[$spaces]/", " ", $text);

}


/**
* @author Martin Scholz
*/
function _wisski_textanly_join_lang_ranges($lang_ranges) {

  foreach ($lang_ranges as $lang => $ranges) {
    // ranges should be sorted! usort($ranges, function($a, $b) { return $a[0] - $b[0]; });
    for ($i = 0; $i < count($ranges) - 1; $i++) {
      while(isset($ranges[$i + 1]) && $ranges[$i][1] == $ranges[$i + 1][0]) {
        $range = array_splice($ranges, $i, 1);
        $ranges[$i][0] = $range[0][0];
      }
    }
    $lang_ranges[$lang] = $ranges;
  }

  return $lang_ranges;

}


