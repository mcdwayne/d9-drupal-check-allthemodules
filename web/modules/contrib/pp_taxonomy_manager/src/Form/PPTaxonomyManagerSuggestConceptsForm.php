<?php
/**
 * @file
 * Contains \Drupal\pp_taxonomy_manager\Form\PPTaxonomyManagerSuggestConceptsForm.
 */

namespace Drupal\pp_taxonomy_manager\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfig;
use Drupal\pp_taxonomy_manager\PPTaxonomyManager;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The confirmation-form for the export of a taxonomy to a PoolParty server.
 */
class PPTaxonomyManagerSuggestConceptsForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_taxonomy_manager_suggest_concepts_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\powertagging\Entity\PowerTaggingConfig $powertagging_config
   *   The PowerTagging configuration to suggest concepts for.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $powertagging_config = NULL) {
    $database_connection = \Drupal::database();
    // Check if PowerTagging configuration exists.
    if (is_null($powertagging_config)) {
      drupal_set_message(t('The selected PowerTagging configuration does not exists.'), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.suggest_concepts_config_list')->toString());
    }

    $form_state->set('powertagging_config', $powertagging_config);

    /** @var \Drupal\semantic_connector\Api\SemanticConnectorPPTApi $ppt_api */
    $ppt_api = $powertagging_config->getConnection()->getApi('PPT');

    // Get previously suggested concepts.
    $suggested_concepts = $ppt_api->getSuggestedConcepts($powertagging_config->getProjectId());
    $suggested_concepts_labels = [];
    foreach ($suggested_concepts as $suggested_concept) {
      $suggested_concepts_labels[] = $suggested_concept['prefLabels'][0]['label'];
    }

    // Get the entity types with PowerTagging field.
    $fields = $powertagging_config->getFields();

    // Count the free terms of the PowerTagging configuration.
    $field_names = [];
    foreach ($fields as $field) {
      $field_names[$field['entity_type_id'] . '__' . $field['field_type']] = $field['label'] . ' (' . $field['entity_type_id'] . ')';
    }

    $field_name_query = NULL;
    foreach ($fields as $field) {
      $db_field_name = $field['entity_type_id'] . '__' . $field['field_type'];
      if ($form_state->hasValue('field_filter')) {
        $selected_fields = array_filter($form_state->getValue('field_filter'));
        if (!empty($selected_fields) && !in_array($db_field_name, $selected_fields)) {
          continue;
        }
      }

      $current_field_query = $database_connection->select($db_field_name, 'fd');
      $current_field_query->addField('fd', $field['field_type'] . '_target_id', 'tid');
      if (!is_null($field_name_query)) {
        $current_field_query->union($field_name_query);
      }
      $field_name_query = $current_field_query;
    }

    $term_query = $database_connection->select('taxonomy_term_field_data', 't')
      ->fields('t', array('tid', 'name'));
    if ($form_state->hasValue('text_filter') && !empty($form_state->getValue('text_filter'))) {
      $term_query->condition('t.name', '%' . $database_connection->escapeLike($form_state->getValue('text_filter')) . '%', 'LIKE');
    }
    if (!empty($suggested_concepts_labels)) {
      $term_query->condition('t.name', $suggested_concepts_labels, 'NOT IN');
    }

    $term_query->addExpression('COUNT(t.tid)', 'count');
    $term_query->addJoin('', $field_name_query, 'fd', 't.tid = fd.tid');
    $term_query->leftJoin('taxonomy_term__field_uri', 'u', 't.tid = u.entity_id');

    $term_query->isNull('u.field_uri_uri');
    $term_query->groupBy('t.tid');
    $term_query->groupBy('t.name');
    $term_query->orderBy('count', 'DESC');
    $term_query->orderBy('t.name', 'ASC');
    $term_query->range(0, 25);

    $terms = $term_query->execute()
      ->fetchAllAssoc('tid');

    $term_options = [];
    if (!empty($terms)) {
      $form['term_checkboxes'] = array(
        '#tree' => TRUE,
      );
      foreach ($terms as $term) {
        $term_options[$term->tid] = [
          'name' => Link::fromTextAndUrl($term->name, Url::fromRoute('entity.taxonomy_term.edit_form', array('taxonomy_term' => $term->tid))),
          'count' => $term->count,
        ];
      }
    }

    if (!empty($term_options)) {
      if (count($field_names) > 1) {

        $form['field_filter'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Filter by field'),
          '#options' => $field_names,
          '#ajax' => array(
            'callback' => '::listUpdate',
            'wrapper' => 'freeterm-list-wrapper',
            'method' => 'replace',
          ),
        );
      }

      $form['text_filter'] = array(
        '#type' => 'textfield',
        '#title' => t('Search for free-term'),
        '#ajax' => array(
          'callback' => '::listUpdate',
          'event' => 'change',
          'wrapper' => 'freeterm-list-wrapper',
          'method' => 'replace',
          'keypress'  =>  TRUE,
        ),
      );
    }

    $form['freeterm_list'] = array(
      '#type' => 'tableselect',
      '#prefix' => '<div id="freeterm-list-wrapper">',
      '#suffix' => '</div>',
      '#header' => array(
        'name' => t('Name'),
        'count' => t('Times used'),
      ),
      '#options' => $term_options,
      '#empty' => t('There are no free-terms available for this PowerTagging configuration.'),
      '#attributes' => array(
        'class' => array('pp-taxonomy-manager-suggest-concept-table'),
      ),
      '#js_select' => TRUE,
    );

    $form['#attached'] = array(
      'library' =>  array(
        'pp_taxonomy_manager/admin_area',
      ),
    );

    // Save and cancel buttons.
    $form['suggest'] = array(
      '#type' => 'submit',
      '#value' => t('Suggest'),
    );

    if (!empty($suggested_concepts)) {
      $form['suggested_concepts'] = array(
        '#type' => 'details',
        '#title' => t('Suggested Concepts'),
        '#open' => FALSE,
      );

      $suggested_concepts_rows = [];
      foreach ($suggested_concepts as $suggested_concept) {
        $suggested_concepts_rows[] = [
          $suggested_concept['prefLabels'][0]['label'],
          $suggested_concept['uri']['uri'],
          \Drupal::service('date.formatter')
            ->format(strtotime($suggested_concept['date']), 'short')
        ];
      }

      $form['suggested_concepts']['concept_table'] = array(
        '#theme' => 'table',
        '#header' => array(
          t('Name'),
          t('URI'),
          t('Suggestion date'),
        ),
        '#rows' => $suggested_concepts_rows,
        '#attributes' => array(
          'id' => 'pp-taxonomy-manager-suggested-concepts-table',
          'class' => array('semantic-connector-tablesorter'),
        ),
      );

      // Add CSS and JS.
      $form['suggested_concepts']['concept_table']['#attached'] = array(
        'library' =>  array(
          'semantic_connector/tablesorter',
        ),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $selected_freeterms = array_filter($form_state->getValue('freeterm_list'));
    if (count($selected_freeterms) == 0) {
      $form_state->setErrorByName('freeterm_list', t('Select at least one free-term from the list.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\powertagging\Entity\PowerTaggingConfig $powertagging_config */
    $powertagging_config = $form_state->get('powertagging_config');
    $term_ids = array_values(array_filter($form_state->getValue('freeterm_list')));
    $terms = Term::loadMultiple($term_ids);
    $suggest_concepts = [];
    /** @var Term $term */
    foreach ($terms as $term) {
      $suggest_concepts[] = array(
        'prefLabels' => [array(
          'label' => $term->getName(),
          'language' => $term->language()->getId(),
        )],
      );
    }

    /** @var \Drupal\semantic_connector\Api\SemanticConnectorPPTApi $ppt_api */
    $ppt_api = $powertagging_config->getConnection()->getApi('PPT');
    $result = $ppt_api->suggestConcepts($powertagging_config->getProjectId(), $suggest_concepts);
    if (empty($result)) {
      drupal_set_message(t('An error occurred while trying to suggest the concepts.'), 'error');
    }
    else {
      drupal_set_message(t('Successfully suggested the selected concepts.'));
    }
  }

  /**
   * Ajax callback function for updating the freeterm list during the concept suggestion.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface &$form_state
   *   The form_state array.
   *
   * @return array
   *   The renderable array of the freeterm list.
   */
  public function listUpdate(array &$form, FormStateInterface $form_state) {
    return $form['freeterm_list'];
  }
}
?>