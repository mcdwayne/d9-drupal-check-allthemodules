<?php

/**
 * @file
 * Contains \Drupal\collect\Controller\ModelController.
 */

namespace Drupal\collect\Controller;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Entity\Model;
use Drupal\collect\Model\DynamicModelTypedDataInterface;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Route controller for model management.
 *
 * @see \Drupal\collect\Model\ModelListBuilder
 * @see \Drupal\collect\Form\ModelForm
 */
class ModelController extends ControllerBase {

  /**
   * The injected model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * Constructs a new ModelController.
   *
   * @param \Drupal\collect\Model\ModelManagerInterface $model_manager
   *   Injected model manager.
   */
  public function __construct(ModelManagerInterface $model_manager) {
    $this->modelManager = $model_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.collect.model')
    );
  }

  /**
   * Displays a pre-filled model add form.
   *
   * @param \Drupal\collect\CollectContainerInterface $collect_container
   *   The container to suggest a new model from.
   *
   * @return array
   *   The model edit form filled with the new values.
   *
   * @see \Drupal\collect\Form\ModelForm
   */
  public function addSuggested(CollectContainerInterface $collect_container) {
    // Get suggested model.
    $model = $this->modelManager->suggestModel($collect_container);

    // Display form.
    return $this->entityFormBuilder()->getForm($model, 'add', ['collect_container' => $collect_container]);
  }

  /**
   * Enables a model.
   *
   * @param \Drupal\collect\Entity\Model $collect_model
   *   Model to enable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirection to model list.
   */
  public function enable(Model $collect_model) {
    $collect_model->enable()->save();
    drupal_set_message($this->t('Model %label has been enabled.', ['%label' => $collect_model->label()]));
    $url = $collect_model->urlInfo('collection');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
  }

  /**
   * Disables a model.
   *
   * @param \Drupal\collect\Entity\Model $collect_model
   *   Model to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirection to model list.
   */
  public function disable(Model $collect_model) {
    $collect_model->disable()->save();
    drupal_set_message($this->t('Model %label has been disabled.', ['%label' => $collect_model->label()]));
    $url = $collect_model->urlInfo('collection');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
  }

  /**
   * Removes any given property from a model.
   *
   * @param \Drupal\collect\Entity\Model $collect_model
   *   The model to remove the property from.
   * @param string $property_name
   *   The name of the property to remove.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A response redirecting to the model form.
   */
  public function removeProperty(Model $collect_model, $property_name) {
    $collect_model->unsetProperty($property_name)->save();
    drupal_set_message($this->t('Property %property removed from %model', ['%property' => $property_name, '%model' => $collect_model->label()]));
    return new RedirectResponse($collect_model->urlInfo()->setAbsolute()->toString());
  }

  /**
   * Displays "Set up a model" button in case there is no model plugin.
   */
  public function checkAddSuggestedModelAccess(CollectContainerInterface $collect_container) {
    $suggested_model = $this->modelManager->suggestModel($collect_container);
    $active_model = $this->modelManager->loadModelByUri($collect_container->getSchemaUri());
    return AccessResult::allowedIf(empty($active_model) && $suggested_model);
  }
}
