<?php

namespace Drupal\onomasticon\Plugin\Filter;

use Drupal\Core\Config\Schema\ArrayElement;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Masterminds\HTML5;

/**
 * @Filter(
 *   id = "filter_onomasticon",
 *   title = @Translation("Onomasticon Filter"),
 *   description = @Translation("Adds glossary information to words."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "onomasticon_vocabulary" = "",
 *     "onomasticon_tag" = "dfn",
 *     "onomasticon_disabled" = "abbr audio button cite code dfn form meta object pre style script video",
 *     "onomasticon_implement" = "extra_element",
 *     "onomasticon_orientation" = "below",
 *     "onomasticon_cursor" = "default",
 *     "onomasticon_repetition" = true
 *   },
 * )
 */
class FilterOnomasticon extends FilterBase {

  /**
   * @var HTML5 DOMDocument
   * This var is the base element all replacements are made on.
   */
  private $htmlDom;

  /**
   * @var array
   * Contains the tree hierarchy for the current element
   * while traversing the DOM Document to restrict replacement
   * to certain parent tags.
   */
  private $htmlTree = array();

  /**
   * @var array
   * Contains all paths of processed DOM Nodes to avoid
   * duplicate replacements.
   */
  private $processedPaths = array();

  /**
   * @var array
   * Collection of replacements. Gets applied to $htmlDom
   * one the complete tree has been traversed.
   */
  private $htmlReplacements = array();

  /**
   * @var Term[]
   * Simple cache mechanism for loaded terms. Also works
   * as a list of already processed terms.
   */
  private $terms = array();

  /**
   * @var array
   * Simple cache mechanism for loaded terms. Also works
   * as a list of already processed terms.
   */
  private $termCache = array();

  /**
   * Main filter function as expected by Drupal.
   *
   * @param string $text
   * @param string $langcode
   * @return \Drupal\filter\FilterProcessResult|string
   */
  public function process($text, $langcode) {
    // Check if a vocabulary has been set.
    if (empty($this->settings['onomasticon_vocabulary'])) {
      return $text;
    }

    // Load the description into an HTML5 DOMDocument.
    $html5 = new HTML5();
    $this->htmlDom = $html5->loadHTML('<html><body>' . $text . '</body></html>');
    $this->htmlDom->preserveWhiteSpace = false;
    // Normalize in case HTMLCorrector has not been run.
    $this->htmlDom->normalizeDocument();
    // Get the root element (<html>).
    $rootElement = $this->htmlDom->documentElement;
    // Get the body element (<body>).
    $body = $rootElement->getElementsByTagName('body')->item(0);
    // Walk the DOM and replace terms with definitions.
    $this->processChildren($body);
    // Traversing finished, let's replace child nodes.
    foreach ($this->htmlReplacements as $r) {
      $domFragment = $this->htmlDom->createDocumentFragment();
      // XML doesn't know some named entities like &nbsp;
      #$domFragment->appendXML(self::html_entities_normalize_xml($r['html']));
      #$r['dom']->parentNode->replaceChild($domFragment, $r['dom']);

      $bool = $domFragment->appendXML(self::html_entities_normalize_xml($r['html']));
      if($bool){
        $r['dom']->parentNode->replaceChild($domFragment, $r['dom']);
      }
    }

    // Export DOMDocument as HTML5
    $text = $body->ownerDocument->saveHTML($body);
    // Prepare return object.
    $result = new FilterProcessResult($text);

    return $result;
  }

  /**
   * Unicode-proof htmlentities function.
   * Returns 'normal' chars as chars and special characters
   * as numeric html entities.
   *
   * @param string $string
   * @return string
   * */
  private function html_entities_normalize_xml($string) {
    // Get rid of existing entities and double-escape.
    $string = html_entity_decode(stripslashes($string), ENT_QUOTES, 'UTF-8');
    $result = '';
    // Create array of multi-byte characters.
    $ar = preg_split('/(?<!^)(?!$)/u', $string);
    foreach ($ar as $c) {
      $o = ord($c);
      if (
        // Any multi-byte character's length is > 1
        (strlen($c) > 1)
        // Control characters are below 32, latin special chars are above 126.
        // Non-latin characters are above 126 as well, including &nbsp;.
        || ($o < 32 || $o > 126)
        // That's the ampersand.
        || ($o == 38)
        /* This following ranges includes single&double quotes,
         * ampersand, hash sign, less- and greater-than signs.
         * We do not want to replace these, otherwise HTML will break,
         * except for the ampersand which is targeted above.
         * Non-breaking spaces &nbsp; are converted to &#160; .
         */
        #|| ($o > 33 && $o < 40) /* quotes + ampersand */
        #|| ($o > 59 && $o < 63) /* html */
      ) {
        // Convert to numeric entity.
        $c = mb_encode_numericentity($c, array(0x0, 0xffff, 0, 0xffff), 'UTF-8');
      }
      $result .= $c;
    }
    // Mask ampersands
    $result = str_replace('&#38;', '###amp###', $result);
    $result = html_entity_decode($result, ENT_HTML5);
    $result = str_replace('###amp###', '&#38;', $result);
    return $result;
  }

  /**
   * Traverses the DOM tree (recursive).
   * If current DOMNode element has children,
   * this function calls itself.
   * #text nodes never have any children, there
   * Onomasticon filter is applied.
   *
   * @param $dom \DOMNode
   *
   */
  public function processChildren($dom) {
    if ($dom->hasChildNodes()) {
      // Children present, this can't be a #text node.
      // Add the tag name to the tree, so we know the
      // hierarchy.
      $this->htmlTree[] = $dom->nodeName;
      // Recursive call on first child.
      foreach ($dom->childNodes as $child) {
        $this->processChildren($child);
      }
    }
    else {
      // No children present => end of tree branch.
      if ($dom->nodeName == '#text' && !$dom->isWhitespaceInElementContent()) {
        // Element is of type #text and has content.

        // First check tree for ancestor tags not allowed.
        $disabled_tags = explode(' ', $this->settings['onomasticon_disabled']);
        // Sanitize user input
        $disabled_tags = array_map(
          function($tag) { return preg_replace("/[^a-z1-6]*/", "", strtolower(trim($tag))); },
          $disabled_tags
        );
        // Add Onomasticon tag and anchor tag.
        $disabled_tags[] = $this->settings['onomasticon_tag'];
        $disabled_tags[] = 'a';
        $disabled_tags = array_unique($disabled_tags);
        // Find the bad boys.
        $bad_tags = array_intersect($disabled_tags, $this->htmlTree);
        if (count($bad_tags) == 0) {
          // To avoid double replacements, check if this element
          // has been processed already. DomNodePath is unique.
          if (!in_array($dom->getNodePath(), $this->processedPaths)) {
            // Element has not been processed yet. Let's do this!
            // Original nodeValue (textContent).
            $text_orig = $dom->nodeValue;
            // Processed text, Onomasticon has been applied.
            $text_repl = $this->replaceTerms($text_orig);
            // Did the filter find anything?
            if ($text_orig !== $text_repl) {
              // Indeed, let's save the information for later.
              $this->htmlReplacements[] = array(
                'dom' => $dom,
                'html' => $text_repl,
              );
            }
            // Add element to processed items.
            $this->processedPaths[] = $dom->getNodePath();
          }
        }
      }

      // End of branch reached. Look for sibling elements.
      if (empty($dom->nextSibling)) {
        // No nextSibling found, last child element of parent.
        // Take a step back in tree and
        array_pop($this->htmlTree);
        $parent = $dom;
        // Reverse traverse the tree until there are no
        // more siblings left to process.
        while (!empty($parent->parentNode) && empty($parent->nextSibling) && count($this->htmlTree) > 0) {
          array_pop($this->htmlTree);
          $parent = $parent->parentNode;
        }
      }
    }
  }

  /**
   * This is the actual filter function.
   *
   * @param $text String containing a #text DOMNode value.
   * @return string Processed string.
   */
  public function replaceTerms($text) {
    if ($this->settings['onomasticon_repetition']) {
      $preg_limit = 1;
    }
    else {
      $preg_limit = -1;
    }

    // Cycle through terms and search for occurrence.
    $replacements = array();
    $language = \Drupal::languageManager()
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();

    foreach ($this->getTaxonomyTerms() as $term) {
      if ($this->settings['onomasticon_repetition'] && array_key_exists($term->id(), $this->termCache)) {
        continue;
      }
      if (!$term->hasTranslation($language)) {
        continue;
      }
      $term = $term->getTranslation($language);
      $term_name = $term->label();
      // Get position of term in text.
      $pos = strpos($text, $term_name);
      // If not found try capitalized (e.g. beginning of sentence).
      if ($pos === false) {
        $pos = strpos($text, ucfirst($term_name));
      }
      // TODO: Turn into an option to search for terms independent of case.
      // Get position of term in text independent of case.
      #$pos = strpos(strtolower($text), strtolower($term_name));

      if ($pos === false) {
        continue;
      }
      // Set the correct cased needle.
      $needle = substr($text, $pos, strlen($term_name));
      if (!array_key_exists($term->id(), $this->termCache)) {
        $this->termCache[$term->id()] = true;
      }
      $description = $term->getDescription();

      // Set the implementation method.
      $implement = $this->settings['onomasticon_implement'];
      if ($implement == 'attr_title') {
        $description = strip_tags($description);
        // Title attribute is enclosed in double quotes,
        // we need to escape double quotes in description.
        // TODO: Instead of removing double quotes altogether, escape them.
        $description = str_replace('"', '', $description);
        // Replace no-breaking spaces with normal ones.
        $description = str_replace('&nbsp;', ' ', $description);
        // Trim multiple white-space characters with single space.
        $description = preg_replace('/\s+/m', ' ', $description);
      }

      $onomasticon = [
        '#theme' => 'onomasticon',
        '#tag' => $this->settings['onomasticon_tag'],
        '#needle' => $needle,
        '#description' => $description,
        '#implement' => $implement,
        '#orientation' => $this->settings['onomasticon_orientation'],
        '#cursor' => $this->settings['onomasticon_cursor'],
      ];

      $placeholder = '###' . $term->id() . '###';
      $replacements[$placeholder] = trim(\Drupal::service('renderer')->render($onomasticon));

      $text = preg_replace("/(?<![a-zA-Z0-9_äöüÄÖÜ])" . $needle . "(?![a-zA-Z0-9_äöüÄÖÜ])/", $placeholder, $text, $preg_limit);
    }

    foreach ($replacements as $placeholder => $replacement) {
      $text = str_replace($placeholder, $replacement, $text, $preg_limit);
    }
    return $text;
  }

  /**
   * Singleton to retrieve all terms.
   *
   * @return Term[]
   */
  private function getTaxonomyTerms() {
    if (empty($this->terms)) {
      $this->terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadTree($this->settings['onomasticon_vocabulary'], 0 , NULL, true);
    }

    return $this->terms;
  }

  /**
   * Filter settings form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $vocabularies = Vocabulary::loadMultiple();
    $options = array();
    foreach ($vocabularies as $vocabulary) {
      $options[$vocabulary->id()] = $vocabulary->get('name');
    }
    $form['onomasticon_vocabulary'] = array(
      '#type' => 'select',
      '#title' => $this->t('Vocabulary'),
      '#options' => $options,
      '#default_value' => $this->settings['onomasticon_vocabulary'],
      '#description' => $this->t('Choose the vocabulary that holds the glossary terms.'),
    );
    $form['onomasticon_tag'] = array(
      '#type' => 'select',
      '#title' => $this->t('HTML tag'),
      '#options' => array(
        'dfn' => $this->t('Definition (dfn)'),
        'abbr' => $this->t('Abbreviation (abbr)'),
        'cite' => $this->t('Title of work (cite)'),
      ),
      '#default_value' => $this->settings['onomasticon_tag'],
      '#description' => $this->t('Choose the HTML tag to contain the glossary term.'),
    );
    $form['onomasticon_disabled'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Disabled tags'),
      '#default_value' => $this->settings['onomasticon_disabled'],
      '#description' => $this->t('Enter all HTML elements in which terms should not be replaced. Anchor tag as well as the default HTML tag are added to that list automatically.'),
    );
    $form['onomasticon_implement'] = array(
      '#type' => 'select',
      '#title' => $this->t('Implementation'),
      '#options' => array(
        'extra_element' => $this->t('Extra element'),
        'attr_title' => $this->t('Title attribute'),
      ),
      '#default_value' => $this->settings['onomasticon_implement'],
      '#description' => $this->t('Choose the implementation of the glossary term description. Due to HTML convention, the description will be stripped of any tags as they are not allowed in a tag\'s attribute.'),
    );
    $form['onomasticon_orientation'] = array(
      '#type' => 'select',
      '#title' => $this->t('Orientation'),
      '#options' => array(
        'above' => $this->t('Above'),
        'below' => $this->t('Below'),
      ),
      '#default_value' => $this->settings['onomasticon_orientation'],
      '#description' => $this->t('Choose whether the tooltip should appear above or below the hovered glossary term.'),
      '#states' => array(
        'visible' => array(
          'select[name="filters[filter_onomasticon][settings][onomasticon_implement]"]' => array('value' => 'extra_element'),
        ),
      ),
    );
    $form['onomasticon_cursor'] = array(
      '#type' => 'select',
      '#title' => $this->t('Mouse cursor'),
      '#options' => array(
        'default' => $this->t('Default (Text cursor)'),
        'help' => $this->t('Help cursor'),
        'none' => $this->t('Hide cursor'),
      ),
      '#default_value' => $this->settings['onomasticon_cursor'],
      '#description' => $this->t('Choose a style the mouse cursor will change to when hovering a glossary term.'),
    );
    $form['onomasticon_repetition'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add definition to first occurrence of term in text, only.'),
      '#default_value' => $this->settings['onomasticon_repetition'],
      '#description' => $this->t('Disable this option to add definitions to all occurrences in text. This option\'s scope is a single text area.'),
    );
    return $form;
  }
}
