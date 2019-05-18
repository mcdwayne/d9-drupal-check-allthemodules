<?php
/**
 * @file
 * Contains \Drupal\powertagging_corpus\Form\PowerTaggingCorpusAddContentForm.
 */

namespace Drupal\powertagging_corpus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging\Plugin\Field\FieldType\PowerTaggingTagsItem;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\taxonomy\Entity\Vocabulary;

class PowerTaggingCorpusAddContentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'powertagging_corpus_add_content_form';
  }

  /**
   * {@inheritdoc}
   *
   * Add content to an existing PoolParty corpus.
   *
   * @param \Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection $connection
   *   The PoolParty server connection to use.
   * @param string $project_id
   *   The ID of the PoolParty project to use.
   * @param string $corpus_id
   *   The corpus to add the content into.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $connection = NULL, $project_id = '', $corpus_id = '') {
    if (is_null($connection) || empty($project_id) || empty($corpus_id)) {
      $form['project_settings']['errors'] = array(
        '#type' => 'item',
        '#markup' => '<div class="messages error">' . t('The parameters provided are incorrect.') . '</div>',
      );
    }

    $corpus_id = urldecode($corpus_id);

    /** @var \Drupal\semantic_connector\Api\SemanticConnectorPPTApi $ppt_api */
    $ppt_api = $connection->getApi('PPT');

    // Check if the project exists.
    $projects = $ppt_api->getProjects();
    foreach ($projects as $project) {
      if ($project['id'] == $project_id) {

        // Check if the corpus exists.
        $corpora = $ppt_api->getCorpora($project_id);
        foreach ($corpora as $corpus) {
          if ($corpus_id == $corpus['corpusId']) {
            // The form was not yet submitted.
            if (is_null($form_state->get('confirm'))) {
              $form['connection_id'] = array(
                '#type' => 'value',
                '#value' => $connection->id(),
              );
              $form['project_id'] = array(
                '#type' => 'value',
                '#value' => $project_id,
              );
              $form['corpus_id'] = array(
                '#type' => 'value',
                '#value' => $corpus_id,
              );

              $corpus_metadata = $ppt_api->getCorpusMetadata($project_id, $corpus_id);
              if (isset($corpus_metadata['quality']) && $corpus_metadata['quality'] == 'good') {
                // Date selection.
                $form['quality_warning'] = array(
                  '#type' => 'markup',
                  '#markup' => '<div class="messages warning">' . t('The quality of the selected corpus is already good, adding more content will not improve its quality any further.') . '</div>',
                );
              }

              // Date selection.
              $form['use_date'] = array(
                '#type' => 'checkbox',
                '#title' => t('Restrict the content on time basis'),
                '#default_value' => FALSE,
              );

              $form['date_select'] = array(
                '#type' => 'fieldset',
                '#title' => t('Date restriction'),
                '#states' => array(
                  'visible' => array(
                    ':input[name="use_date"]' => array('checked' => TRUE),
                  ),
                ),
              );

              $form['date_select']['date_from'] = array(
                '#type' => 'date',
                '#title' => t('From'),
                '#description' => t('Only use content created from this day on.'),
              );

              $form['date_select']['date_to'] = array(
                '#type' => 'date',
                '#title' => t('To'),
                '#description' => t('Only use content created to this day.'),
              );

              // Content selection.
              $form['content'] = array(
                '#type' => 'fieldset',
                '#title' => t('Content to push into the corpus'),
                '#tree' => TRUE,
              );

              $form['content']['entity_types'] = array(
                '#type' => 'checkboxes',
                '#title' => t('Entity types to push into the corpus'),
                '#options' => array(
                  'node' => t('Nodes'),
                  'user' => t('Users'),
                  'taxonomy_term' => t('Taxonomy Terms'),
                ),
                '#default_value' => array(),
                '#required' => TRUE,
              );

              // Nodes.
              $form['content']['node'] = array(
                '#type' => 'fieldset',
                '#title' => t('Nodes'),
                '#states' => array(
                  'visible' => array(
                    ':input[name="content[entity_types][node]"]' => array('checked' => TRUE),
                  ),
                ),
              );

              $node_type_options = node_type_get_names();
              $form['content']['node']['node_types'] = array(
                '#type' => 'checkboxes',
                '#title' => t('Node types to push into the corpus'),
                '#options' => $node_type_options,
                '#default_value' => array(),
                '#states' => array(
                  'required' => array(
                    ':input[name="content[entity_types][node]"]' => array('checked' => TRUE),
                  ),
                ),
              );

              foreach ($node_type_options as $bundle => $node_type) {
                $bundle_fields = PowerTaggingTagsItem::getSupportedTaggingFields('node', $bundle);
                $form['content']['node'][$bundle] = array(
                  '#type' => 'checkboxes',
                  '#title' => t('Fields of node type "%nodetype" to push into the corpus', array('%nodetype' => $node_type)),
                  '#options' => $bundle_fields,
                  '#default_value' => array(),
                  '#states' => array(
                    'visible' => array(
                      ':input[name="content[node][node_types][' . $bundle . ']"]' => array('checked' => TRUE),
                    ),
                    'required' => array(
                      ':input[name="content[node][node_types][' . $bundle . ']"]' => array('checked' => TRUE),
                    ),
                  ),
                );
              }

              // Users.
              $form['content']['user'] = array(
                '#type' => 'fieldset',
                '#title' => t('Users'),
                '#states' => array(
                  'visible' => array(
                    ':input[name="content[entity_types][user]"]' => array('checked' => TRUE),
                  ),
                ),
              );

              $bundle_fields = PowerTaggingTagsItem::getSupportedTaggingFields('user', 'user');
              if (!empty($bundle_fields)) {
                $form['content']['user']['user'] = array(
                  '#type' => 'checkboxes',
                  '#title' => t('Fields of users to push into the corpus'),
                  '#options' => $bundle_fields,
                  '#default_value' => array(),
                  '#states' => array(
                    'required' => array(
                      ':input[name="content[entity_types][user]"]' => array('checked' => TRUE),
                    ),
                  ),
                );
              }
              else {
                $form['content']['user']['empty'] = array(
                  '#type' => 'markup',
                  '#markup' => t('<div class="messages warning">' . t('Users currently don\'t have any fields, which could be used as data for the corpus.') . '</div>'),
                );
              }

              // Taxonomy Terms.
              $form['content']['taxonomy_term'] = array(
                '#type' => 'fieldset',
                '#title' => t('Taxonomy Terms'),
                '#states' => array(
                  'visible' => array(
                    ':input[name="content[entity_types][taxonomy_term]"]' => array('checked' => TRUE),
                  ),
                ),
              );

              $vocabularies = Vocabulary::loadMultiple();
              $vocabulary_options = array();
              /** @var Vocabulary $vocabulary */
              foreach ($vocabularies as $vocabulary) {
                $vocabulary_options[$vocabulary->id()] = $vocabulary->label();
              }
              $form['content']['taxonomy_term']['vocabularies'] = array(
                '#type' => 'checkboxes',
                '#title' => t('Vocabularies to push into the corpus'),
                '#options' => $vocabulary_options,
                '#default_value' => array(),
                '#states' => array(
                  'required' => array(
                    ':input[name="content[entity_types][taxonomy_term]"]' => array('checked' => TRUE),
                  ),
                ),
              );

              /** @var Vocabulary $vocabulary */
              foreach ($vocabularies as $vocabulary) {
                $bundle = $vocabulary->id();
                $bundle_fields = PowerTaggingTagsItem::getSupportedTaggingFields('taxonomy_term', $vocabulary->id());
                $form['content']['taxonomy_term'][$bundle] = array(
                  '#type' => 'checkboxes',
                  '#title' => t('Fields of vocabulary "%vocabularyname" to push into the corpus', array('%vocabularyname' => $vocabulary->label())),
                  '#options' => $bundle_fields,
                  '#default_value' => array(),
                  '#states' => array(
                    'visible' => array(
                      ':input[name="content[taxonomy_term][vocabularies][' . $bundle . ']"]' => array('checked' => TRUE),
                    ),
                    'required' => array(
                      ':input[name="content[taxonomy_term][vocabularies][' . $bundle . ']"]' => array('checked' => TRUE),
                    ),
                  ),
                );
              }

              $form['entities_per_request'] = array(
                '#type' => 'textfield',
                '#title' => t('Entities per request'),
                '#description' => t('The number of entities, that get processed during one HTTP request. (Allowed value range: 1 - 100)') . '<br />' . t('The higher this number is, the less HTTP requests have to be sent to the server until the batch finished pushing ALL your entities into the corpus, what results in a shorter duration of the bulk pushing process.') . '<br />' . t('Numbers too high can result in a timeout, which will break the whole bulk pushing process.') . '<br />' . t('If entities are configured to get pushed with uploaded files, a value of 5 or below is recommended.'),
                '#required' => TRUE,
                '#default_value' => '10',
              );

              // Save and cancel buttons.
              $form['submit'] = array(
                '#type' => 'submit',
                '#value' => t('Push content'),
              );
              $form['cancel'] = array(
                '#type' => 'link',
                '#title' => t('Cancel'),
                '#href' => 'admin/config/semantic-drupal/powertagging/powertagging-corpus',
                '#suffix' => '</div>',
              );

              return $form;
            }
            // The form was already submitted --> display the confirm form.
            else {
              return \Drupal::formBuilder()->getForm(PowerTaggingCorpusAddContentConfirmForm::class, $form_state, array(
                  'connection' => $connection,
                  'project' => $project,
                  'corpus' => $corpus,
                )
              );
            }
          }
        }
        drupal_set_message(t('The selected corpus could not be found in the PoolParty project.'), 'error');
        break;
      }
    }
    drupal_set_message(t('The selected project could not be found on the PoolParty server.'), 'error');
    $form_state->setRedirectUrl(Url::fromRoute('powertagging_corpus.overview'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // The form was not yet committed.
    if (is_null($form_state->get('confirm'))) {
      $entity_types = array_filter($values['content']['entity_types']);
      if (!empty($entity_types)) {
        $content_selected = [];
        foreach ($entity_types as $entity_type) {
          switch ($entity_type) {
            case 'node':
              $node_types = array_filter($values['content'][$entity_type]['node_types']);
              if (empty($node_types)) {
                $form_state->setErrorByName('content[node][node_types]', t('Please select at least one node type.'));
              }
              else {
                foreach ($node_types as $node_type) {
                  $field_values = array_filter($values['content'][$entity_type][$node_type]);
                  if (empty($field_values)) {
                    $form_state->setErrorByName('content[node][node_types]', t('Please select at least one field for node type "%nodetype".', array('%nodetype' => $node_type)));
                  }
                  else {
                    $content_selected[$entity_type][$node_type] = $field_values;
                  }
                }
              }
              break;

            case 'user':
              // If users don't have any valid fields ignore the entity type
              // selection "users".
              if (isset($values['content'][$entity_type])) {
                $field_values = array_filter($values['content'][$entity_type]['user']);
                if (empty($field_values)) {
                  $form_state->setErrorByName('content[user][user]', t('Please select at least one field for users.'));
                }
                else {
                  $content_selected[$entity_type]['user'] = $field_values;
                }
              }
              break;

            case 'taxonomy_term':
              $vocabularies = array_filter($values['content'][$entity_type]['vocabularies']);
              if (empty($vocabularies)) {
                $form_state->setErrorByName('content[taxonomy_term][vocabularies]', t('Please select at least one vocabulary.'));
              }
              else {
                foreach ($vocabularies as $vocabulary) {
                  $field_values = array_filter($values['content'][$entity_type][$vocabulary]);
                  if (empty($field_values)) {
                    $form_state->setErrorByName('content[taxonomy_term][vocabularies]', t('Please select at least one field for vocabulary "%vocabulary".', array('%vocabulary' => $vocabulary)));
                  }
                  else {
                    $content_selected[$entity_type][$vocabulary] = $field_values;
                  }
                }
              }
              break;
          }
        }
        $form_state->setValue('content_selected', $content_selected);
      }

      if (isset($values['inform_user']) && !empty($values['inform_user']) && !empty($values['inform_user_email']) && !\Drupal::service('email.validator')->isValid($values['inform_user_email'])) {
        $form_state->setErrorByName('inform_user_email', t('Please enter a valid email address.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The form gets submitted --> move on to a confirmation step.
    if (is_null($form_state->get('confirm'))) {
      $form_state->set('storage_values', $form_state->getValues());
      $form_state->set('confirm', TRUE);
      $form_state->setRebuild(TRUE);
    }
    // The confirmation form was submitted, start the batch operation.
    else {
      $values = $form_state->get('storage_values');
      $entities_per_request = $values['entities_per_request'];
      $batch = array(
        'title' => t('Push entities into the corpus'),
        'operations' => array(),
        'init_message' => t('Pushing the selected content into the corpus.'),
        'progress_message' => '',
        'finished' => array('\Drupal\powertagging_corpus\PowerTaggingCorpusBatches', 'pushEntityFinished'),
      );

      $start_date = 0;
      $end_date = 0;
      if ($values['use_date']) {
        $start_date = strtotime($values['date_from']['year'] . '-' . $values['date_from']['month'] . '-' . $values['date_from']['day']);
        $end_date = strtotime($values['date_to']['year'] . '-' . $values['date_to']['month'] . '-' . $values['date_to']['day']);
      }

      $entities_info = array();
      foreach ($values['content_selected'] as $entity_type => $content_types_fields) {
        foreach (array_keys($content_types_fields) as $content_type) {
          $bundle = $content_type;

          switch ($entity_type){
            case "node":
              $query = \Drupal::entityQuery('node');
              $query->condition('type', $content_type);
              break;

            case "taxonomy_term":
              $query = \Drupal::entityQuery('taxonomy_term');
              $query->condition('vid', $content_type);
              break;

            // Users.
            default:
              $query = \Drupal::entityQuery('user');
          }

          if ($entity_type != 'taxonomy_term' && $values['use_date']) {
            $query->condition('created', $start_date, '>=');
            $query->condition('created', $end_date, '<=');
          }

          $entity_ids = $query->execute();
          foreach ($entity_ids as $entity_id) {
            $entities_info[] = array(
              'id' => $entity_id,
              'entity_type' => $entity_type,
              'content_type' => $content_type,
              'bundle' => $bundle,
            );
          }
        }
      }

      $corpus_details = array(
        'connection_id' => $values['connection_id'],
        'project_id' => $values['project_id'],
        'corpus_id' => $values['corpus_id'],
      );

      $batch_infos = array(
        'total' => count($entities_info),
        'start_time' => time(),
      );

      for ($i = 0; $i < count($entities_info); $i += $entities_per_request) {
        $entities_info_slice = array_slice($entities_info, $i, $entities_per_request);
        $batch['operations'][] = array(
          array('\Drupal\powertagging_corpus\PowerTaggingCorpusBatches', 'pushEntity'),
          array(
            $entities_info_slice,
            $values['content_selected'],
            $corpus_details,
            $batch_infos,
          ),
        );
      }

      batch_set($batch);
    }
  }
}