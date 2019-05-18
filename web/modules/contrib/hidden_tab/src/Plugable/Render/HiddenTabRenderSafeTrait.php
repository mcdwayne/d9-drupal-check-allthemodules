<?php

namespace Drupal\hidden_tab\Plugable\Render;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Utility;
use Symfony\Component\HttpFoundation\ParameterBag;

trait HiddenTabRenderSafeTrait {

  /**
   * {@inheritdoc}
   */
  public final function render(EntityInterface $entity,
                         HiddenTabPageInterface $page,
                         AccountInterface $user,
                         ParameterBag $bag,
                         array &$output) {
    try {
      $this->render0($entity, $page, $user, $bag, $output);
    }
    catch (\Throwable $error) {
      Utility::renderLog($error, $entity->getEntityTypeId(), '~', $entity->id(), 'plugin_id={h_pid}', [
        'h_pid' => $this->PID,
      ]);
      /** @noinspection PhpUndefinedMethodInspection */
      $output[$this->id()] = [
        '#markup' => t('There was an error displaying the page'),
      ];
    }
  }

  /**
   * This method may throw exceptions and it is handled by render()
   *
   * Sub classes may extend this instead and let render() of this class handle
   * exceptions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   See render().
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   See render().
   * @param \Drupal\Core\Session\AccountInterface $use
   *   See render().
   * @param \Symfony\Component\HttpFoundation\ParameterBag $bag
   *   See render().
   * @param array $output
   *   See render().
   */
  protected abstract function render0(EntityInterface $entity,
                                      HiddenTabPageInterface $page,
                                      AccountInterface $use,
                                      ParameterBag $bag,
                                      array &$output);

}
