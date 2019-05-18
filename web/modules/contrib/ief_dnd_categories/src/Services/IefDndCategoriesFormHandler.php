<?php

namespace Drupal\ief_dnd_categories\Services;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Renderer;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Term;

class IefDndCategoriesFormHandler {

  /**
   * Object in charge of computing categories position in relation to inline entity form table elements.
   */
  public $categoriesHandler;

  /**
   * @var Renderer
   *   Injected render service.
   */
  protected $renderer;

  /**
   * @var Renderer
   *   Injected EntityTypeManger service used to search taxonomy terms for categories.
   */
  protected $entityTypeManager;

  /**
   * @var string $categoryField
   * Entity field containing the taxonomy category.
   */
  protected $categoryField;

  /**
   * @var string $tableField
   *   Name of a field containing the entities sortable by inline entity form.
   */
  protected $tableField;

  public function __construct($entity_type, $entity_bundle, $entity_ief_field) {
    $config = $this->getFormConfig($entity_type . '::' . $entity_bundle . '::' . $entity_ief_field);
    $this->categoryField = $config['field_category'];
    $this->tableField = $config['entity_ief_field'];
    $this->renderer = \Drupal::service('renderer');
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($config['field_category_vid']);
    $this->categoriesHandler = new IefDndCategoriesPositionHandler($terms);
  }

  /**
   * Update inline entity form table rows with DnD categories.
   *
   * @param array
   *   ief table rows.
   */
  public function updateRowsWithCategories(&$rows) {

    $this->categoriesHandler->setCategoriesPositionsFromEntityRowsData($rows);

    $categories = $this->categoriesHandler->getCategories();

    $categoryRows = [];

    foreach ($categories as $tid => $category) {
      if ($tid != '') {
        $columns = [
          0 => $category,
          'colspan' => 5
        ];
        $rowClasses = ['category-term', 'draggable'];
        if (is_null($category['position'])) {
          $rowClasses[] = 'hidden';
        }
        $categoryRows[] = [
          'data' => $columns,
          'class' => $rowClasses,
          'no_striping' => TRUE,
          'category-id' => $tid,
        ];
      }
    }

    $rows = array_merge($rows, $categoryRows);
  }

  /**
   * Updates row entities categories: weights are already updated by the default submit function.
   */
  public function submit($form, FormState $form_state) {

    $this->categoriesHandler->setCategoriesPositionsFromUserInput($form_state->getUserInput());
    $documentPageNode = $form_state->getFormObject()->getEntity();
    $entityIndexes = $documentPageNode->get($this->tableField)->getValue();
    $rowPositions = $this->categoriesHandler->getTableCategoriesFromPosition($entityIndexes);
    foreach ($rowPositions as $position => $categoryId) {
      $mediaId = $entityIndexes[$position]['target_id'];
      $media = Media::load($mediaId);
      $media->set($this->categoryField, $categoryId);
      $media->save();
    }

  }

  /**
   * Ajax submit function for the button adding unused categories bellow the inline entity table.
   *
   * @return AjaxResponse
   *   Show selected unused category.
   */
  public function addCategory($form, FormState $form_state) {
    $response = new AjaxResponse();
    $categoryId = $form_state->getUserInput()[$this->tableField]['actions']['category_select'];
    if (!empty($categoryId)) {
      $fieldName = str_replace('_', '-', $this->tableField);
      $tableId = ".field--name-$fieldName table.ief-entity-table";
      $response->addCommand(
        new InvokeCommand($tableId . " tr[category-id='$categoryId'", 'show')
      );
    }
    return $response->addCommand(
      new InvokeCommand('#inline-entity-form-add-category', 'val', [''])
    );
  }

  /**
   * @overrides theme_inline_entity_form_entity_table
   * Called on page refresh where inline entity d&d table is used.
   *
   * @see ief_dnd_categories_form_alter.
   */
  public function renderTable($variables) {

    $renderer = $this->renderer;
    $form = $variables['form'];
    $entity_type = $form['#entity_type'];

    $fields = $form['#table_fields'];
    $has_tabledrag = \Drupal::entityTypeManager()->getHandler($entity_type, 'inline_form')->isTableDragEnabled($form);

    // Sort the fields by weight.
    uasort($fields, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    $header = [];
    if ($has_tabledrag) {
      $header[] = ['data' => '', 'class' => ['ief-tabledrag-header']];
      $header[] = ['data' => t('Sort order'), 'class' => ['ief-sort-order-header']];
    }
    // Add header columns for each field.
    $first = TRUE;
    foreach ($fields as $field_name => $field) {
      $column = ['data' => $field['label'], 'class' => ['inline-entity-form-' . $entity_type . '-' . $field_name]];
      // The first column gets a special class.
      if ($first) {
        $column['class'][] = 'ief-first-column-header';
        $first = FALSE;
      }
      $header[] = $column;
    }
    $header[] = t('Operations');

    // Build an array of entity rows for the table.
    $rows = [];
    foreach (Element::children($form) as $key) {
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
      $entity = $form[$key]['#entity'];
      $row_classes = ['ief-row-entity'];
      $cells = [];
      if ($has_tabledrag) {
        $cells[] = ['data' => '', 'class' => ['ief-tabledrag-handle']];
        $cells[] = $renderer->render($form[$key]['delta']);
        $row_classes[] = 'draggable';
      }
      // Add a special class to rows that have a form underneath, to allow
      // for additional styling.
      if (!empty($form[$key]['form'])) {
        $row_classes[] = 'ief-row-entity-form';
      }

      foreach ($fields as $field_name => $field) {
        if ($field['type'] == 'label') {
          $data = $variables['form'][$key]['#label'];
        }
        elseif ($field['type'] == 'field' && $entity->hasField($field_name)) {
          $display_options = ['label' => 'hidden'];
          if (isset($field['display_options'])) {
            $display_options += $field['display_options'];
          }
          $data = $entity->get($field_name)->view($display_options);
        }
        elseif ($field['type'] == 'callback') {
          $arguments = [
            'entity' => $entity,
            'variables' => $variables,
          ];
          if (isset($field['callback_arguments'])) {
            $arguments = array_merge($arguments, $field['callback_arguments']);
          }

          $data = call_user_func_array($field['callback'], $arguments);
        }
        else {
          $data = t('N/A');
        }

        $cells[] = ['data' => $data, 'class' => ['inline-entity-form-' . $entity_type . '-' . $field_name]];
      }

      // Add the buttons belonging to the "Operations" column.
      $cells[] = $renderer->render($form[$key]['actions']);

      $categoryId = $entity->get($this->categoryField)->getValue();
      if (isset($categoryId[0]) && isset($categoryId[0]['target_id'])) {
        $categoryId = $categoryId[0]['target_id'];
      }
      else {
        $categoryId = '';
      }

      // Create the row.
      $rows[] = [
        'weight' => $key,
        'data' => $cells,
        'category-id' => $categoryId,
        'class' => $row_classes
      ];

      // If the current entity array specifies a form, output it in the next row.
      if (!empty($form[$key]['form'])) {
        $row = [
          ['data' => $renderer->render($form[$key]['form']), 'colspan' => count($fields) + 1],
        ];
        $rows[] = ['data' => $row, 'class' => ['ief-row-form'], 'no_striping' => TRUE];
      }
    }

    $this->updateRowsWithCategories($rows);

    if (count($rows)) {
      usort($rows, [$this, 'orderEntitiesByCategories']);
    }

    if (!empty($rows)) {
      $tabledrag = [];
      if ($has_tabledrag) {
        $tabledrag = [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'ief-entity-delta',
          ],
        ];
      }

      $table = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => [
          'id' => 'ief-entity-table-' . $form['#id'],
          'class' => ['ief-entity-table'],
        ],
        '#tabledrag' => $tabledrag,
      ];
    }

    return $renderer->render($table);
  }

  /**
   * Compares $rowA and $rowB positions.
   * @see ::getRelativeWeight.
   */
  public function orderEntitiesByCategories($rowA, $rowB)
  {
    $rowA['is_category'] = NULL;
    $rowB['is_category'] = NULL;
    if (in_array('category-term', $rowA['class'])) {
      $rowA['is_category'] = TRUE;
      $rowA['weight'] = $rowA['data'][0]['position'];
    }
    if (in_array('category-term', $rowB['class'])) {
      $rowB['is_category'] = TRUE;
      $rowB['weight'] = $rowB['data'][0]['position'];
    }
    return IefDndCategoriesPositionHandler::getRelativeWeight($rowA, $rowB);
  }

  public function getFormConfig($configKey = NULL) {

    $config = ief_dnd_get_config();

    foreach ($config as $key => $configValue) {
      if ($configValue['entity_type'] . '::' . $configValue['entity_bundle'] . '::' . $configValue['entity_ief_field'] === $configKey) {
        return $config[$key];
      }
    }

    return NULL;
  }

}
