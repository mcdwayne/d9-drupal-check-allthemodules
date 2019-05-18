<?php

/**
 * @file
 * Contains \Drupal\content_planner\Plugin\DashboardBlock\CustomHTMLBlock1.
 */

namespace Drupal\content_planner\Plugin\DashboardBlock;

use Drupal\content_planner\DashboardBlockBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a user block for Content Planner Dashboard
 *
 * @DashboardBlock(
 *   id = "custom_html_block_1",
 *   name = @Translation("Text/HTML Widget 1")
 * )
 */
class CustomHTMLBlock1 extends CustomHTMLBlockBase {

}