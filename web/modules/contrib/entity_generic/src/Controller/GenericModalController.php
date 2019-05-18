<?php

namespace Drupal\entity_generic\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_generic\Entity\GenericTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller routines for entity routes.
 */
class GenericModalController extends GenericController {

  /**
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, RequestStack $request_stack, EntityFormBuilderInterface $entity_form_builder) {
    parent::__construct($date_formatter, $renderer, $request_stack);
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('request_stack'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Callback for composing the new entity.
   *
   * @param $entity_class
   * @param array $values
   * @param array $args
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function composeEntity($entity_class, array $values, array $args = []) {
    return $entity_class::create($values);
  }

  /**
   * Callback for adding the entity using modal form.
   *
   * @param array $args
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function addGenericEntityModal(array $args = []) {
    if (!isset($args['entity_type'])) {
      $route_parameters = \Drupal::routeMatch()->getParameters()->all();
      foreach ($route_parameters as $route_parameter_name => $route_parameter) {
        if ($route_parameter instanceof GenericTypeInterface) {
          $args['entity_bundle_info'] = $this->entityTypeManager->getDefinition($route_parameter->bundle());
          $args['entity_type'] = $args['entity_bundle_info']->getBundleOf();
          break;
        }
      }
    }

    $request_parameters = \Drupal::request()->query->all();
    $entity_type_info = $this->entityTypeManager()->getDefinition($args['entity_type']);
    $entity_class = $entity_type_info->getClass();

    // New entity.
    $entity_values = isset($args['values']) ? $args['values'] : [];
    if (isset($route_parameter)) {
      $entity_values['type'] = $route_parameter->id();
    }
    $entity = $this->composeEntity($entity_class, $entity_values, $request_parameters);

    // Get the modal form using the form builder.
    $form_mode = isset($args['form_mode']) ? $args['form_mode'] : 'modal';
    $form_operation = isset($args['form_operation']) ? $args['form_operation'] : 'default';
    $form_state_additions = [
      'modal_form_display' => EntityFormDisplay::collectRenderDisplay($entity, $form_mode),
    ];
    $form_state_additions += $request_parameters;
    $form = $this->entityFormBuilder->getForm($entity, $form_operation, $form_state_additions);

    // AJAX response.
    $response = new AjaxResponse();

    // Add an AJAX command to open a modal dialog with the form as the content.
    $form_title = isset($args['form_title']) ? $args['form_title'] : 'Add ' . $entity->getEntityType()->getLabel()->__toString();
    $form_args = [];
    $form_args['width'] = isset($args['form_width']) ? $args['form_width'] : '800';
    if (isset($args['form_height'])) {
      $form_args['height'] = $args['form_height'];
    }
    $response->addCommand(new OpenModalDialogCommand($this->t($form_title), $form, $form_args));

    return $response;
  }

  /**
   * Callback for editing the entity using modal form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function editGenericEntityModal(EntityInterface $entity) {
    $request_parameters = \Drupal::request()->query->all();

    // Get the modal form using the form builder.
    $form_mode = isset($request_parameters['form_mode']) ? $request_parameters['form_mode'] : 'modal';
    $form_state_additions = [
      'modal_form_display' => EntityFormDisplay::collectRenderDisplay($entity, $form_mode),
    ];
    $form_state_additions += $request_parameters;
    $form = $this->entityFormBuilder->getForm($entity, $form_mode, $form_state_additions);

    // AJAX response.
    $response = new AjaxResponse();

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand($this->t('Edit ' . $entity->getEntityType()->getLabel()->__toString()), $form, ['width' => '800']));

    return $response;
  }

  /**
   * Callback for editing the entity using modal form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function toggleStatusModal(EntityInterface $entity) {
    $request_parameters = \Drupal::request()->query->all();

    // Get the modal form using the form builder.
    $form_mode = isset($request_parameters['form_mode']) ? $request_parameters['form_mode'] : 'status_modal';
    $form_state_additions = [
      'modal_form_display' => EntityFormDisplay::collectRenderDisplay($entity, $form_mode),
    ];
    $form_state_additions += $request_parameters;
    $form = $this->entityFormBuilder->getForm($entity, $form_mode, $form_state_additions);

    // AJAX response.
    $response = new AjaxResponse();

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand($this->t('Change status'), $form, ['width' => '800']));

    return $response;
  }

  /**
   * Callback for deleting the entity using modal form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function deleteGenericEntityModal(EntityInterface $entity) {
    $request_parameters = \Drupal::request()->query->all();

    // Get the modal form using the form builder.
    $form_mode = isset($request_parameters['form_mode']) ? $request_parameters['form_mode'] : 'delete_modal';
    $form_state_additions = [
      'modal_form_display' => EntityFormDisplay::collectRenderDisplay($entity, $form_mode),
    ];
    $form_state_additions += $request_parameters;
    $form = $this->entityFormBuilder->getForm($entity, $form_mode, $form_state_additions);

    // AJAX response.
    $response = new AjaxResponse();

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand($this->t('Delete ' . $entity->getEntityType()->getLabel()->__toString()), $form, ['width' => '800']));

    return $response;
  }

}
