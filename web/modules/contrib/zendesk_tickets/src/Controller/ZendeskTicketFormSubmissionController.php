<?php

namespace Drupal\zendesk_tickets\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns responses for Zendesk Ticket Form Submissions.
 */
class ZendeskTicketFormSubmissionController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Controller constructor.
   *
   * @param RequestStack $request_stack
   *   The request stack.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager) {
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Successful submission page.
   *
   * @return array
   *   The page render array.
   */
  public function success() {
    $request = $this->requestStack->getCurrentRequest();
    $form_type_id = $request->query->get('type');
    if ($form_type_id) {
      $form_type = $this->entityTypeManager->getStorage('zendesk_ticket_form_type')->load($form_type_id);
    }

    $build = [];
    $build['content'] = [
      '#theme' => 'zendesk_tickets_submission_success',
      '#entity' => $form_type,
    ];

    return $build;
  }

}
