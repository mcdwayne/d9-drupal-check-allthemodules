<?php

namespace Drupal\save_edit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Class SettingsForm.
 *
 * @package Drupal\save_edit\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'save_edit.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'save_edit_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('save_edit.settings');
    $weights_range = range(-10, 10);
    $weights = array_combine($weights_range, $weights_range);

    $form['button_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to use for Save & Edit button'),
      '#description' => $this->t('This is the default text that will be used for the button at the bottom of the node form.<br>It would be best to use familiar terms like "<strong>Save &amp; Edit</strong>" or "<strong>Apply</strong>" so that users can easily understand the feature/function related to this option.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('button_value'),
    ];
    $form['button_weight'] = [
      '#type' => 'select',
      '#title' => $this->t('Save & Edit Button Weight'),
      '#description' => $this->t('You can adjust the horizontal positioning in the button section (or vertical positioning when using the dropbutton setting).'),
      '#options' => $weights,
      '#default_value' => $config->get('button_weight'),
    ];
    $form['dropbutton'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Integrate into dropbutton'),
      '#description' => $this->t('This setting will insert the Save &amp; Edit button into the save dropbutton.'),
      '#default_value' => $config->get('dropbutton'),
    ];
    $form['unpublish'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Unpublish All Nodes'),
      '#description' => $this->t('This setting will automatically uncheck the "Published" status when using <strong>Save &amp; Edit</strong> button to save nodes.'),
      '#default_value' => $config->get('unpublish'),
    ];
    $form['unpublish_new_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Unpublish on New Nodes Only'),
      '#description' => $this->t('This will only mark the node as unpublished upon creating a new node. Assuming this is used, on subsequent uses of <strong>Save &amp; Edit</strong> the node will be unpublished already, and NOT affected. You will be required at some point to manually publish the node using the optional <strong>Publish</strong> button, or manually ticking the appropriate checkbox when hitting the default Save button.'),
      '#default_value' => $config->get('unpublish_new_only'),
    ];
    $form['hide_default_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide default Save button'),
      '#description' => $this->t('This will hide the Save dropbutton.'),
      '#default_value' => $config->get('hide_default_save'),
    ];

    $form['hide_default_publish'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the Publish button'),
      '#default_value' => $config->get('hide_default_publish'),
      '#description' => $this->t('This will hide the Publish button.'),
    );
    $form['hide_default_preview'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide default Preview button'),
      '#default_value' => $config->get('hide_default_preview'),
      '#description' => $this->t('This will hide the Preview button.'),
    );
    $form['hide_default_delete'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide default Delete button'),
      '#default_value' => $config->get('hide_default_delete'),
      '#description' => $this->t('This will hide the Delete button.'),
    );

    $node_types = NodeType::loadMultiple();
    $keyed_node_types = [];
    foreach ($node_types as $content_type) {
      $keyed_node_types[$content_type->id()] = $content_type->label();
    }
    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#options' => $keyed_node_types,
      '#title' => $this->t('Node types'),
      '#description' => $this->t('Set the node types you want to display links for.'),
      '#default_value' => $config->get('node_types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('save_edit.settings')
      ->set('button_value', $form_state->getValue('button_value'))
      ->set('button_weight', $form_state->getValue('button_weight'))
      ->set('dropbutton', $form_state->getValue('dropbutton'))
      ->set('hide_default_save', $form_state->getValue('hide_default_save'))
      ->set('hide_default_publish', $form_state->getValue('hide_default_publish'))
      ->set('hide_default_preview', $form_state->getValue('hide_default_preview'))
      ->set('hide_default_delete', $form_state->getValue('hide_default_delete'))
      ->set('unpublish', $form_state->getValue('unpublish'))
      ->set('unpublish_new_only', $form_state->getValue('unpublish_new_only'))
      ->set('node_types', $form_state->getValue('node_types'))
      ->save();
  }

}
