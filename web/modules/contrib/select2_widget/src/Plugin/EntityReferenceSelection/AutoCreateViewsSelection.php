<?php

namespace Drupal\select2_widget\Plugin\EntityReferenceSelection;

use function array_combine;
use function array_keys;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection;

/**
 * Plugin implementation of the 'selection' entity_reference.
 *
 * @EntityReferenceSelection(
 *   id = "views_autocreate",
 *   label = @Translation("Views: Filter by an entity reference view (with autocreate)"),
 *   group = "views_autocreate",
 *   weight = -5
 * )
 */
class AutoCreateViewsSelection extends ViewsSelection implements SelectionWithAutocreateInterface {


  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $selection_handler_settings = $this->configuration['handler_settings'];
    $entity_type_id = $this->configuration['target_type'];
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $bundles = $this->entityManager->getBundleInfo($entity_type_id);

    // Merge-in default values.
    $selection_handler_settings += [
      'auto_create' => FALSE,
      'auto_create_bundle' => NULL,
    ];

    $form['auto_create'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Create referenced entities if they don't already exist"),
      '#default_value' => $selection_handler_settings['auto_create'],
    ];

    if ($entity_type->hasKey('bundle')) {
      $bundle_options = [];
      foreach ($bundles as $bundle_name => $bundle_info) {
        $bundle_options[$bundle_name] = $bundle_info['label'];
      }
      natsort($bundle_options);

      $form['auto_create_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Store new items in'),
        '#options' => $bundle_options,
        '#default_value' => $selection_handler_settings['auto_create_bundle'],
        '#access' => count($bundles) > 1,
        '#states' => [
          'visible' => [
            ':input[name="settings[handler_settings][auto_create]"]' => ['checked' => TRUE],
          ],
        ],
      ];

//      $bundle_keys = array_keys($bundle_options);
//      $form['target_bundles'] = [
//        '#type' => 'value',
//        '#value' => array_combine($bundle_keys, $bundle_keys),
//      ];

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $handler_settings = $this->configuration['handler_settings'];
    $display_name = $handler_settings['view']['display_name'];
    $arguments = $handler_settings['view']['arguments'];
    $result = [];
    if ($this->initializeView($match, $match_operator, $limit)) {
      // Get the results.
      $result = $this->view->executeDisplay($display_name, $arguments);
    }

    $return = [];
    if ($result) {
      foreach ($result as $row) {
        $entity = $row['#row']->_entity;
        $return[$entity->bundle()][$entity->id()] = render($row);
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $bundle_key = $entity_type->getKey('bundle');
    $label_key = $entity_type->getKey('label');

    $entity = $this->entityManager->getStorage($entity_type_id)->create([
      $bundle_key => $bundle,
      $label_key => $label,
    ]);

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId($uid);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    return TRUE;
  }

}
