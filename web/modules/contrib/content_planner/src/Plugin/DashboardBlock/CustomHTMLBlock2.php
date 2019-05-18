<?php

/**
 * @file
 * Contains \Drupal\content_planner\Plugin\DashboardBlock\CustomHTMLBlock2.
 */

namespace Drupal\content_planner\Plugin\DashboardBlock;

use Drupal\content_planner\DashboardBlockBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a user block for Content Planner Dashboard
 *
 * @DashboardBlock(
 *   id = "custom_html_block_2",
 *   name = @Translation("Text/HTML Widget 2")
 * )
 */
class CustomHTMLBlock2 extends CustomHTMLBlockBase {

}