<?php

namespace Drupal\uc_tax\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Displays a list of tax methods and rates.
 */
class TaxRateMethodsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_tax_methods';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_tax.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $tax_config = $this->config('uc_tax.settings');

    $header = [
      $this->t('Name'),
      $this->t('Rate'),
      $this->t('Taxed products'),
      $this->t('Taxed product types'),
      $this->t('Taxed line items'),
      $this->t('Weight'),
      $this->t('Operations'),
    ];
    $form['methods'] = [
      '#type' => 'table',
      '#header' => $header,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'uc-tax-method-weight',
        ],
      ],
      '#empty' => $this->t('No tax rates have been configured yet.'),
    ];

    $rows = [];
    foreach (uc_tax_rate_load() as $rate_id => $rate) {

      // Build a list of operations links.
      $operations = [
        'edit' => [
          'title' => $this->t('edit'),
          'url' => Url::fromRoute('uc_tax.rate_edit', ['tax_rate' => $rate_id]),
        ],
// @todo Fix when Rules works.
//      'conditions' => [
//        'title' => $this->t('conditions'),
//        'url' => Url::fromRoute('admin/store/config/taxes/manage/uc_tax_', ['rate_id' => $rate_id]),
//        'weight' => 5,
//      ],
        'clone' => [
          'title' => $this->t('clone'),
          'url' => Url::fromRoute('uc_tax.rate_clone', ['tax_rate' => $rate_id]),
        ],
        'delete' => [
          'title' => $this->t('delete'),
          'url' => Url::fromRoute('uc_tax.rate_delete', ['tax_rate' => $rate_id]),
        ],
      ];

      // Ensure "delete" comes towards the end of the list.
      if (isset($operations['delete'])) {
        $operations['delete']['weight'] = 10;
      }
      uasort($operations, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

      $form['methods'][$rate_id]['status'] = [
        '#type' => 'checkbox',
        '#title' => $rate->name,
        '#default_value' => $rate->enabled,
      ];
      $form['methods'][$rate_id]['rate'] = [
        '#markup' => $rate->rate * 100 . '%',
      ];
      $form['methods'][$rate_id]['taxed_products'] = [
        '#markup' => $rate->shippable ? $this->t('Shippable products') : $this->t('Any product'),
      ];
      $form['methods'][$rate_id]['taxed_types'] = [
        '#markup' => implode(', ', $rate->taxed_product_types),
      ];
      $form['methods'][$rate_id]['taxed_line_items'] = [
        '#markup' => implode(', ', $rate->taxed_line_items),
      ];
      $form['methods'][$rate_id]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $rate->weight,
        '#attributes' => ['class' => ['uc-tax-method-weight']],
      ];
      $form['methods'][$rate_id]['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled = [];
    $method_weight = [];
    foreach ($form_state->getValue('methods') as $rate_id => $rate) {
      $enabled[$rate_id] = $rate['status'];
      $method_weight[$rate_id] = $rate['weight'];
    }

    $tax_config = $this->config('uc_tax.settings');
    $tax_config
      ->set('enabled', $enabled)
      ->set('method_weight', $method_weight)
      ->set('type_weight', $form_state->getValue('uc_tax_type_weight'))
      ->save();

    return parent::submitForm($form, $form_state);
  }

}
