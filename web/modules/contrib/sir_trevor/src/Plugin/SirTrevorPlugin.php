<?php
namespace Drupal\sir_trevor\Plugin;

interface SirTrevorPlugin {

  const block = 'block';
  const mixin = 'mixin';

  /**
   * @return string
   */
  public static function getType();

  /**
   * @return string
   */
  public function getEditorCss();

  /**
   * @return string[]
   */
  public function getEditorDependencies();

  /**
   * @return string
   */
  public function getEditorJs();

  /**
   * @return string
   */
  public function getMachineName();

  /**
   * @return string
   */
  public function getDefiningModule();

  /**
   * @return bool
   */
  public function hasIconsFile();

  /**
   * @return string
   */
  public function getIconsFile();
}