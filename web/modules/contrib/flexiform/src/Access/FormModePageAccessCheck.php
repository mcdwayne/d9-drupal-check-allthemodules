<?php

namespace Drupal\flexiform\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks access for displaying configuration translation page.
 */
class FormModePageAccessCheck implements AccessInterface {

  /**
   * Flexiform form mode access callback.
   */
  public function access(EntityFormMode $form_mode, Request $request, AccountInterface $account) {
    $entities = $this->getProvidedEntities($form_mode, $request);
    $access_result = NULL;
    foreach ($entities as $entity) {
      if (!isset($access_result)) {
        $access_result = $entity->access('edit', $account, TRUE);
      }
      else {
        $access_result->andIf($entity->access('edit', $account, TRUE));
      }
    }

    return $access_result;
  }

  /**
   * Get the provided entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of provided entities keyed by machine name.
   */
  protected function getProvidedEntities(EntityFormMode $form_mode, Request $request) {
    $route_match = RouteMatch::createFromRequest($request);
    $settings = $form_mode->getThirdPartySetting('flexiform', 'exposure');

    $provided = [];
    $provided['base_entity'] = $route_match->getParameter('base_entity');
    foreach ($settings['parameters'] as $namespace => $info) {
      if ($provided_entity = $route_match->getParameter($namespace)) {
        $provided[$namespace] = $provided_entity;
      }
    }

    return $provided;
  }

}
