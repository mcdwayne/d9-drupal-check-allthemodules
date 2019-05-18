<?php

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteTagsWidget;

/**
 * @file
 * Functions for manipulating taxonomy_autocomplete widget to add
 * clickable/popular tags.
 */

/**
 * Util function to inject the popular tags functionality into a form element.
 *
 * @param array $element
 *   form element
 * @param array $context
 *   form element context
 */
function _popular_tags_inject(&$element, $context) {
  if ($context['widget'] instanceof EntityReferenceAutocompleteTagsWidget) {
    $element['#attached']['library'][] = 'popular_tags/popular_tags';
    // Popular Tags settings will be stored in third_party_settings
    $settings = $context['widget']->getThirdPartySettings('popular_tags');
    $field_definition = $context['items']->getFieldDefinition();
    $field_name = $field_definition->get('field_name');
    // There may be multiple targeted vocabularies
    $vocabs = $element['target_id']['#selection_settings']['target_bundles'];
    $limit = @$settings['popular_tags']['limit'];
    $showhide = @$settings['popular_tags']['showhide'];;
    if ($showhide) {
      $data['popular_tags'][$field_name]['limit'] = $limit;
      $element['#attached']['drupalSettings'] = $data;
    }
    $terms = _popular_tags_get_terms($vocabs, $limit, $showhide);
    $tag_terms = '<div class="tag-terms">';
    $classes[] = 'term';
    if (!empty($limit) && !empty($showhide)) {
      $classes[] = 'popular';
    }
    $count = 0;
    foreach ($terms as $tid => $term) {
      $count++;
      if (!empty($limit) && !empty($showhide) && $count > $limit) {
        $key = array_search('popular', $classes);
        if ($key !== FALSE) {
          unset($classes[$key]);
        }
      }

      // a term name containing comma needs to be quoted
      if (strpos($term->name, ',') !== FALSE) {
        $term->name = '"' . $term->name . '"';
      }

      $tag_terms .= t('<a class="@classes" href="#" title="x@freq" >@term</a>', array(
        '@term' => $term->name,
        '@freq' => $term->freq,
        '@classes' => implode(' ', $classes),
      ));
    }
    $tag_terms .= '</div>';
    $html = '<div class="popular-tags">' . t('You can type your own tags, or choose from the most commonly used tags below.');
    if (!empty($limit) && !empty($showhide)) {
      $html .= '<a href="#" class="show-all-terms">' . t('Show All Terms') . '</a>';
      $html .= '<a href="#" class="show-popular-terms">' . t('Show Popular Terms') . '</a>';
    }
    $html .= $tag_terms . '</div>';
    $element['target_id']['#description'] = isset($element['#description']) ? $element['#description'] . $html : $html;
  }
}

/**
 * Util func to get the array of popular terms (key = tid, value = term name).
 *
 * @param string $vname
 *   name of the vocabulary
 * @param int $limit
 *   number of tags/terms to fetch (NULL if want default)
 * @param boolean $all
 *   flag set to TRUE if ALL tags/terms should be fetched
 *
 * @return array
 *   fetched terms
 */
function _popular_tags_get_terms($vocabs, $limit = NULL, $all = FALSE) {
  $query = \Drupal::database()->select('taxonomy_term_data', 'td');
  $query->fields('td', array('tid'));
  $query->leftJoin('taxonomy_index', 'tn', 'tn.tid = td.tid');
  $query->leftJoin('taxonomy_term_field_data', 'ttfd', 'td.tid = ttfd.tid');
  $query->fields('ttfd', array('name'));
  $query->addExpression('COUNT(tn.tid)', 'freq');
  $query->condition('td.vid', $vocabs, 'IN');
  $query->groupBy('td.tid');
  $query->groupBy('ttfd.name');
  $query->orderBy('freq', 'DESC');
  $query->orderBy('name');
  if (!empty($limit) && !$all) {
    $query->range(0, $limit);
  }
  $terms = $query->execute()->fetchAllAssoc('tid');
  return $terms;
}
