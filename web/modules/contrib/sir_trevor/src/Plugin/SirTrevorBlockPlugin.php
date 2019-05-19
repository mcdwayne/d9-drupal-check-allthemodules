<?php
namespace Drupal\sir_trevor\Plugin;

interface SirTrevorBlockPlugin extends SirTrevorPlugin {
  /**
   * @return string
   */
  public function getDisplayCss();

  /**
   * @return string[]
   */
  public function getDisplayDependencies();

  /**
   * @return string
   */
  public function getDisplayJs();


  /**
   * @return string
   */
  public function getTemplate();

}