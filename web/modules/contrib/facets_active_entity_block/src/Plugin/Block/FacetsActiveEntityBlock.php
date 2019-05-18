<?php

namespace Drupal\facets_active_entity_block\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Facets Active Entity" block.
 *
 * @Block(
 *   id = "facets_active_entity",
 *   admin_label = @Translation("Facets active entity block"),
 * )
 */
class FacetsActiveEntityBlock extends FacetsActiveEntityBlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['view_mode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View mode'),
      '#description' => $this->t('Enter the machine name of the view mode to render, or leave blank to use the default view mode.'),
      '#default_value' => isset($config['view_mode']) ? $config['view_mode'] : '',
    ];

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->setConfigurationValue('view_mode', $form_state->getValue('view_mode'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#cache' => ['max-age' => 0],
    ] + $this->getEntityView();
  }

  /**
   * Build a view using a view builder for the configured entity and view mode
   *
   * @return array
   */
  protected function getEntityView() {
    $activeFacets = $this->getFacets(TRUE, TRUE);

    $config = $this->getConfiguration();

    $enabledFacets = isset($config['enabled_facets']) ? $config['enabled_facets'] : [];

    $entityType = NULL;
    $entityId = NULL;

    foreach ($enabledFacets as $id) {
      if (array_key_exists($id, $activeFacets)) {
        $activeItems = $activeFacets[$id]->getActiveItems();

        if (!empty($activeItems)) {
          $entityType = $this->getEntityType($activeFacets[$id]);
          $entityId = array_pop($activeItems);

          break;
        }
      }
    }

    $result = NULL;

    if (!is_null($entityId)) {
      $entity = \Drupal::entityTypeManager()->getStorage($entityType)->load($entityId);
      $viewMode = isset($config['view_mode']) ? $config['view_mode'] : 'full';

      if (!is_null($entity)) {
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());

        $result = $view_builder->view($entity, $viewMode);
      }
    }

    if (is_null($result)) {
      $result = [
        '#type' => 'markup',
        '#markup' => '',
      ];
    }

    return $result;
  }
}
