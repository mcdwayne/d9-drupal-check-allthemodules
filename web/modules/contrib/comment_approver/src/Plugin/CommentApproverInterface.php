<?php

namespace Drupal\comment_approver\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for Comment approver plugins.
 */
interface CommentApproverInterface extends ConfigurablePluginInterface,PluginInspectionInterface {

  /**
   * Return the name of the plugin.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns the description of Comment Approver.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Returns True if comment is ok.
   *
   * @return bool
   */
  public function isCommentFine($comment);

  /**
   * Returns settings form structure or False if no settings form is needed.
   *
   * @return array|bool
   */
  public function settingsForm();

}
