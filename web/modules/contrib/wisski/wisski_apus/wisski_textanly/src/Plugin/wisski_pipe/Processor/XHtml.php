<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Plugin\wisski_pipe\Processor\XHtml.
 * 
 * @author Martin Scholz
 */

namespace Drupal\wisski_textanly\Plugin\wisski_pipe\Processor;

use Drupal\wisski_pipe\ProcessorInterface;
use Drupal\wisski_pipe\Plugin\wisski_pipe\Processor\RunPipe;
use Drupal\wisski_pipe\Entity\Pipe;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * @Processor(
 *   id = "textanly_xhtml",
 *   label = @Translation("Analyse XHTML content"),
 *   description = @Translation("Process XHTML content and run a pipe on it."),
 *   tags = { "pipe", "recursive", "text", "analysis", "xhtml" }
 * )
 */
class XHtml extends RunPipe {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function doRun() {
    
    // determine which field we take as input: precedence is
    // 1. text
    // 2. html
    // 3. xhtml
    if (isset($this->data->html)) $this->data->xhtml = $this->data->html;
    if (isset($this->data->text)) $this->data->xhtml = $this->data->text;
    if (!isset($this->data->xhtml)) {
      // there are no fields that we can work on
      if ($logger != NULL) $this->logWarning("There is no field for xhtml contents.");
      return; 
    }

    $default_lang = isset($this->data->lang) ? $this->data->lang : '';
    
    unset($this->data->text);
    $this->stripTags($default_lang);
    // $this->data->text now contains plain text (with additional ws injected)
    
    // then run the pipe on the whole text
    parent::doRun();

    // adjust ranges
    // TODO: maybe this should be in a separate processor?
    foreach ($this->data->annos as $k => $anno) {
      $s = $anno->range[0]; // + $start;
      $e = $anno->range[1]; // + $start;
      $anno->plain_text_range = array($s, $e);
      if ($s == 0 && $e == 0) continue; // pos 0 always shifts back to 0!
      foreach ($this->data->char_shifts as $shift => $offset) {
        if ($s != 0 && $s < $offset) {
          $anno->range[0] = max($s - $shift, 0);
          $s = 0;
        }
        if ($e < $offset) {
          $anno->range[1] = max($e - $shift, 0);
          break;
        }
      }
    }
    
    $this->data->annos = $annos;
    $this->data->plain_text = $this->data->text;
    $this->data->text = $this->data->xhtml;

  }

  
  /**
   * {@inheritdoc}
   */
  public function inputFields() {
    return parent::inputFields() + array(
      'text' => FALSE,
      'html' => FALSE,
      'xhtml' => FALSE,
      'lang' => FALSE,
    );
  }
      

  /**
   * {@inheritdoc}
   */
  public function outputFields() {
    return parent::outputFields() + array(
      'text' => 1,
      'plain_text' => 1,
      'xhtml' => 1,
      'annos' => 1,
      'char_shifts' => 1,
      'lang_ranges' => 1,
    );
  }
      




  /** Takes a fragment of HTML and extracts text information
  *
  * @param text the HTML fragment
  * @param default_lang the document defualt language
  *
  * @return an array of text information containing
  * - text: The pure text contents.
  *   UTF8 encoded; use multibyte methods mb_* or //u flag in preg_*!
  *   Some HTML tags are replaces by a whitespace character to separate words
  *   (e.g. <br/>, <p>, ...)
  * - char_shifts: due to ws-insertion for tags, the (P)CDATA character position
  *   in the HTML doc may vary from the position in pure text string.
  *   This is an assoc array with pairs
  *   <#shifted chars> => <max text pos with that shift (excluded)>
  *   e.g. an array(0 => 12, 1 => 14, 2 => 34) says that all char positions until
  *   excluding char 12 have to be shifted left 0, until excluding char 14
  *   shifted left 1, etc. to obtain the char position in the HTML
  * - lang_ranges: an array with keys being language labels and values being each
  *   a list of text intervals that are marked in this language.
  *   Intervals are encoded as array(start, end). E.g.
  *   array(
  *       'en' => array(array(10, 20)),
  *       'de' => array(array(0, 10), array(20, 30)))
  * - annos: an array of annotations found in the text
  *
  */
  protected function stripTags($default_lang = '') {

    $ws_replacements = array('br', 'p', '/p', 'div', '/div');
    
    $xhtml = $this->data->xhtml;
    $xhtml = "<div>$xhtml</div>"; // encapsulate: text may be xml/html snippet (leading/trailing chars or multiple root tags)
    $doc = \DOMDocument::loadXML($xhtml, LIBXML_NOERROR);
    if (!$doc) {
      $doc = \DOMDocument::loadHTML('<?xml encoding="UTF-8">' . $xhtml);
    }
    if (!$doc) {
      return NULL;
    }

    list($text, $textlen, $char_shifts, $lang_ranges, $annos) = $this->stripTagsWalk($doc->documentElement, $ws_replacements, '', 0, array(), array(), $default_lang, array());

    $lang_ranges = $this->joinLangRanges($lang_ranges);

    $this->data->text = $text;
    $this->data->lang_ranges = $lang_ranges;
    $this->data->char_shifts = $char_shifts;
    $this->data->annos = $annos;

  }


  /** Helper function for stripTags()
  * that walks the DOM tree collecting the information
  */
  protected function stripTagsWalk($element, $replacements, $text, $textlen, $char_shifts, $langs, $cur_lang, $annos) {

    if ($element->hasAttribute('lang')) $cur_lang = $element->getAttribute('lang');
    if ($element->hasAttribute('xml:lang')) $cur_lang = $element->getAttribute('xml:lang');

    if (in_array(strtolower($element->tagName), $replacements)) {
      $text .= ' ';
      $langs[$cur_lang][] = array($textlen, $textlen + 1);
      $textlen += 1;
      $char_shifts[] = $textlen;
    }
    
    // TODO: reuse AnnotationHelper functions
    $anno = $this->extractAnnotationFromTag($element);
    if ($anno != NULL) {
      $anno->range = array($textlen); // end of range is set below
    }

    $child = $element->firstChild;
    while ($child) {

      switch ($child->nodeType) {
        case XML_TEXT_NODE:
        case XML_CDATA_SECTION_NODE:

          $l = $textlen;
          $text .= $this->normalizeSpace($child->textContent);
          $textlen += mb_strlen($child->textContent);
          $langs[$cur_lang][] = array($l, $textlen);
          break;

        case XML_ELEMENT_NODE:

          list($text, $textlen, $char_shifts, $langs, $annos) = $this->stripTagsWalk($child, $replacements, $text, $textlen, $char_shifts, $langs, $cur_lang, $annos);
          break;

      }

      $child = $child->nextSibling;

    }

    if ($anno != NULL) {
      $anno->range[1] = $textlen; // set end of annotation range
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


  protected function normalizeSpace($text) {
    // $spaces = mb_convert_encoding("\f\n\r\t&nbsp;", "UTF-8", "HTML-ENTITIES");
    // return mb_ereg_replace("/[$spaces]/", " ", $text);
    return preg_replace('/\s/u', ' ', $text);
  }


  protected function joinLangRanges($lang_ranges) {

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


  protected function extractAnnotationFromTag($element) {
    $anno = NULL;
    
    if ($element->hasAttribute('class') && strpos($element->getAttribute('class'), 'wisski_anno') !== FALSE) {
      $anno = array('approved' => FALSE);
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
    } elseif ($element->hasAttribute('data-wisski-anno')) {
      $anno = array('approved' => FALSE);
      if ($element->hasAttribute('data-wisski-anno-id')) $anno['id'] = $element->getAttribute('data-wisski-anno-id');
      if ($element->hasAttribute('typeof')) $anno['class'] = $element->getAttribute('typeof');
      if ($element->hasAttribute('about')) $anno['uri'] = $element->getAttribute('about');
      if ($element->hasAttribute('data-wisski-anno-target')) $anno['uri'] = $element->getAttribute('data-wisski-anno-target');
      if ($element->getAttribute('data-wisski-anno-certainty') == 'approved') $anno['approved'] = TRUE;
    }

    return (object) $anno;

  }



}
