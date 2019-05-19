<?php

namespace Drupal\uc_stock\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\node\NodeInterface;

/**
 * Defines the stock edit form.
 */
class StockEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_stock_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form['#title'] = $this->t('<em>Edit @type stock</em> @title', ['@type' => node_get_type_label($node), '@title' => $node->label()]);

    $form['stock'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => ' ' . $this->t('Active'), 'class' => ['select-all', 'nowrap']],
        $this->t('SKU'),
        $this->t('Stock'),
        $this->t('Threshold'),
      ],
    ];
    $form['#attached']['library'][] = 'core/drupal.tableselect';

    $skus = uc_product_get_models($node->id(), FALSE);
    foreach ($skus as $sku) {
      $stock = db_query("SELECT * FROM {uc_product_stock} WHERE sku = :sku", [':sku' => $sku])->fetchAssoc();

      $form['stock'][$sku]['active'] = [
        '#type' => 'checkbox',
        '#default_value' => !empty($stock['active']) ? $stock['active'] : 0,
      ];
      $form['stock'][$sku]['sku'] = [
        '#markup' => $sku,
      ];
      $form['stock'][$sku]['stock'] = [
        '#type' => 'textfield',
        '#default_value' => !empty($stock['stock']) ? $stock['stock'] : 0,
        '#maxlength' => 9,
        '#size' => 9,
      ];
      $form['stock'][$sku]['threshold'] = [
        '#type' => 'textfield',
        '#default_value' => !empty($stock['threshold']) ? $stock['threshold'] : 0,
        '#maxlength' => 9,
        '#size' => 9,
      ];
    }

    $form['nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach (Element::children($form_state->getValue('stock')) as $sku) {
      $stock = $form_state->getValue(['stock', $sku]);

      db_merge('uc_product_stock')
        ->key(['sku' => $sku])
        ->updateFields([
          'active' => $stock['active'],
          'stock' => $stock['stock'],
          'threshold' => $stock['threshold'],
        ])
        ->insertFields([
          'sku' => $sku,
          'active' => $stock['active'],
          'stock' => $stock['stock'],
          'threshold' => $stock['threshold'],
          'nid' => $form_state->getValue('nid'),
        ])
        ->execute();
    }

    $this->messenger()->addMessage($this->t('Stock settings saved.'));
  }

}
