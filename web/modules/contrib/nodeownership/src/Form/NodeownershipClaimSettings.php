<?php

namespace Drupal\nodeownership\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Form.
 */
class NodeownershipClaimSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nodeownership_claim_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nodeownership.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Retrive conigs.
    // Node types.
    $types = array_keys(node_type_get_names());
    $options = array();
    foreach ($types as $node_type) {
      $options[$node_type] = $node_type;
    }

    $form['nodeownership_node_types'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types which can be claimed for ownership'),
      '#options' => $options,
      '#default_value' => \Drupal::configFactory()->getEditable('nodeownership.settings')->get('nodeownership_node_types'),
    );

    $form['nodeownership_link_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Claim Text'),
      '#description' => t('The text that will be shown for the claiming the node'),
      '#default_value' => \Drupal::configFactory()->getEditable('nodeownership.settings')->get('nodeownership_link_text'),
    );

    $form['nodeownership_pending_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Claim Pending Text'),
      '#description' => t('The text that will be shown for pending claims'),
      '#default_value' => \Drupal::configFactory()->getEditable('nodeownership.settings')->get('nodeownership_pending_text'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    \Drupal::configFactory()
        ->getEditable('nodeownership.settings')
        ->set('nodeownership_node_types', $form_state->getValue('nodeownership_node_types'))
        ->save();
    \Drupal::configFactory()
        ->getEditable('nodeownership.settings')
        ->set('nodeownership_link_text', $form_state->getValue('nodeownership_link_text'))
        ->save();
    \Drupal::configFactory()
        ->getEditable('nodeownership.settings')
        ->set('nodeownership_pending_text', $form_state->getValue('nodeownership_pending_text'))
        ->save();

    drupal_set_message(\Drupal::configFactory()->getEditable('nodeownership.settings')->get('nodeownership_link_text'));

    return parent::submitForm($form, $form_state);
  }

}
