<?php
 
/**
 
 * @file
 
 * Contains \Drupal\custom_nodeformactions\Form\SimpleConfigForm.
 
 */
 
namespace Drupal\custom_nodeformactions\Form;
 
use Drupal\Core\Form\ConfigFormBase;
 
use Drupal\Core\Form\FormStateInterface;
 
class CustomnodeActionsConfigForm extends ConfigFormBase {
 
  /**
 
   * {@inheritdoc}
 
   */
 
  public function getFormId() {
 
    return 'custom_nodeformactions_config_form';
 
  }
 
  /**
 
   * {@inheritdoc}
 
   */
 
  public function buildForm(array $form, FormStateInterface $form_state) {
 
    $form = parent::buildForm($form, $form_state);
 
    $config = $this->config('custom_nodeformactions.settings');
 
    $form['addmorebuttonlabel'] = array(
 
      '#type' => 'textfield',
 
      '#title' => $this->t('Add More Button Label'),
 
      '#default_value' => $config->get('custom_nodeformactions.addmorebuttonlabel'),
 
      '#required' => TRUE,
 
    );
	
	$form['addcopybuttonlabel'] = array(
 
      '#type' => 'textfield',
 
      '#title' => $this->t('Add Copy Button Label'),
 
      '#default_value' => $config->get('custom_nodeformactions.addcopybuttonlabel'),
 
      '#required' => TRUE,
 
    );
 
    $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
 
    $node_type_titles = array();
 
    foreach ($node_types as $machine_name => $val) {
 
      $node_type_titles[$machine_name] = $val->label();
 
    }
 
    $form['node_types'] = array(
 
      '#type' => 'checkboxes',
 
      '#title' => $this->t('Node Types'),
 
      '#options' => $node_type_titles,
 
      '#default_value' => $config->get('custom_nodeformactions.node_types'),
 
    );
 
    return $form;
 
  }
 
  /**
 
   * {@inheritdoc}
 
   */
 
  public function submitForm(array &$form, FormStateInterface $form_state) {
 
    $config = $this->config('custom_nodeformactions.settings');
 
    $config->set('custom_nodeformactions.addmorebuttonlabel', $form_state->getValue('addmorebuttonlabel'));
	
	$config->set('custom_nodeformactions.addcopybuttonlabel', $form_state->getValue('addcopybuttonlabel'));
 
    $config->set('custom_nodeformactions.node_types', $form_state->getValue('node_types'));
 
    $config->save();
 
    return parent::submitForm($form, $form_state);
 
  }
 
  /**
 
   * {@inheritdoc}
 
   */
 
  protected function getEditableConfigNames() {
 
    return [
 
      'custom_nodeformactions.settings',
 
    ];
 
  }
 
}