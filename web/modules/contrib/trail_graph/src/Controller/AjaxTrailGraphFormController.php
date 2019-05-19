<?php

namespace Drupal\trail_graph\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilder;

/**
 * Class AjaxNodeOrderFormController.
 */
class AjaxTrailGraphFormController extends ControllerBase {

  /**
   * Drupal\Core\Form\FormBuilder definition.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Constructs a new AjaxNodeOrderFormController object.
   */
  public function __construct(FormBuilder $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Loads Node order form in to trail graph sidebar.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   Taxonomy term to order.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response that replaces content of sidebar.
   */
  public function loadNodeOrderForm(TermInterface $taxonomy_term) {
    $response = new AjaxResponse();
    $form = $this->formBuilder->getForm('\Drupal\trail_graph\Form\TrailGraphNodeorderForm', $taxonomy_term);
    return $response->addCommand(new HtmlCommand('#trails-form-tab-content', $form));
  }

}
