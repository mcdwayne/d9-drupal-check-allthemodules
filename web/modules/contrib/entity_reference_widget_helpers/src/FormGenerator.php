<?php

namespace Drupal\entity_reference_widget_helpers;

use Drupal\Core\Url;

/**
 *
 */
class FormGenerator {

  /**
   * Convert an autocomplete widget to a <select>.
   *
   * @param [type] $element
   * @param [type] $form_state
   * @param [type] $context
   * @param [type] $count
   *
   * @return element
   */
  public static function useDropdown($element, $form_state, $context, $max_count = 0) {
    $entity_helper = \Drupal::service('entity_reference_widget_helpers.entity_helper');
    // @todo: need a plain autocomplete replace

    // Ief add existing autocomplete.
    if (isset($element['form']['entity_id'])) {
      // Lookup the options.
      $target = $element['form']['entity_id']['#target_type'];
      $bundles = $element['form']['entity_id']['#selection_settings']['target_bundles'];
      $options = $entity_helper->getOptions($target, $bundles);
      $options_count = count($options);
      // Nothing there? Return unchanged.
      if ($options_count == 0) {
        return $element;
      }
      // Max count exceeded? Return unchanged.
      if ($max_count > 0 && $options_count > $max_count) {
        return $element;
      }
      $title = $element['form']['entity_id']['#title'];
      $required = $element['form']['entity_id']['#required'];
      $element['form']['entity_id'] = [
        '#type' => 'select',
        '#options' => $options,
        '#title' => $title,
        '#required' => $required,
      ];
      // Disable the grouping options.
      foreach ($options as $id => $opt) {
        $element['form']['entity_id'][$id] = [
          '#attributes' => [
            '#disabled' => 'disabled',
          ],
        ];
      }
    }

    return $element;
  }

  /**
   * Add a link to a collection.
   *
   * @param [type] $element
   * @param [type] $form_state
   * @param [type] $context
   *
   * @return element
   */
  public static function collectionLink($element, $form_state, $context) {
    $entity_manager = \Drupal::entityManager();
    $field_settings = $context['items']->getSettings();
    $urls = [];
    // Pop open links in new window.
    $link_attrs = [
      'target' => '_blank',
    ];
    if ($field_settings['target_type'] == 'taxonomy_term') {
      foreach ($field_settings['handler_settings']['target_bundles'] as $bundle) {
        $urls['list_' . $bundle . '_terms'] = [
          'title' => t('Edit @bundle terms',
            ['@bundle' => $entity_manager->getStorage('taxonomy_vocabulary')->load($bundle)->label()]),
          'url' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form',
            ['taxonomy_vocabulary' => $bundle]),
          'attributes' => $link_attrs,
        ];
      }
    }
    if ($field_settings['target_type'] == 'webform') {
      $urls['list_webforms'] = [
        'title' => t('Edit webforms'),
        'url' => Url::fromRoute('entity.webform.collection'),
        'attributes' => $link_attrs,
      ];
    }

    if ($urls) {
      // Stick link(s) in the widget suffix.
      if (!isset($element['#suffix'])) {
        $element['#suffix'] = '';
      }
      $e = [
        'collection_links' => [
          '#type' => 'dropbutton',
          '#links' => $urls,
        ],
      ];
      $rendered_collection = \Drupal::service('renderer')->render($e);
      $element['#suffix'] .= $rendered_collection;
    }

    return $element;
  }

}
