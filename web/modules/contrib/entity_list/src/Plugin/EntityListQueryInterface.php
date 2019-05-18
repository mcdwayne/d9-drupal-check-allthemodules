<?php

namespace Drupal\entity_list\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Entity list query plugins.
 */
interface EntityListQueryInterface extends PluginInspectionInterface, QueryInterface {

  /**
   * Generates the query settings form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The render array representing the query settings form.
   */
  public function settingsForm(FormStateInterface $form_state);

  /**
   * Build the query according to the settings.
   *
   * @return $this
   */
  public function buildQuery();

  /**
   * Check if the query need a pager.
   *
   * @return bool
   *   TRUE if the query need pager, FALSE otherwise.
   */
  public function usePager();

  /**
   * Get the selected bundles.
   *
   * @return array
   *   An array of bundle ids.
   */
  public function getBundles();

}
