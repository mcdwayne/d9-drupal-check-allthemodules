<?php

namespace Drupal\reference_map\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Reference Map Type plugins.
 */
interface ReferenceMapTypeInterface extends PluginInspectionInterface {

  /**
   * Gets all the entity ids referenced by the given entity through the map.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to start with.
   * @param bool $exception_on_invalid
   *   (optional) If TRUE, an invalid map will throw an exception.
   * @param int $start
   *   (optional) The step in the map to start with.
   * @param int $end
   *   (optional) The step in the map to end with.
   *
   * @return array
   *   An indexed array of the entity ids referenced by the given entity through
   *   the map or an empty array if no entities were found or if the map is
   *   invalid and $exception_on_invalid is FALSE.
   */
  public function follow(ContentEntityInterface $entity, $exception_on_invalid = TRUE, $start = 0, $end = NULL);

  /**
   * Gets all entity ids that reference the given entity through the map.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to use as the destination.
   * @param bool $exception_on_invalid
   *   (optional) If TRUE, an invalid map will throw an exception.
   * @param int $end
   *   The step in the map that the given entity matches.
   *
   * @return array
   *   An indexed array of the entity ids that reference the given entity
   *   through the map or an empty array if no entities were found or if the map
   *   is invalid and $exception_on_invalid is FALSE.
   */
  public function followReverse(ContentEntityInterface $entity, $exception_on_invalid = TRUE, $end = NULL);

  /**
   * Validates the Reference Map Config entity.
   *
   * @param bool $exception_on_invalid
   *   (optional) If TRUE, an invalid map will throw an exception.
   *
   * @return bool
   *   TRUE if the map is valid or FALSE if the map is invalid and
   *   $exception_on_invalid is FALSE.
   */
  public function validate($exception_on_invalid = TRUE);

  /**
   * Returns the map array from the Reference Map Config entity.
   *
   * @param int $start
   *   (optional) The step in the map to start with.
   * @param int $end
   *   (optional) The step in the map to end with.
   *
   * @return array
   *   The Reference Map Config entity's map array, trimmed to $start and $end
   *   if specified, or an empty array if the map couldn't be loaded.
   */
  public function getMap($start = 0, $end = NULL);

  /**
   * Returns the Reference Map Config entity.
   *
   * @return \Drupal\reference_map\Entity\ReferenceMapConfigInterface
   *   The Reference Map Config entity.
   */
  public function getConfig();

  /**
   * Alters the Reference Map Config entity's form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function configFormAlter(array &$form, FormStateInterface $form_state);

  /**
   * Validates alterations to the Reference Map Config entity's form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function configFormValidate(array &$form, FormStateInterface $form_state);

  /**
   * Presaves additional information to the Reference Map Config entity.
   *
   * Additional settings should be put in the Reference Map Config entity's
   * settings array.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function configFormPreSave(array &$form, FormStateInterface $form_state);

  /**
   * Alters the actions on the Reference Map Config entity's form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $actions
   *   The default actions.
   */
  public function configFormActions(array &$form, FormStateInterface $form_state, array &$actions);

}
