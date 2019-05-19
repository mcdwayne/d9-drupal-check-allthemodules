<?php

namespace Drupal\taxonomy_moderator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;

/**
 * Class TaxonomyModerationTermReportForm.
 */
class TaxonomyModerationTermReportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_moderation_term_approve_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = 'approved') {
    $connection = Database::getConnection();
    $fieldsArray = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('taxonomy_moderator_field');
    $fields = $fieldsArray['node'];
    $terms = [];
    $termStatus = ($type == 'approved') ? 1 : 2;
    foreach (array_keys($fields) as $field) {
      $query = $connection->select('node__' . $field, 'tmfd')->condition('tmfd.' . $field . '_status', $termStatus);
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

}
