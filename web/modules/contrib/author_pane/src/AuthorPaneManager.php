<?php

/**
 * @file
 * Contains the \Drupal\author_pane\AuthorPaneManager class.
 */

namespace Drupal\author_pane;

use Drupal\Core\Entity\ContentEntityInterface;
use \Drupal\field\Entity\FieldConfig;

/**
 * Class AuthorPaneManager.
 *
 * @package Drupal\author_pane
 */
class AuthorPaneManager {

  protected $datumPluginManager;

  public $authorPane;

  /**
   * Constructor for AuthorPaneManager.
   *
   * @param AuthorPaneDatumPluginManager $datumPluginManager
   */
  public function __construct(AuthorPaneDatumPluginManager $datumPluginManager) {
    $this->datumPluginManager = $datumPluginManager;
   }


  public function load($author_pane_id) {
    $this->authorPane = \Drupal\author_pane\Entity\AuthorPane::load($author_pane_id);

    return $this->authorPane;
  }

}
