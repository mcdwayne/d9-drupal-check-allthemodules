<?php

/**
 * @file
 * Contains \Drupal\powertagging\Plugin\Field\FieldFormatter\PowerTaggingTagsFormatter
 */

namespace Drupal\powertagging\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging\Plugin\Field\FieldType\PowerTaggingTagsItem;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the PowerTaggingTgs formatter.
 *
 * @FieldFormatter(
 *   id = "powertagging_tags_list",
 *   label = @Translation("Tag list"),
 *   field_types = {
 *     "powertagging_tags"
 *   }
 * )
 */
class PowerTaggingTagsFormatter extends FormatterBase {

  public static function defaultSettings() {
    return array(
      'add_hidden_info' => [],
      'tag_sort_order' => 'score',
      'freeterms_at_end' => FALSE,
    );
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->settings;

    $form['add_hidden_info'] = array(
      '#title' => t('Add hidden information'),
      '#type' => 'checkboxes',
      '#options' => array(
        'alt_labels' => t('Alternative labels'),
        'hidden_labels' => t('Hidden labels'),
      ),
      '#default_value' => isset($settings['add_hidden_info']) ? $settings['add_hidden_info'] : [],
      '#description' => t('Select the labels that will be added additionally in a hidden box to each PowerTagging Tag') . '<br />' . t('The Drupal default search is improved by indexing the corresponding node with those labels.')
    );

    $form['tag_sort_order'] = array(
      '#title' => t('Tag sort order'),
      '#type' => 'select',
      '#options' => array(
        'alphabetically' => t('Alphabetically'),
        'score' => t('By score'),
      ),
      '#default_value' => isset($settings['tag_sort_order']) ? $settings['tag_sort_order'] : 'score',
      '#description' => t('The order of the connected tags. The higher a score of a tag is, the more relevant it is for the content.'),
    );

    $form['freeterms_at_end'] = array(
      '#title' => t('Freeterms at the end'),
      '#type' => 'checkbox',
      '#default_value' => isset($settings['freeterms_at_end']) ? $settings['freeterms_at_end'] : FALSE,
      '#description' => t('Shift freeterms (concepts without a URI) to the end of the tags list.')
    );

    return $form;
  }

  public function settingsSummary() {
    $settings = $this->settings;
    $summary_parts = [];

    // Add information about the hidden labels.
    $labels = array();
    if (isset($settings['add_hidden_info']) && !empty($settings['add_hidden_info']['alt_labels'])) {
      $labels[] = t('Alternative labels');
    }
    if (isset($settings['add_hidden_info']) && !empty($settings['add_hidden_info']['hidden_labels'])) {
      $labels[] = t('Hidden labels');
    }
    $summary_parts[] = t('Hidden data: @labels', array('@labels' => (empty($labels) ? 'none' : implode(', ', $labels))));

    // Add information about the tag sort order.
    if (isset($settings['tag_sort_order']) && $settings['tag_sort_order'] == 'alphabetically') {
      $sort_order = t('Alphabetically');
    }
    else {
      $sort_order = t('By score');
    }
    $summary_parts[] = t('Tag sort order: @sortorder', array('@sortorder' => $sort_order));

    $summary_parts[] = t('Freeterms at the end: @end', array('@end' => (isset($settings['freeterms_at_end']) && $settings['freeterms_at_end']) ? t('True') : t('False')));

    return $summary_parts;
  }

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if (empty($items)) {
      return [];
    }

    $settings = $this->settings;
    $elements = NULL;
    $context = [
      'items' => $items,
      'langcode' => $langcode,
    ];
    \Drupal::moduleHandler()->alter('powertagging_tag_list', $elements, $context);

    if (is_null($elements)) {
      $elements = [];
      $tag_scores = [];
      /** @var PowerTaggingTagsItem $item */
      foreach ($items as $item) {
        $item_value = $item->getValue();
        if ($item_value['target_id'] !== NULL) {
          $tag_scores[$item_value['target_id']] = intval($item_value['score']);
        }
      }
      $terms = Term::loadMultiple(array_keys($tag_scores));
      // Sort the tags.
      usort($terms, array(new PowerTaggingTagsSorter($tag_scores, (isset($settings['tag_sort_order']) ? $settings['tag_sort_order'] : 'score'), (isset($settings['freeterms_at_end']) ? $settings['freeterms_at_end'] : FALSE)), "sort"));

      $tags_to_theme = array();
      /** @var Term $term */
      foreach ($terms as $term) {
        $uri = $term->get('field_uri')->getValue();
        $alt_labels = [];
        if ($term->hasField('field_alt_labels') && $term->get('field_alt_labels')->count()) {
          foreach ($term->get('field_alt_labels')->getValue() as $alt_label) {
            $alt_labels[] = $alt_label['value'];
          }
        }
        $hidden_labels = [];
        if ($term->hasField('field_hidden_labels') && $term->get('field_hidden_labels')->count()) {
          foreach ($term->get('field_hidden_labels')->getValue() as $hidden_label) {
            $hidden_labels[] = $hidden_label['value'];
          }
        }
        $tags_to_theme[] = array(
          'uri' => (!empty($uri) ? $uri[0]['uri'] : ''),
          'html' => \Drupal\Component\Utility\Html::escape($term->getName()),
          'alt_labels' => (isset($settings['add_hidden_info']) && !empty($settings['add_hidden_info']['alt_labels']) ? $alt_labels : []),
          'hidden_labels' => (isset($settings['add_hidden_info']) && !empty($settings['add_hidden_info']['hidden_labels']) ? $hidden_labels : []),
        );
      }
      $powertagging_config = PowerTaggingConfig::load($this->getFieldSetting('powertagging_id'));
      $elements[] = array(
        '#markup' => SemanticConnector::themeConcepts($tags_to_theme, $powertagging_config->getConnectionId(), $powertagging_config->getProjectId())
      );
    }

    return $elements;
  }
}

class PowerTaggingTagsSorter {
  private $scores;
  private $order;
  private $freeterms_at_end;

  /**
   * PowerTaggingTagsSorter constructor.
   *
   * @param array $scores
   *   An associative array of tid => score.
   * @param string $sort_by
   *   What to sort by, can be either "score" or "alphabetically"
   * @param bool $freeterms_at_end
   *   If TRUE concepts will always be listed before freeterms.
   */
  public function __construct(Array $scores, $sort_by, $freeterms_at_end = FALSE) {
    $this->scores = $scores;
    $this->order = $sort_by;
    $this->freeterms_at_end = $freeterms_at_end;
  }

  public function sort(Term $term_a, Term $term_b) {
    // Shift freeterms to the end if required.
    if ($this->freeterms_at_end) {
      $term_a_is_concept = !empty($term_a->get('field_uri')->getValue());
      $term_b_is_concept = !empty($term_b->get('field_uri')->getValue());
      if ($term_a_is_concept != $term_b_is_concept) {
        return ($term_a_is_concept) ? -1 : 1;
      }
    }

    // Sort by score.
    if ($this->order == 'score') {
      $score_diff = $this->scores[$term_b->id()] - $this->scores[$term_a->id()];
      if ($score_diff == 0) {
        return strcasecmp($term_a->getName(), $term_b->getName());
      }
      return $score_diff;
    }
    // Sort alphabetically.
    else {
      return strcasecmp($term_a->getName(), $term_b->getName());
    }
  }
}