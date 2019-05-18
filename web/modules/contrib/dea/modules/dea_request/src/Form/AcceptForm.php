<?php

namespace Drupal\dea_request\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dea\SolutionInterface;
use Drupal\dea_request\Entity\AccessRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;

class AcceptForm extends ContentEntityForm {

  /**
   * @var PluginManagerInterface
   */
  protected $solutionManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, PluginManagerInterface $solution_manager) {
    parent::__construct($entity_manager);
    $this->entityManager = $entity_manager;
    $this->solutionManager = $solution_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('dea.discovery.solution')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $builder = \Drupal::entityTypeManager()->getViewBuilder('dea_request');
    $form['entity'] = $builder->view($this->entity, 'summary');
    
    $form['solutions'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose one or more solutions:'),
      '#options' => array_map(function (SolutionInterface $solution) {
        return $solution->applyDescription();
      }, $this->allSolutions()),
      '#required' => TRUE,
    ];
    return $form;
  }

  protected function allSolutions() {
    $solutions = [];
    foreach ($this->solutionManager->getDefinitions() as $plugin_id => $info) {
      $discovery = $this->solutionManager->createInstance($plugin_id);
      foreach ($discovery->solutions($this->entity->getTarget(), $this->entity->uid->entity, $this->entity->operation->value) as $key => $solution) {
        if (!in_array($solution, $solutions)) {
          $solutions[$key] = $solution;
        }
      }
    }
    return $solutions;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    $entity->status = AccessRequest::ACCEPTED;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->allSolutions()[$form_state->getValue('solutions')]->apply();

    drupal_set_message($this->t('%user\'s request to %operation %label has been accepted.', [
      '%user' => $this->entity->uid->entity->label(),
      '%operation' => $this->entity->operation->value,
      '%label' => $this->entity->getTarget()->label(),
    ]));
    $form_state->setRedirect('dea_request.list');
  }


}
