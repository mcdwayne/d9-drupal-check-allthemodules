<?php

namespace Drupal\entity_reference_ajax_formatter\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Returns responses for our ajaxified entity reference route.
 */
class EntityReferenceAjaxController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new EntityReferenceAjaxController.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Render the desired field into an AjaxResponse.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get the field from.
   * @param string $field_name
   *   The field to display.
   * @param string $view_mode
   *   The view mode to display the field with.
   * @param string $language
   *   The language to get the field in.
   * @param integer $start
   *   Currently only used in the EntityReferenceAjaxFormatter.
   * @param string $printed
   *   Currently only used in the EntityReferenceAjaxFormatter.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the field doesn't exist or not a Content Entity.
   */
  public function viewField(EntityInterface $entity, $field_name, $view_mode, $language, $start, $printed) {

    // Ensure that this is a valid Content Entity.
    if (!($entity instanceof ContentEntityInterface)) {
      throw new BadRequestHttpException('Requested Entity is not a Content Entity.');
    }

    // Check that this field exists.
    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    if (!$field = $entity->getTranslation($language)->get($field_name)) {
      throw new BadRequestHttpException('Requested Field does not exist.');
    }

    $response = new AjaxResponse();
    $field_elements = $field->view($view_mode);
    $response->addCommand(
      new ReplaceCommand(
        "#ajax-field-{$entity->getEntityTypeId()}-{$entity->id()}-{$field_name}",
        $this->renderer->render($field_elements)
      )
    );

    return $response;

  }

}
