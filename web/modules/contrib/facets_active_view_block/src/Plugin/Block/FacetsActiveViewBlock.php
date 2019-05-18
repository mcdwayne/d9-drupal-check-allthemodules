<?php

namespace Drupal\facets_active_view_block\Plugin\Block;

use Drupal\facets_active_entity_block\Plugin\Block\FacetsActiveEntityBlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Provides a 'FacetsActiveViewBlock' block.
 *
 * @Block(
 *  id = "facets_active_view_block",
 *  admin_label = @Translation("Facets active view block"),
 * )
 */
class FacetsActiveViewBlock extends FacetsActiveEntityBlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['view'] = [
      '#type' => 'select',
      '#title' => $this->t('View to display'),
      '#description' => $this->t('The view to display, passing the active facet as a parameter.'),
      '#options' => Views::getViewsAsOptions(),
      '#default_value' => isset($config['view']) ? $config['view'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->setConfigurationValue('view', $form_state->getValue('view'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
        '#cache' => ['max-age' => 0],
      ] + $this->getView();
  }

  /**
   * Render the view, passing the given active facet.
   *
   * @return array|null
   *   A renderable array of the executed view.
   */
  protected function getView() {
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

    // If we found the entity, try to render the view.
    if (!is_null($entityId)) {
      // Get the view name and display name from the configuration.
      list($view_name, $display_name) = explode(':', $config['view']);

      // Load the view.
      $view = Views::getView($view_name);

      // Got the view? Render it passing the entity ID from the facet.
      if (is_object($view)) {
        $result = $view->buildRenderable($display_name, [$entityId]);
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
