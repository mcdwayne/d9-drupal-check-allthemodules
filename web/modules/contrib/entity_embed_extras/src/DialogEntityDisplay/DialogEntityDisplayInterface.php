<?php

namespace Drupal\entity_embed_extras\DialogEntityDisplay;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for Dialog Entity Display plugins.
 *
 * Allows for the creation of plugins to handle how an entity is displayed in
 * EntityEmbedDialog during the embed step.
 *
 * @see \Drupal\entity_embed_extras\DialogEntityDisplay\DialogEntityDisplayBase
 * @see \Drupal\entity_embed_extras\DialogEntityDisplay\DialogEntityDisplayBase
 * @see \Drupal\entity_embed_extras\DialogEntityDisplay\DialogEntityDisplayManager
 * @see \Drupal\entity_embed_extras\Annotation\DialogEntityDisplay
 * @see plugin_api
 *
 * @ingroup entity_embed_api
 */
interface DialogEntityDisplayInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Whether this plugin should display a configuration form.
   *
   * @return bool
   *   A boolean value.
   */
  public function isConfigurable();

  /**
   * Get Form Element to display the entity in the dialog.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be embedded.
   * @param array $original_form
   *   The original EntityEmbedDialog form.
   * @param \Drupal\entity_embed\DialogEntityDisplay\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The render array element.
   */
  public function getFormElement(EntityInterface $entity, array &$original_form, FormStateInterface $form_state);

}
