<?php

/**
 * @file
 * Contains \Drupal\comment\CommentLazyBuilders.
 */

namespace Drupal\pe_assignment_answer;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;


/**
 * Defines a service for comment #lazy_builder callbacks.
 */
class AssignmentAnswerLazyBuilder {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new CommentLazyBuilders object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder, AccountInterface $current_user) {
    $this->entityManager = $entity_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->currentUser = $current_user;
  }

  /**
   * #lazy_builder callback; builds the answer form.
   *
   * @return array
   *   A renderable array containing the comment form.
   */
  public function renderForm($nid) {
    $values = array(
      'type' => NODE_TYPE_ASSIGNMENT_ANSWER,
    );

    $entity = $this->entityManager->getStorage('node')->create($values);
    $element = $this->entityFormBuilder->getForm($entity);

    $element['field_question'] = [
      '#type' => 'hidden',
      '#default_value' => $nid,
    ];

    // Question doesn't allow file uploads.
    $node = \Drupal\node\Entity\Node::load($nid);
    if (!$node->get('field_allow_uploads')->value) {
      $element['field_allow_uploads']['#access'] = FALSE;
    }
    // Hide vertical tabs.
    $element['advanced']['#access'] = FALSE;
    // @todo: preview
    $element['actions']['preview']['#access'] = FALSE;

    return $element;
  }

}
