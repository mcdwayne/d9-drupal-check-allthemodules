<?php

namespace Drupal\meta_position\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Config form settings for meta_position module.
 */
class MetaPositionConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['meta_position.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'meta_position_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = array_map(function (NodeType $nodeType) {
      return $nodeType->label();
    }, NodeType::loadMultiple());
    $config = $this->config('meta_position.settings');
    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display block meta as vertical tabs'),
      '#description'   => $this->t('Use the checkbox to display the node meta block as vertical tabs and under the main node form.'),
      '#default_value' => $config->get('enabled'),
    );
    $form['node_types'] = [
      '#title' => $this->t('Content type.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#description' => $this->t('Select the content type on which display the node meta block as vertical tabs and under the main node form. Leave <b>empty</b> to select all the content types.'),
      '#default_value' => $config->get('node_types'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('meta_position.settings');
    $config->set('node_types', array_filter($form_state->getValue('node_types')));
    $config->set('enabled', $form_state->getValue('enabled'));
    $config->save();
  }

}
