<?php

namespace Drupal\entity_list\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_list\Entity\EntityList;
use Drupal\entity_list\Entity\EntityListInterface;

/**
 * Defines an interface for Entity list display plugins.
 */
interface EntityListDisplayInterface extends PluginInspectionInterface {

  /**
   * Generates the render array for the list.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $items
   *   The list of entities.
   * @param string $view_mode
   *   The current view mode.
   * @param string|null $langcode
   *   The current langcode.
   *
   * @return array
   *   The render array.
   */
  public function render(array $items, $view_mode = 'full', $langcode = NULL);

  /**
   * Generates the display settings form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The display settings form.
   */
  public function settingsForm(FormStateInterface $form_state);

}
