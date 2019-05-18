<?php

namespace Drupal\flashpoint_community_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;


/**
 * Provides the route controller for flashpoint_community_content.
 */

class FlashpointCommunityContentController extends ControllerBase
{

  /**
   * Creates the settings page
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to build the page
   * @return $text;
   *   The page text to return
   */
  public function management(GroupInterface $group)
  {
    $markup = '<h3>' . t('Manage Community') . '</h3>';
    $markup .= '<p>' . t('Please use the subsection links to manage content in this community.') . '</p>';

    return ['#markup' => $markup];
  }
}