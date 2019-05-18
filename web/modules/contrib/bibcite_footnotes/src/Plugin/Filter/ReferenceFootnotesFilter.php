<?php

namespace Drupal\bibcite_footnotes\Plugin\Filter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\footnotes\Plugin\Filter\FootnotesFilter;

/**
 * Reference Footnotes filter.
 *
 * @Filter(
 *   id = "filter_reference_footnotes",
 *   module = "bibcite_footnotes",
 *   title = @Translation("Reference Footnotes filter"),
 *   description = @Translation("You can insert footnotes directly into texts."),
 *   type = \Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   cache = FALSE,
 *   settings = {
 *     "footnotes_footnotefootnote_linkcollapse" = FALSE,
 *     "footnotes_ibid" = FALSE,
 *     "notes_section_label" = "Notes",
 *   },
 *   weight = 0
 * )
 */
class ReferenceFootnotesFilter extends FootnotesFilter {

  /**
   * Object with configuration for reference footnotes.
   *
   * @var object
   */
  protected $config;

  /**
   * Object with configuration for reference footnotes, where we need editable..
   *
   * @var object
   */
  protected $configEditable;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->config = \Drupal::config('reference_footnotes.settings');
    $this->configEditable = \Drupal::configFactory()
      ->getEditable('reference_footnotes.settings');
  }

  /**
   * Create the settings form for the filter.
   *
   * @param array $form
   *   A minimally prepopulated form array.
   * @param FormStateInterface $form_state
   *   The state of the (entire) configuration form.
   *
   * @return array
   *   The $form array with additional form elements for the settings of
   *   this filter. The submitted form values should match $this->settings.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings['footnotes_collapse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapse reference footnotes with identical content'),
      '#default_value' => $this->settings['footnotes_collapse'],
      '#description' => t('If two reference footnotes have the exact same content, they will be collapsed into one as if using the same value="" attribute.'),
    ];
    $settings['footnotes_ibid'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display subsequent instances of multiple references with \'Ibid.\''),
      '#default_value' => $this->settings['footnotes_ibid'],
    ];
    $settings['notes_section_label'] = [
      '#type' => 'textfield',
      '#title' => t('Notes section heading label'),
      '#default_value' => $this->settings['notes_section_label'],
    ];
    $settings['reference_dont_show_backlink_text'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Don't show note 'value' text in reference list."),
      '#description' => $this->t("Suitable for MLA-style citations, like (Smith, 33-22)"),
      '#default_value' => $this->settings['reference_dont_show_backlink_text'],
    ];
    $settings['works_cited_sort_by'] = [
      '#type' => 'select',
      '#title' => $this->t("Sort Workd Cited list by"),
      '#options' => [
        'weight' => $this->t("Manually"),
        'alphabetical' => $this->t("Alphabetically"),
      ],
      '#default_value' => $this->settings['works_cited_sort_by'],
    ];
    return $settings;
  }

  /**
   * Helper function called from preg_replace_callback() above.
   *
   * Uses static vars to temporarily store footnotes found.
   * This is not threadsafe, but PHP isn't.
   *
   * @param array $matches
   *   Elements from array:
   *   - 0: complete matched string.
   *   - 1: tag name.
   *   - 2: tag attributes.
   *   - 3: tag content.
   * @param string $op
   *   Operation.
   *
   * @return string
   *   Return the string processed by geshi library.
   */
  protected function replaceCallback($matches, $op = '') {
    static $opt_collapse = 0;
    static $n = 0;
    static $store_matches = [];
    static $used_values = [];
    $str = '';

    if ($op == 'prepare') {
      // In the 'prepare' case, the first argument contains the options to use.
      // The name 'matches' is incorrect, we just use the variable anyway.
      $opt_collapse = $matches;
      return 0;
    }

    if ($op == 'output footer') {


      if (count($store_matches) > 0) {
        // Separate out endontes and reference notes.

        if ($this->settings['footnotes_ibid']) {
          $this->ibidemify($references);
        }
        // Only if there are stored fn matches, pass the array of fns to be
        // themed as a list
        $markup = [
          '#theme' => 'bibcite_footnote_list',
          '#notes' => $store_matches,
          '#config' => $this->settings,
        ];
        $str = \Drupal::service('renderer')->render($markup, FALSE);
      }
      // Reset the static variables so they can be used again next time.
      $n = 0;
      $store_matches = [];
      $used_values = [];

      return $str;
    }

    // Default op: act as called by preg_replace_callback()
    // Random string used to ensure footnote id's are unique, even
    // when contents of multiple nodes reside on same page.
    // (fixes http://drupal.org/node/194558).
    $randstr = $this->randstr();

    $value = $this->extractAttribute($matches, 'value');
    $page = $this->extractAttribute($matches, 'page');
    $reference = $this->extractAttribute($matches, 'reference');

    if ($value) {
      // A value label was found. If it is numeric, record it in $n so further
      // notes can increment from there.
      // After adding support for multiple references to same footnote in the
      // body (http://drupal.org/node/636808) also must check that $n is
      // monotonously increasing.
      if (is_numeric($value) && $n < $value) {
        $n = $value;
      }
    }
    elseif ($opt_collapse and $value_existing = $this->findFootnote($matches[2], $reference, $store_matches)) {
      // An identical footnote already exists. Set value to the previously
      // existing value.
      $value = $value_existing;
    }
    else {
      // No value label, either a plain <fn> or unparsable attributes. Increment
      // the footnote counter, set label equal to it.
      $n++;
      $value = $n;
    }

    // Remove illegal characters from $value so it can be used as an HTML id
    // attribute.
    $value_id = preg_replace('|[^\w\-]|', '', $value);


    // Create a sanitized version of $text that is suitable for using as HTML
    // attribute value. (In particular, as the title attribute to the footnote
    // link).
    $allowed_tags = [];
    $text_clean = Xss::filter($matches['2'], $allowed_tags);
    // HTML attribute cannot contain quotes.
    $text_clean = str_replace('"', "&quot;", $text_clean);
    // Remove newlines. Browsers don't support them anyway and they'll confuse
    // line break converter in filter.module.
    $text_clean = str_replace("\n", " ", $text_clean);
    $text_clean = str_replace("\r", "", $text_clean);

    // Create a footnote item as an array.
    $fn = [
      'value' => $value,
      'text' => $matches[2],
      'text_clean' => $text_clean,
      'page' => $page,
      'reference' => $reference,
      'fn_id' => 'footnote' . $value_id . '_' . $randstr,
      'ref_id' => 'footnoteref' . $value_id . '_' . $randstr,
    ];

    // We now allow to repeat the footnote value label, in which case the link
    // to the previously existing footnote is returned. Content of the current
    // footnote is ignored. See http://drupal.org/node/636808 .
    if (!in_array($value, $used_values)) {
      // This is the normal case, add the footnote to $store_matches.
      // Store the footnote item.
      array_push($store_matches, $fn);
      array_push($used_values, $value);
    }
    else {
      // A footnote with the same label already exists.
      // Use the text and id from the first footnote with this value.
      // Any text in this footnote is discarded.
      $i = array_search($value, $used_values);
      $fn['text'] = $store_matches[$i]['text'];
      $fn['text_clean'] = $store_matches[$i]['text_clean'];
      $fn['fn_id'] = $store_matches[$i]['fn_id'];
      // Push the new ref_id into the first occurrence of this footnote label
      // The stored footnote thus holds a list of ref_id's rather than just one
      // id.
      $ref_array = is_array($store_matches[$i]['ref_id']) ? $store_matches[$i]['ref_id'] : [$store_matches[$i]['ref_id']];
      array_push($ref_array, $fn['ref_id']);
      $store_matches[$i]['ref_id'] = $ref_array;
    }

    // Return the item themed into a footnote link.
    // Drupal 7 requires we use "render element" which just introduces a wrapper
    // around the old array.
    $fn = [
      '#theme' => 'bibcite_footnote_link',
      'fn' => $fn,
    ];


    $result = \Drupal::service('renderer')->render($fn, FALSE);

    return $result;
  }

  /**
   * @inheritdoc
   */
  private function findFootnote($text, $reference, &$store_matches) {
    if (!empty($store_matches)) {
      foreach ($store_matches as &$fn) {
        if ($fn['text'] == $text && $fn['reference'] == $reference) {
          return $fn['value'];
        }
      }
    }
    return FALSE;
  }

  /**
   * @param $matches
   * @param $value_match
   *
   * @return string
   */
  protected function extractAttribute($matches, $attribute): string {
    $value = '';
    // Did the pattern match anything in the <fn> tag?
    if ($matches[1]) {
      // See if value attribute can parsed, either well-formed in quotes eg
      // <fn value="3">.
      if (preg_match('|' . $attribute . '=["\'](.*?)["\']|', $matches[1], $value_match)) {
        $value = $value_match[1];
        // Or without quotes eg <fn value=8>.
      }
      elseif (preg_match('|' . $attribute . '=(\S*)|', $matches[1], $value_match)) {
        $value = $value_match[1];
      }
    }
    return $value;
  }

  /**
   * Determine references that are the same as one above it
   * to be replaced with the string 'Ibid'.
   *
   * @param array $footnotes
   */
  protected function ibidemify(&$footnotes) {
    $prev_reference_id = FALSE;
    foreach ($footnotes as $index => $fn) {
      if ($prev_reference_id) {
        if ($fn['reference'] == $prev_reference_id) {
          $footnotes[$index]['ibid'] = TRUE;
          continue;
        }
      }
      $prev_reference_id = $fn['reference'];
    }
  }
}
