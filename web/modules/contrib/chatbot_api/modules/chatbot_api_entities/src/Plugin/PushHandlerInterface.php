<?php

namespace Drupal\chatbot_api_entities\Plugin;

use Drupal\chatbot_api_entities\Entity\EntityCollection;
use Drupal\chatbot_api_entities\Entity\EntityCollectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Push handler plugins.
 */
interface PushHandlerInterface extends PluginInspectionInterface {

  /**
   * Push Drupal entities to a remote end-point.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   Array of entities to push.
   * @param \Drupal\chatbot_api_entities\Entity\EntityCollection $entityCollection
   *   Configuration object for the collection.
   *
   * @return $this
   */
  public function pushEntities(array $entities, EntityCollection $entityCollection);

  /**
   * Gives the plugin a chance to modify its configuration.
   *
   * @param \Drupal\chatbot_api_entities\Entity\EntityCollectionInterface $entityCollection
   *   Collection being saved.
   * @param array $configuration
   *   Existing configuration.
   *
   * @return array
   *   Updated configuration.
   */
  public function saveConfiguration(EntityCollectionInterface $entityCollection, array $configuration);

  /**
   * Check if plugin is enabled.
   *
   * @return bool
   *   TRUE if enabled.
   */
  public function isEnabled();

  /**
   * Get the settings form.
   *
   * @param \Drupal\chatbot_api_entities\Entity\EntityCollectionInterface $entityCollection
   *   Collection being edited.
   * @param array $form
   *   The entire form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Configuration form for this item.
   */
  public function getSettingsForm(EntityCollectionInterface $entityCollection, array $form, FormStateInterface $form_state);

  /**
   * Validate the settings form.
   *
   * @param \Drupal\chatbot_api_entities\Entity\EntityCollectionInterface $entityCollection
   *   Collection being edited.
   * @param array $form
   *   The entire form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function validateSettingsForm(EntityCollectionInterface $entityCollection, array $form, FormStateInterface $form_state);

}
