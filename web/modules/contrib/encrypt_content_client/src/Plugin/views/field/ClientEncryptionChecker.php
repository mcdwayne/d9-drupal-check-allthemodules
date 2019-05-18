<?php
 
/**
 * @file
 * Definition of Drupal\encrypt_content_client\Plugin\views\field\ClientEncryptionChecker
 */
 
namespace Drupal\encrypt_content_client\Plugin\views\field;
 
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
 
/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("client_encryption_checker")
 */
class ClientEncryptionChecker extends FieldPluginBase {
 
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
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }
 
  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }
 
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $values->_entity;
    $node_id = $node->id();
    $node_type = $node->getType();
    $user_id = \Drupal::currentUser()->id();
    $total_users_count = count(\Drupal::entityQuery('user')) - 1;
    
    $query = \Drupal::database()->select('encrypt_content_client_encryption_containers', 'encryption_containers');
    $query->fields('encryption_containers', ['encrypted_data_keys'])
      ->condition("encryption_containers.entity_id", (int) $node_id, '=')
      ->condition("encryption_containers.entity_type", $node_type, '=');
    $encrypted_data_keys = $query->execute()->fetchAssoc()['encrypted_data_keys'];
    $encrypted_for = count(json_decode($encrypted_data_keys));
    
    if ($encrypted_for == 0 || !$encrypted_data_keys) {
      return "Content is not encrypted.";
    }
    else if ($encrypted_for == $total_users_count) {
      return "Content is encrypted for every user.";
    }
    else if (!$encrypted_data_keys[$user_id]) {
      return "Content is inaccessible.";
    }
    else {
      return "Data-keys needs to be updated.";
    }
    
  }
}