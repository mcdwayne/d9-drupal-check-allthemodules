<?php

namespace Drupal\group_taxonomy\Controller;

use Drupal\group\Entity\Controller\GroupContentController;
use Drupal\group\Entity\GroupInterface;

/**
 * Returns responses for 'group_menu' GroupContent routes.
 */
class GroupTaxonomyController extends GroupContentController {

  /**
   * @param GroupInterface $group
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function groupContentOverview(GroupInterface $group) {
    $class = '\Drupal\group_taxonomy\GroupTaxonomyContentListBuilder';
    $definition = $this->entityTypeManager()->getDefinition('group_content');
    return $this->entityTypeManager()->createHandlerInstance($class, $definition)->render();
  }

  /**
   * @param GroupInterface $group
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function groupContentOverviewTitle(GroupInterface $group) {
    return $this->t("%label taxonomies", ['%label' => $group->label()]);
  }

}
