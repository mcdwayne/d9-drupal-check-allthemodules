<?php
namespace Drupal\dea_request\Form;

use Drupal\dea\SolutionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait RevokeFormTrait {
  /**
   * @var \Drupal\dea\SolutionDiscovery
   */
  protected $solutionDiscovery;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('dea.discovery.solution')
    );
  }

  protected function revokeOptions($entity) {
    $solutions = $this->allSolutions($entity);
    if (!$solutions) {
      return [];
    }
    return [
      '#type' => 'checkboxes',
      '#title' => $this->t('Revert solutions'),
      '#description' => $this->t('Choose which solutions to revert.'),
      '#options' => array_map(function (SolutionInterface $solution) {
        return $solution->revokeDescription();
      }, $solutions),
      '#default_value' => array_keys($solutions),
    ];
  }
  
  protected function revoke($ids, $entity) {
    if (!$ids) {
      return;
    }
    $solutions = $this->allSolutions($entity);
    foreach ($ids as $id) {
      $solutions[$id]->revoke();
    }
  }

  protected function allSolutions($entity) {
    $target = $entity->getTarget();
    $subject = $entity->getOwner();
    $operation = $entity->getOperation();

    return array_filter($this->solutionDiscovery->solutions($target, $subject, $operation), function (SolutionInterface $solution) {
      return $solution->isApplied();
    });
  }
}
