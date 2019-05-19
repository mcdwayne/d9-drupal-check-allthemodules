<?php

namespace Drupal\taxonomy_moderator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\Entity\User;

/**
 * Class TaxonomyModerationTermApproveForm.
 */
class TaxonomyModerationTermApproveForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_moderation_term_approve_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = 'pending') {
    $connection = Database::getConnection();
    $fieldsArray = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('taxonomy_moderator_field');
    $fields = $fieldsArray['node'];
    $terms = [];

    foreach (array_keys($fields) as $field) {
      $query = $connection->select('node__' . $field, 'tmfd')->condition('tmfd.' . $field . '_status', 0);
      $termField = $field . '_value';
      $query->fields('tmfd', ['entity_id', 'delta', $termField]);
      $executed = $query->execute();
      $results = $executed->fetchAll(\PDO::FETCH_OBJ);
      foreach ($results as $row) {
        $entityObj = entity_load('node', $row->entity_id);
        $bundle = $entityObj->bundle();
        $fieldConf = FieldConfig::loadByName('node', $bundle, $field)->getSettings();
        $congifVocab = $fieldConf['taxomonymoderator']['taxomonymoderator_vocabularies'];
        $vocabulary = NodeType::loadMultiple([$entityObj->getType()]);
        $terms[$row->entity_id . '__' . $row->delta . '__' . $field . '__' . $congifVocab . '__' . $row->$termField] = [
          'term_name' => '<span class="table-filter-text-source">' . $row->$termField . '</span>',
          'reference_node' => \Drupal::l($entityObj->getTitle(), Url::fromUri('internal:' . \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $row->entity_id))),
          'author' => $entityObj->getOwner()->getDisplayName(),
          'created_date' => format_date($entityObj->getCreatedTime()),
          'status' => $entityObj->isPublished(),
          'content_type' => $vocabulary[$entityObj->getType()]->label(),
          'vocabulary' => taxonomy_vocabulary_load($congifVocab)->label(),
        ];
      }
    }
    if(!empty($terms)) {
      $form['terms']['#type'] = 'table';
      $form['terms']['#js_select'] = TRUE;
      $form['terms']['#header'] = [
        'Action',
        'Term Name',
        'Title',
        'Content Type',
        'Vocabulary',
        'Author',
        'Created On',
        'Workflow state',
      ];

      foreach ($terms as $key => $value) {
        $arr = [
          'enable' => [
            '#type' => 'checkbox',
            '#title' => '',
            '#default_value' => $key,
            '#disabled' => 0,
          ],
          'term' => [
            '#markup' => $value['term_name'],
          ],
          'node_title' => [
            '#markup' => $value['reference_node'],
            '#attributes' => [
              'class' => ['table-filter-text'],
            ],
          ],
          'content_type' => [
            '#markup' => $value['content_type'],
          ],
          'vocabulary' => [
            '#markup' => $value['vocabulary'],
          ],
          'author' => [
            '#markup' => $value['author'],
          ],
          'created' => [
            '#markup' => $value['created_date'],
          ],
          'status' => [
            '#markup' => $value['status'] ? 'Published' : 'Unpublished',
          ],
        ];

        $form['terms'][$key] = $arr;
      }

      $form['submit_approve'] = [
        '#type' => 'submit',
        '#value' => $this->t('Approve'),
        '#name' => 'approve',
        '#submit' => ['::submitFormApprove'],
      ];

      $form['submit_reject'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reject'),
        '#name' => 'reject',
        '#submit' => ['::submitFormReject'],
      ];
    }
    else {
      $form['terms'] = [
        '#type' => 'markup',
        '#markup' => "No " . $type . " terms",
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return;    
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormReject(array &$form, FormStateInterface $form_state) {
    // Display result.
    $terms = $form_state->getValues()['terms'];
    $message = '';
    // exit.
    foreach ($terms as $key => $term) {
      if ($term['enable'] != 0) {
        $term_info = explode('__', $key);
        // Load some entity.
        $entity = entity_load('node', $term_info[0]);
        $en_ref_field_list = \Drupal::entityManager()->getFieldMapByFieldType('entity_reference');
        $entity_type = 'node';
        $entity_bundle = $entity->bundle();
        $field_name = '';
        foreach ($en_ref_field_list as $e_type => $data) {
          if (!$entity_type || ($entity_type == $e_type)) {
            foreach ($data as $f_name => $data) {
              if (!$entity_bundle || isset($data['bundles'][$entity_bundle])) {
                if (!$field_name || ($field_name == $f_name)) {
                  $en_ref_field_config = FieldStorageConfig::loadByName($e_type, $f_name);
                  if ($en_ref_field_config) {
                    $en_ref_field = $en_ref_field_config->getName();
                    $fieldConf = FieldConfig::loadByName($entity_type, $entity_bundle, $en_ref_field)->getSettings();
                    if ($fieldConf['target_type']) {
                      $target_vocabs = $fieldConf['handler_settings']['target_bundles'];
                      foreach ($target_vocabs as $vocabulary) {
                        if ($term_info[3] == $vocabulary) {
                          $properties = [
                            'name' => $term_info[4],
                            'vid' => $term_info[3],
                          ];
                          $sugField_values = $entity->get($term_info[2]);
                          foreach ($sugField_values as $delta => $field_value) {
                            if ($field_value->getValue()['value'] == $term_info[4]) {
                              $entity->get($term_info[2])[$delta]->status = 2;
                              $entity->get($term_info[2])[$delta]->last_edited_uid = User::load(\Drupal::currentUser()->id())->get('uid')->value;
                            }
                          }
                          $entity->setNewRevision(TRUE);
                          $entity->setRevisionLogMessage('Terms rejected');
                          $entity->save();
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    drupal_set_message($message . ' terms has been created successfully');
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormApprove(array &$form, FormStateInterface $form_state) {
    // Display result.
    $terms = $form_state->getValues()['terms'];
    $message = '';
    // exit.
    foreach ($terms as $key => $term) {
      if ($term['enable'] != 0) {
        $term_info = explode('__', $key);
        // Load some entity.
        $entity = entity_load('node', $term_info[0]);
        $en_ref_field_list = \Drupal::entityManager()->getFieldMapByFieldType('entity_reference');
        $entity_type = 'node';
        $entity_bundle = $entity->bundle();
        $field_name = '';
        foreach ($en_ref_field_list as $e_type => $data) {
          if (!$entity_type || ($entity_type == $e_type)) {
            foreach ($data as $f_name => $data) {
              if (!$entity_bundle || isset($data['bundles'][$entity_bundle])) {
                if (!$field_name || ($field_name == $f_name)) {
                  $en_ref_field_config = FieldStorageConfig::loadByName($e_type, $f_name);
                  if ($en_ref_field_config) {
                    $en_ref_field = $en_ref_field_config->getName();
                    $fieldConf = FieldConfig::loadByName($entity_type, $entity_bundle, $en_ref_field)->getSettings();
                    if ($fieldConf['target_type']) {
                      $target_vocabs = $fieldConf['handler_settings']['target_bundles'];
                      foreach ($target_vocabs as $vocabulary) {
                        if ($term_info[3] == $vocabulary) {
                          $properties = [
                            'name' => $term_info[4],
                            'vid' => $term_info[3],
                          ];
                          $term = Term::create($properties);
                          if ($term->save()) {
                            $message = $message . ' ' . $term_info[4];
                          }
                          else {
                            drupal_set_message('Error in creating term ' . $term_info[4]);
                          }
                          $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadByProperties($properties);
                          $term = reset($terms);
                          $term_id = !empty($term) ? $term->id() : 0;
                          $entity->get($en_ref_field)->appendItem(['target_id' => $term_id]);
                          $sugField_values = $entity->get($term_info[2]);
                          foreach ($sugField_values as $delta => $field_value) {
                            if ($field_value->getValue()['value'] == $term_info[4]) {
                              $entity->get($term_info[2])[$delta]->status = 1;
                              $entity->get($term_info[2])[$delta]->last_edited_uid = User::load(\Drupal::currentUser()->id())->get('uid')->value;
                            }
                          }
                          $entity->setNewRevision(TRUE);
                          $entity->setRevisionLogMessage('Terms approved');
                          $entity->save();
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    drupal_set_message($message . ' terms has been created successfully');

  }

}
