<?php
 
/**
 * @file
 * Definition of Drupal\uc_gc_client\Plugin\views\field\PaymentType
 */
 
namespace Drupal\uc_gc_client\Plugin\views\field;
 
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
 
/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("gc_payment_type")
 */
class PaymentType extends FieldPluginBase {
 
  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }
 
  /**
   * Define the available options
   * @return array
   */
  //protected function defineOptions() {}
 
  /**
   * Provide the options form.
   */
  //public function buildOptionsForm(&$form, FormStateInterface $form_state) {}
 
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {

    $ucid = $values->_entity->id();
    $type = db_select('uc_gc_client','c')
      ->fields('c',['type'])
      ->condition('ucid', $ucid)
      ->execute()->fetchField();
    if (isset($type)) {
      if ($type == 'S') return t('Subscription'); 
      elseif ($type == 'P') return t('One-off payments');
    }
  }
}
