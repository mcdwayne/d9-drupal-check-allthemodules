<?php

/**
 * @file
 * Contains \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface.
 */

namespace Drupal\nodeletter\NodeletterSender;


/**
 * Interface for newsletter templates.
 *
 * NodeletterSender plugins have to provide templates which can be
 * configured in nodeletter node type settings.
 *
 * Interface NewsletterTemplateInterface
 * @package Drupal\nodeletter\NodeletterSender
 */
interface NewsletterTemplateInterface {


  /**
   * Get template ID.
   *
   * @return string
   */
  public function getId();


  /**
   * Get template label.
   *
   * @return string
   */
  public function getLabel();


}

