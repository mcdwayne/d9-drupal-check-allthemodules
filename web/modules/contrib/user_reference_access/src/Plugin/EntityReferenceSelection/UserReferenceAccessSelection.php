<?php

namespace Drupal\user_reference_access\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "user_reference_access:user",
 *   label = @Translation("User selection based on entity access"),
 *   base_plugin_label = @Translation("User selection based on entity access"),
 *   entity_types = {"user"},
 *   group = "user_reference_access",
 *   weight = 1
 * )
 */
class UserReferenceAccessSelection extends UserSelection {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'access' => [
        'new_entities_permission' => false,
        'existing_entities_operations' => [],
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    // TODO: Move permission generation to getPermissionsOptions.
    $permissionsOptions = [
      '' => t('None'),
    ];
    foreach (\Drupal::service('user.permissions')->getPermissions() as $id => $info) {
      $permissionsOptions[$info['provider']][$id] = $info['title'];
    }
    $form['access']['new_entities_permission'] = [
      '#type' => 'select',
      '#title' => t('Permission to use for new entities'),
      '#default_value' => $configuration['access']['new_entities_permission'],
      '#options' => $permissionsOptions,
      '#description' => t('Leave empty to allow all users. This setting only has effect when creating a new entity.'),
    ];

    // TODO: Move the option generation to getEntityOperations() that invokes and alter hook.
    $entityOperationsOptions = [
      'view' => t('View'),
      'update' => t('Update'),
      'delete' => t('Delete'),
    ];
    $form['access']['existing_entities_operations'] = [
      '#type' => 'checkboxes',
      '#title' => t('Limit selection to users that can perform the selected actions'),
      '#default_value' => $configuration['access']['existing_entities_operations'],
      '#options' => $entityOperationsOptions,
      '#description' => t('Leave empty to allow all users. This setting only has effect when editing an existing entity.'),
    ];

    $form += parent::buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $configuration = $this->getConfiguration();
    $target_type = $configuration['target_type'];
    $query = $this->buildEntityQuery($match, $match_operator);
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }

    // Split the results into chunks of $innerLimit size to try to load as little
    // user accounts as possible.
    $chunks = $limit == 0 ? [$result] : array_chunk($result, $limit);
    $options = [];
    /** @var \Drupal\Core\Entity\EntityInterface $parentEntity */
    $parentEntity = $configuration['entity'];
    $newEntityPermission = isset($configuration['access']['new_entities_permission']) ? $configuration['access']['new_entities_permission'] : FALSE;
    $existingEntityOperations = isset($configuration['access']['existing_entities_operations']) ? array_filter($configuration['access']['existing_entities_operations']) : [];
    while (($limit == 0 || count($options) < $limit) && count($chunks) > 0) {
      $chunk = array_shift($chunks);
      /** @var \Drupal\user\Entity\User[] $users */
      $users = $this->entityManager->getStorage($target_type)->loadMultiple($chunk);
      foreach ($users as $userId => $user) {
        $access = FALSE;

        if ($parentEntity->isNew()) {
          $access = $user->hasPermission($newEntityPermission);
        } else {
          if (empty($existingEntityOperations)) {
            // No operations were selected. Don't filter.
            $access = TRUE;
          } else {
            foreach ($existingEntityOperations as $operation) {
              if ($parentEntity->access($operation, $user)) {
                // If user has access to perform at least one of the listed
                // operations the account can be referenced.
                $access = TRUE;
                break;
              }
            }
          }
        }

        if ($access) {
          $bundle = $user->bundle();
          $options[$bundle][$userId] = Html::escape($this->entityManager->getTranslationFromContext($user)->label());
        }
      }
    }

    return $options;
  }

}
