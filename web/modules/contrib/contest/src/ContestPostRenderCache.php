<?php

namespace Drupal\contest;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a service for contest post render cache callbacks.
 */
class ContestPostRenderCache {
  protected $entityManager;
  protected $formBuilder;
  protected $request;

  /**
   * Constructs a new ContestPostRenderCache object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param Symfony\Component\HttpFoundation\RequestStack $request
   *   The request dependency injection.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder dependency injection.
   */
  public function __construct(EntityManagerInterface $entity_manager, RequestStack $request, FormBuilderInterface $formBuilder) {
    $this->entityManager = $entity_manager;
    $this->formBuilder = $formBuilder;
    $this->request = $request;
  }

  /**
   * Callback for #post_render_cache; replaces placeholder with contest view.
   *
   * @param int $id
   *   The contest ID.
   * @param string $view_mode
   *   The view mode the contest should be rendered with.
   *
   * @return array
   *   A renderable array containing the contest form.
   */
  public function renderViewForm($id, $view_mode) {
    $contest = $this->entityManager->getStorage('contest')->load($id);

    if (!$contest) {
      return ['#markup' => ''];
    }
    $form = $this->formBuilder->getForm('Drupal\contest\Form\ContestViewForm', $contest, $this->request->getCurrentRequest());
    $form['#view_mode'] = $view_mode;

    return $form;
  }

}
