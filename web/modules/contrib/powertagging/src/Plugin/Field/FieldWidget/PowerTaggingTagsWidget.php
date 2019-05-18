<?php
/**
 * @file
 * Contains \Drupal\powertagging\Plugin\Field\FieldWidget\PowerTaggingTagsWidget
 */

namespace Drupal\powertagging\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging\Plugin\Field\FieldType\PowerTaggingTagsItem;
use Drupal\powertagging\PowerTagging;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'powertagging_tags_default' widget.
 *
 * @FieldWidget(
 *   id = "powertagging_tags_default",
 *   label = @Translation("Term extraction"),
 *   field_types = {
 *     "powertagging_tags"
 *   },
 *   multiple_values = TRUE
 * )
 */
class PowerTaggingTagsWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $langcode = isset($storage['langcode']) ? $storage['langcode'] : '';
    $field_settings = $this->getFieldSettings();
    $powertagging_config = PowerTaggingConfig::load($field_settings['powertagging_id']);
    $powertagging_config_settings = $powertagging_config->getConfig();

    // Show the legend.
    $legend_types = [
      'concept' => t('Concepts from the thesaurus'),
      'freeterm' => t('Free terms'),
      'disabled' => t('Already selected tags'),
    ];
    $legend = '<div class="powertagging-legend">';
    foreach ($legend_types as $type => $label) {
      $legend .= '<div class="powertagging-legend-item"><span id="powertagging-legend-item-colorbox-' . $type . '" class="powertagging-legend-item-colorbox">&nbsp;</span>' . $label . '</div>';
    }
    $legend .= '</div>';
    $element['legend'] = [
      '#type' => 'item',
      '#markup' => $legend,
    ];

    // Get the selected tag IDs.
    $tag_ids = [];
    /** @var PowerTaggingTagsItem $item */
    foreach ($items as $item) {
      $item_value = $item->getValue();
      if ($item_value['target_id'] !== NULL) {
        $tag_ids[] = $item_value['target_id'] . '#' . $item_value['score'];
      }
    }
    $tag_string = implode(',', $tag_ids);

    // Get the default tags if required.
    $default_terms = [];
    if (empty($tag_ids) && !empty($field_settings['default_tags_field'])) {
      $tag_entity = $items->getEntity();
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $tagfield_items */
      $tagfield_items = $tag_entity->{$field_settings['default_tags_field']};
      if ($tagfield_items->count()) {
        $default_tags_info_field = FieldStorageConfig::loadByName($tag_entity->getEntityTypeId(), $field_settings['default_tags_field']);
        $keys = array_keys($default_tags_info_field->getColumns());

        $default_tag_ids = [];
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
        foreach ($tagfield_items as $item) {
          if (($target_id = $item->getValue()[$keys[0]]) !== NULL) {
            $default_tag_ids[] = $target_id;
          }
        }
        $terms = Term::loadMultiple($default_tag_ids);
        /** @var Term $term */
        foreach ($terms as $term) {
          // The term doesn't have to be created.
          /*if ($term->getVocabularyId() === $powertagging_config_settings['project']['taxonomy_id']) {
            $tag_ids[] = $term->id() . '#100';
          }*/

          // The term has to be created in the correct taxonomy.
          if ($term->hasField('field_uri') && $term->get('field_uri')->count()) {
            $default_terms[] = $term->getName() . '|' . $term->get('field_uri')->getString();
          }
          else {
            $default_terms[] = $term->getName() . '|';
          }
        }
        $tag_string = implode(',', $default_terms);
      }
    }

    // Create hidden list for the selected tags.
    $element['tag_string'] = [
      '#type' => 'hidden',
      '#maxlength' => 32000,
      '#default_value' => !empty($tag_string) ? $tag_string : NULL,
      '#element_validate' => [[$this, 'validateTags']],
      '#attributes' => [
        'class' => ['powertagging_tag_string'],
      ],
    ];

    // Show the form field.
    $element['powertagging'] = [
      '#type' => 'fieldset',
      '#title' => $element['#title'],
    ];

    // Add a field to display an error if the selected language is not
    // supported.
    $error_markup = t('Tagging is not possible for the currently selected language.');
    if (\Drupal::currentUser()->hasPermission('administer powertagging')) {
      $link = Link::createFromRoute(t('PowerTagging configuration'), 'entity.powertagging.edit_config_form', ['powertagging' => $field_settings['powertagging_id']]);
      $error_markup .= '<br />' . t('Select a PoolParty language in your @powertagging_config.', ['@powertagging_config' => $link->toString()]);
    }
    $element['powertagging']['language_error'] = [
      '#type' => 'item',
      '#markup' => '<div class="messages messages--warning">' . $error_markup . '</div>',
    ];

    $element['powertagging']['manual'] = [
      '#type' => 'textfield',
      '#title' => t('Add tags manually'),
      '#description' => t('The autocomplete mechanism will suggest concepts from the thesaurus.'),
      '#attributes' => [
        'class' => ['powertagging_autocomplete_tags', 'form-autocomplete'],
      ],
    ];

    // Check if the Visual Mapper has to be added.
    $add_visual_mapper = FALSE;
    if ($powertagging_config_settings['project']['mode'] == 'annotation' && isset($field_settings['browse_concepts_charttypes'])) {
      $chart_types = array_values(array_filter($field_settings['browse_concepts_charttypes']));
      $add_visual_mapper = (!empty($chart_types) && SemanticConnector::visualMapperUsable());
    }

    if ($add_visual_mapper) {
      $element['powertagging']['browse_tags'] = array(
        '#value' => t('Browse tags'),
        '#type' => 'button',
        '#attributes' => array(
          'class' => array('powertagging-browse-tags'),
        ),
      );

      // Using #children prevents stripping of attributes and form elements.
      $element['powertagging']['browse_tags_area'] = array(
        '#children' => '
<div class="powertagging-browse-tags-area" style="display:none;">
  <div class="powertagging-browse-tags-search">
    <label>Search concept</label>
    <input type="text" class="powertagging-browse-tags-search-ac form-text" />
  </div>
  <div class="powertagging-browse-tags-selection">
    <p>Tags to add:</p>
    <div class="powertagging-browse-tags-selection-results"></div>
     <button class="powertagging-browse-tags-selection-save" type="button">Save</button>
     <button class="powertagging-browse-tags-selection-cancel" type="button">Cancel</button>
  </div>
  <div class="powertagging-browse-tags-vm"></div>
</div>'
      );
    }

    $element['powertagging']['tags_result'] = [
      '#type' => 'item',
      '#title' => t('Your selected tags'),
      '#markup' => '<div class="powertagging-tag-result"><div class="no-tags">' . t('No tags selected') . '</div></div>',
    ];

    $element['powertagging']['tags'] = [
      '#type' => 'item',
      '#title' => t('Tags extracted from'),
      '#markup' => '<div class="ajax-progress-throbber"><div class="throbber">' . t('Loading...') . '</div></div><div class="powertagging-extracted-tags"></div>',
    ];

    $element['powertagging']['get_tags'] = [
      '#value' => t('Get tags'),
      '#type' => 'button',
      '#attributes' => array(
        'class' => array('powertagging-get-tags'),
      ),
    ];

    // Attach the libraries.
    $element['#attached'] = [
      'library' => [
        'powertagging/widget',
      ],
      'drupalSettings' => [
        'powertagging' => $this->getJavaScriptSettings($tag_ids, $default_terms, $langcode),
      ],
    ];

    // Add the Visual Mapper if required.
    if ($add_visual_mapper) {
      $element['#attached']['library'][] = 'semantic_connector/visual_mapper';

      /*$element['#attached']['library'] = array(
        array('system', 'ui.dialog'),
      );*/
    }

    return $element;
  }

  /**
   * Converts the comma separated list from the tag_string into the expected
   * array for the multiple target_id.
   *
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (empty($values['tag_string'])) {
      return NULL;
    }
    $tags = explode(',', $values['tag_string']);
    $values = [];
    foreach ($tags as $tag) {
      list($tag_id, $score) = explode('#', $tag);
      $values[] = ['target_id' => $tag_id, 'score' => $score];
    }

    return $values;
  }

  /**
   * Validation handler for the PowerTagging Tags field.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic input element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateTags(array $element, FormStateInterface &$form_state) {
    $error = FALSE;

    // Only check if value is not empty.
    if (empty($element['#value'])) {
      return;
    }

    $selected_tags = explode(',', $element['#value']);
    $tids = [];
    $tags = [];
    $new_terms = [];
    $new_terms_score = [];

    // Check if all tids are integer-values or new freeterms.
    foreach ($selected_tags as $tag) {
      list($term, $score) = explode('#', $tag);
      if (is_numeric($term) && (intval($term) == floatval($term))) {
        $tids[] = $term;
        $tags[] = $tag;
      }
      elseif (is_string($term)) {
        if (strpos($term, '|')) {
          $new_terms[] = $term;
          $new_terms_score[$term] = $score;
        }
      }
      else {
        $error = TRUE;
      }
    }

    $storage_settings = $this->fieldDefinition->getFieldStorageDefinition()
      ->getSettings();
    $powertagging_id = $storage_settings['powertagging_id'];
    /** @var PowerTaggingConfig $powertagging_config */
    $powertagging_config = PowerTaggingConfig::load($powertagging_id);
    $config = $powertagging_config->getConfig();
    $powertagging = new PowerTagging($powertagging_config);

    // Get language.
    $langcode = $form_state->getValue('langcode');
    $langcode = $langcode[0]['value'];

    // Check if all the terms are still existent if there was no error till now.
    if (!$error && count($tids)) {
      $terms = Term::loadMultiple($tids);
      // All of the terms are existent.
      if (count($terms) != count($tids)) {
        $error = TRUE;
      }
      // Update data of existing terms if required.
      else {
        $existing_terms_by_uri = [];
        /** @var Term $existing_term */
        foreach ($terms as $existing_term) {
          if ($existing_term->hasField('field_uri') &&
            $existing_term->get('field_uri')->count()
          ) {
            $uri = $existing_term->get('field_uri')->getString();
            $existing_terms_by_uri[$uri] = $existing_term;
          }
        }

        if (!empty($existing_terms_by_uri)) {
          $concepts_details = $powertagging->getConceptsDetails(array_keys($existing_terms_by_uri), $langcode);
          foreach ($concepts_details as $concept_detail) {
            if (isset($existing_terms_by_uri[$concept_detail['uri']])) {
              $existing_term = $existing_terms_by_uri[$concept_detail['uri']];
              $term_data_changed = $powertagging->updateTaxonomyTermDetails($existing_term, (object) $concept_detail);
              // Only save the taxonomy term if any information has changed.
              if ($term_data_changed) {
                $existing_term->save();
              }
            }
          }
        }
      }
    }

    // If there is no error at all, add taxonomy terms for the new freeterms.
    if (!$error && count($new_terms)) {
      $vid = $config['project']['taxonomy_id'];
      $new_term_ids = $powertagging->addTaxonomyTerms($vid, $new_terms, $langcode);
      foreach ($new_term_ids as $term => $new_term_id) {
        $tags[] = $new_term_id . '#' . $new_terms_score[$term];
      }
      $form_state->setValue($element['#parents'], implode(',', $tags));
    }

    if ($error) {
      $form_state->setErrorByName($element['#name'], t('Invalid tag selection.'));
    }
  }

  /**
   * Returns the PowerTagging settings for the drupalSettings.
   *
   * @param array $tag_ids
   *   The list of connected tag IDs.
   * @param array $default_terms
   *   The list of default terms.
   * @param string $langcode
   *   The language of the entity.
   *
   * @return array The javascript settings.
   *   The Drupal settings.
   */
  protected function getJavaScriptSettings(array $tag_ids, array $default_terms, $langcode) {
    $field = $this->fieldDefinition;
    $field_settings = $this->getFieldSettings();
    $powertagging_config = PowerTaggingConfig::load($field_settings['powertagging_id'])
      ->getConfig();
    $limits = empty($field->getSetting('limits')) ? $powertagging_config['limits'] : $field->getSetting('limits');

    // Set the existing concepts and free terms.
    $selected_tags = [];
    if (!empty($tag_ids)) {
      foreach ($tag_ids as $tag) {
        list($tag_id, $score) = explode('#', $tag);
        $term = Term::load($tag_id);
        if (!is_null($term)) {
          $selected_tags[] = [
            'tid' => $term->id(),
            'uri' => $term->get('field_uri')->getString(),
            'label' => $term->getName(),
            'type' => empty($term->get('field_uri')
              ->getString()) ? 'freeterm' : 'concept',
            'score' => $score,
          ];
        }
      }
    }

    // Set the default term if available.
    if (!empty($default_terms)) {
      foreach ($default_terms as $term) {
        list($label, $uri) = explode('|', $term);
        $selected_tags[] = [
          'tid' => 0,
          'uri' => $uri,
          'label' => $label,
          'type' => (empty($uri) ? 'freeterm' : 'concept'),
          'score' => 100,
        ];
      }
    }

    // Sort the selected tags: concepts on top and free terms to the bottom.
    usort($selected_tags, [$this, 'sortSelectedTags']);

    // Get the configured project languages.
    $allowed_langcodes = [];
    foreach ($powertagging_config['project']['languages'] as $drupal_lang => $pp_lang) {
      if (!empty($pp_lang)) {
        $allowed_langcodes[] = $drupal_lang;
      }
    }

    // Check if the Visual Mapper has to be added.
    $visual_mapper_chart_types = [];
    if ($powertagging_config['project']['mode'] == 'annotation' && isset($field_settings['browse_concepts_charttypes'])) {
      $chart_types = array_values(array_filter($field_settings['browse_concepts_charttypes']));
      if (!empty($chart_types) && SemanticConnector::visualMapperUsable()) {
        $visual_mapper_chart_types = $chart_types;
      }
    }

    $file_upload_settings = $field->getSetting('file_upload');
    $settings = [];
    $settings[$field->getName()] = [
      'fields' => $this->getSelectedTaggingFields($field),
      'settings' => [
        'field_name' => $field->getName(),
        'entity_type' => $field->getTargetEntityTypeId(),
        'bundle' => $field->getTargetBundle(),
        'use_fields' => array_keys(array_filter($field_settings['fields'])),
        'powertagging_id' => $field_settings['powertagging_id'],
        'taxonomy_id' => $powertagging_config['project']['taxonomy_id'],
        'concepts_per_extraction' => $limits['concepts_per_extraction'],
        'concepts_threshold' => $limits['concepts_threshold'],
        'freeterms_per_extraction' => $limits['freeterms_per_extraction'],
        'freeterms_threshold' => $limits['freeterms_threshold'],
        'custom_freeterms' => ($powertagging_config['project']['mode'] == 'annotation' ? (!is_null($field->getSetting('custom_freeterms')) ? $field->getSetting('custom_freeterms') : TRUE) : FALSE),
        'use_shadow_concepts' => ($powertagging_config['project']['mode'] == 'annotation' ? (!is_null($field->getSetting('use_shadow_concepts')) ? $field->getSetting('use_shadow_concepts') : FALSE) : FALSE),
        'browse_concepts_charttypes' => (!empty($visual_mapper_chart_types) ? $visual_mapper_chart_types : []),
        'concept_scheme_restriction' => (isset($powertagging_config['concept_scheme_restriction']) ? $powertagging_config['concept_scheme_restriction'] : []),
        'data_properties' => $powertagging_config['data_properties'],
        // The currently used Drupal language of the entity.
        'entity_language' => $langcode,
        // An array of allowed Drupal languages.
        'allowed_languages' => $allowed_langcodes,
        'corpus_id' => $powertagging_config['project']['corpus_id'],
        'max_file_size' => (isset($file_upload_settings['max_file_size']) ? $file_upload_settings['max_file_size'] : (2 * 1048576)),
        'max_file_count' => (isset($file_upload_settings['max_file_count']) ? $file_upload_settings['max_file_count'] : 5),
        'ac_add_matching_label' => (!is_null($field->getSetting('ac_add_matching_label')) ? $field->getSetting('ac_add_matching_label') : FALSE),
        'ac_add_context' => (!is_null($field->getSetting('ac_add_context')) ? $field->getSetting('ac_add_context') : FALSE),
      ],
      'selected_tags' => $selected_tags,
    ];

    return $settings;
  }

  /**
   * Get the fields that are supported for the tagging.
   *
   * @param FieldDefinitionInterface $field
   *   The field config object.
   *
   * @return array
   *   A list of supported fields.
   */
  protected function getSelectedTaggingFields(FieldDefinitionInterface $field) {
    /** @var \Drupal\Core\Entity\EntityFieldManager $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $field_definitions = $entityFieldManager->getFieldDefinitions($field->getTargetEntityTypeId(), $field->getTargetBundle());
    $supported_field_types = PowerTaggingTagsItem::getSupportedFieldTypes();
    $supported_fields = [];

    $field_settings = $this->getFieldSettings();
    $selected_fields = array_filter($field_settings['fields']);

    switch ($field->getTargetEntityTypeId()) {
      case 'node':
        if (isset($selected_fields['title'])) {
          $supported_fields[] = [
            'field_name' => 'title',
            'module' => 'core',
            'widget' => 'string_textfield',
          ];
        }
        break;

      case 'taxonomy_term':
        if (isset($selected_fields['name'])) {
          $supported_fields[] = [
            'field_name' => 'name',
            'module' => 'core',
            'widget' => 'string_textfield',
          ];
        }
        if (isset($selected_fields['description'])) {
          $supported_fields[] = [
            'field_name' => 'description',
            'module' => 'text',
            'widget' => 'text_textarea',
          ];
        }
        break;
    }

    // Get the form display to check which widgets are used.
    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($field->getTargetEntityTypeId() . '.' . $field->getTargetBundle() . '.' . 'default');

    foreach ($field_definitions as $field_definition) {
      if (!$field_definition instanceof FieldConfig) {
        continue;
      }
      $field_storage = $field_definition->getFieldStorageDefinition();
      $field_name = $field_definition->getName();
      $specific_widget_type = $form_display->getComponent($field_definition->getName());
      if (isset($supported_field_types[$field_storage->getTypeProvider()][$field_storage->getType()]) && in_array($specific_widget_type['type'], $supported_field_types[$field_storage->getTypeProvider()][$field_storage->getType()])) {
        $add_field = FALSE;
        // A normal field.
        if ($field_storage->getType() !== 'entity_reference') {
          if (in_array($field_name, $selected_fields)) {
            $add_field = TRUE;
          }
        }
        // A referenced entity.
        else {
          foreach ($selected_fields as $selected_field) {
            if (strpos($selected_field, $field_name . '|') === 0) {
              $add_field = TRUE;
              break;
            }
          }
        }

        if ($add_field) {
          $supported_fields[] = [
            'field_name' => $field_name,
            'module' => $field_storage->getTypeProvider(),
            'widget' => $specific_widget_type['type'],
          ];
        }
      }
    }

    return $supported_fields;
  }

  /**
   * Callback function to sort the selected tags.
   *
   * Sort the selected tags: concepts on top and free terms to the bottom.
   */
  protected function sortSelectedTags($a, $b) {
    if ($a['type'] == $b['type']) {
      $score_diff = $b['score'] - $a['score'];
      if ($score_diff == 0) {
        return strcasecmp($a['label'], $b['label']);
      }
      return $score_diff;
    }

    return ($a['type'] == 'freeterm') ? 1 : -1;
  }

}
