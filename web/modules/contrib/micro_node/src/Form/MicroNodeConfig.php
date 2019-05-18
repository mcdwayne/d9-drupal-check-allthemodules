<?php

namespace Drupal\micro_node\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

class MicroNodeConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['micro_node.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micro_node_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = array_map(function (NodeType $nodeType) { return $nodeType->label(); }, NodeType::loadMultiple());
    $config = $this->config('micro_node.settings');
    $form['node_types'] = [
      '#title' => t('The node types we can associate with a site entity. This setting can be override per site type.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $config->get('node_types'),
    ];

    $form['node_types_tab'] = [
      '#title' => t('Select the node type for which you want display the add form as a local task (tab) on the site canonical page. Otherwise Local actions are provided on the site content tab. This setting can be override per site type.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $config->get('node_types_tab'),
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('micro_node.settings');
    $config->set('node_types', array_filter($form_state->getValue('node_types')));
    $config->set('node_types_tab', array_filter($form_state->getValue('node_types_tab')));
    $config->save();

    $node_types = array_filter($form_state->getValue('node_types'));
    foreach ($node_types as $bundle => $info) {
      micro_node_assign_fields('node', $bundle);
    }
    // We need to build custom dynamic routes and menus.
    drupal_flush_all_caches();
  }

}
