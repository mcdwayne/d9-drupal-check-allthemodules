<?php

namespace Drupal\vertical_tabs_config\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure order for this site.
 */
class OrderConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vt_order_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vertical_tabs_config.order',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vertical_tabs_config.order');

    $vertical_tabs = vertical_tabs_config_vertical_tab_list(TRUE);

    $form['desc'] = [
      '#type' => 'item',
      '#markup' => $this->t('Reorder vertical tabs in the table to set a global order.'),
    ];

    // https://www.drupal.org/node/1876710

    $form['vttable'] = [
      '#type' => 'table',
      '#header' => [t('Vertical tab'), t('Weight')],
      '#empty' => t('There are no vertical tabs.'),
      // Insert or not selection checkbox on first column.
      '#tableselect' => FALSE,
      // TableDrag: callback arguments for drupal_add_tabledrag().
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'vttable-order-weight',
        ],
      ],
    ];

    foreach ($vertical_tabs as $vt_machine_name => $vt_human_name) {
      $id = 'vertical_tabs_config_' . $vt_machine_name;

      // TableDrag: Mark the table row as draggable.
      $form['vttable'][$id]['#attributes']['class'][] = 'draggable';

      // TableDrag: Sort the table row according to its existing/configured weight.
      $form['vttable'][$id]['#weight'] = $config->get('vertical_tabs_config_' . $vt_machine_name);

      // Some table columns containing raw markup.
      $form['vttable'][$id]['label'] = [
        '#plain_text' => $vt_human_name,
      ];

      // TableDrag: Weight column element.
      $form['vttable'][$id]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $vt_human_name]),
        '#title_display' => 'invisible',
        '#default_value' => $config->get('vertical_tabs_config_' . $vt_machine_name),
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => ['vttable-order-weight']],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $vertical_tabs = vertical_tabs_config_vertical_tab_list();

    foreach ($vertical_tabs as $vt_machine_name => $vt_human_name) {
      $new_value = $values['vttable']['vertical_tabs_config_' . $vt_machine_name]['weight'];
      $this->config('vertical_tabs_config.order')
        ->set('vertical_tabs_config_' . $vt_machine_name, $new_value)
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
