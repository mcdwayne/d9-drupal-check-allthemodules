<?php

namespace Drupal\entityform_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides a block for creating a new content entity.
 *
 * @Block(
 *   id = "entityform_block",
 *   admin_label = @Translation("Entity form"),
 *   category = @Translation("Forms")
 * )
 */
class EntityEditFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'entity_type' => '',
      'bundle' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return \Drupal::entityManager()
      ->getAccessControlHandler($this->configuration['entity_type'])
      ->createAccess($this->configuration['bundle'], $account, [], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Get content entity types.
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface[] $content_entity_types */
    $content_entity_types = [];
    foreach (\Drupal::entityManager()->getDefinitions() as $entity_type_id => $entity_type_definition) {
      if ($entity_type_definition->getGroup() == 'content') {
        $content_entity_types[$entity_type_id] = $entity_type_definition;
      }
    }
    $options = array();
    foreach ($content_entity_types as $type_key => $entity_type) {
      // Entities that do not declare a form class.
      // Exclude Comment entities as they have to be attached to another entity.
      if (!$entity_type->hasFormClasses() || $type_key == 'comment') {
        continue;
      }
      // Get all bundles for current entity type.
      $entity_type_bundles = \Drupal::entityManager()->getBundleInfo($type_key);
      foreach ($entity_type_bundles as $bundle_key => $bundle_info) {
        // Personal contact form requires a user recipient to be specified.
        if ($bundle_key == 'personal' && $type_key == 'contact_message') {
          continue;
        }
        $options[(string) $entity_type->getLabel()][$type_key . '.' . $bundle_key] = $bundle_info['label'];
      }
    }

    $form['entity_type_bundle'] = array(
      '#title' => $this->t('Entity Type + Bundle'),
      '#type' => 'select',
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $this->configuration['entity_type'] . '.' . $this->configuration['bundle'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $selected_entity_type_bundle = $form_state->getValue('entity_type_bundle');
    $values = explode('.', $selected_entity_type_bundle);
    $this->configuration['entity_type'] = $values[0];
    $this->configuration['bundle'] = $values[1];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = array();
    // Specify selected bundle if the entity has bundles.
    if (\Drupal::entityManager()->getDefinition($this->configuration['entity_type'])->hasKey('bundle')) {
      $bundle_key = \Drupal::entityManager()->getDefinition($this->configuration['entity_type'])->getKey('bundle');
      $values = array($bundle_key => $this->configuration['bundle']);
    }

    $entity = \Drupal::entityManager()
      ->getStorage($this->configuration['entity_type'])
      ->create($values);

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId(\Drupal::currentUser()->id());
    }

    $form = \Drupal::entityManager()
      ->getFormObject($this->configuration['entity_type'], 'default')
      ->setEntity($entity);
    return \Drupal::formBuilder()->getForm($form);
  }
}
