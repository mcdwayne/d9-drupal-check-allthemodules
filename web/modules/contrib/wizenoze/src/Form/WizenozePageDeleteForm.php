<?php

namespace Drupal\wizenoze\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Search page entities.
 */
class WizenozePageDeleteForm extends EntityDeleteForm {

  /**
   * Protected routeBuilder variable.
   *
   * @var Drupal\Core\ProxyClass\Routing\RouteBuilder
   */
  protected $routeBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(RouteBuilder $routeBuilder) {
    $this->routeBuilder = $routeBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.wizenoze.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message($this->t('@search_page_page_label has been deleted.', ['@search_page_page_label' => $this->entity->label()]));

    // Trigger router rebuild.
    $this->routeBuilder->rebuild();

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
