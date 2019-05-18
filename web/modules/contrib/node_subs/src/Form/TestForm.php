<?php

namespace Drupal\node_subs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node_subs\Service\NodeService;

/**
 * Class TestForm.
 */
class TestForm extends FormBase {

  /**
   * Drupal\node_subs\Service\NodeService definition.
   *
   * @var \Drupal\node_subs\Service\NodeService
   */
  protected $nodeService;
  /**
   * Constructs a new TestForm object.
   */
  public function __construct(
    NodeService $node_service
  ) {
    $this->nodeService = $node_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('node_subs.nodes')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_subs_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->nodeService->queueProcess();
  }

}
