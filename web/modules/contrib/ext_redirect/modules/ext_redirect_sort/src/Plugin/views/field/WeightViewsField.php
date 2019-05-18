<?php

namespace Drupal\ext_redirect_sort\Plugin\views\field;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Driver\Exception\Exception;
use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("weight_views_field")
 */
class WeightViewsField extends BulkForm {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return Markup::create('<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->');
  }


  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    $form['weight_views_field'] = [
      '#tree' => TRUE,
    ];

    $form['actions']['submit']['#value'] = $this->t('Save weight order');

    foreach ($this->view->result as $row_index => $row) {
      $form['weight_views_field'][$row_index] = array(
        '#tree' => TRUE,
      );

      // Item to keep id of the entity.
      $form['weight_views_field'][$row_index]['id'] = array(
        '#type' => 'hidden',
        '#value' => $row->_entity->id(),
        '#attributes' => array('class' => 'draggableviews-id'),
      );
    }

    $options = [
      'table_id' => ext_redirect_sort_table_id($this->view),
      'action' => 'order',
      'relationship' => 'sibling',
      'group' => 'views-field-weight-views-field',
    ];

    drupal_attach_tabledrag($form, $options);
  }

  /**
   * @inheritdoc
   */
  public function viewsFormSubmit(&$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();

    $connection = Database::getConnection();
    $transaction = $connection->startTransaction();

    try {
      $counter = 0;
      foreach ($input['weight_views_field'] as $item) {
        $fields = [
          'weight' => ++$counter,
          'changed' => REQUEST_TIME,
        ];
        $connection->update('redirect_rule')->fields($fields)
          ->where('rid = :rid', [':rid' => $item['id']])->execute();
      }
    }
    catch (Exception $ex) {
      $transaction->rollback();
      \Drupal::logger('ext_redirect_sort')
        ->error('Failed with @message', ['@message' => $ex->getMessage()]);
      return drupal_set_message(t('There was an error while saving the data. Please, try gain.'), 'warning');
    }

    drupal_set_message(t('Sort order has been saved'));
  }
}
