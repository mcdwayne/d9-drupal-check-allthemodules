<?php

namespace Drupal\hidden_tab\Plugable\Render;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * For placing elements in the renderable hidden tab page output.
 *
 * @see \Drupal\hidden_tab\Controller\XPageRenderController
 */
interface HiddenTabRenderInterface extends HiddenTabPluginInterfaceBase {

  const PID = 'hidden_tab_render';

  /**
   * Check if user has access to view output of the plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity being accessed.
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   page being accessed.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The accessing user.
   *
   * @return AccessResult
   *   Whether if user has access.
   */
  public function access(EntityInterface $entity,
                         HiddenTabPageInterface $page,
                         AccountInterface $user): AccessResult;

  /**
   * Render the component
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being visited.
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page being visited.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Accessing user.
   * @param \Symfony\Component\HttpFoundation\ParameterBag $bag
   *   Received parameters.
   * @param array $output
   *   The generated output, ready to be flushed as renderable.
   */
  public function render(EntityInterface $entity,
                         HiddenTabPageInterface $page,
                         AccountInterface $user,
                         ParameterBag $bag,
                         array &$output);

}
