<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use Drupal\sir_trevor\Plugin\SirTrevorBlockPlugin;

class SirTrevorBlockMock implements SirTrevorBlockPlugin {

  private $displayCss;
  private $displayDependencies;
  private $displayJs;
  private $editorCss;
  private $editorDependencies;
  private $editorJs;
  private $machineName;
  private $definingModule;
  private $template;
  private $iconsFile;

  public function __construct($machineName, $provider = 'sir_trevor') {
    $this->machineName = $machineName;
    $this->definingModule = $provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayCss() {
    return $this->displayCss;
  }

  /**
   * @return mixed
   */
  public function getDisplayDependencies() {
    return $this->displayDependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayJs() {
    return $this->displayJs;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditorCss() {
    return $this->editorCss;
  }

  /**
   * @return mixed
   */
  public function getEditorDependencies() {
    return $this->editorDependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditorJs() {
    return $this->editorJs;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->machineName;
  }


  /**
   * {@inheritdoc}
   */
  public function getDefiningModule() {
    return $this->definingModule;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate() {
    return $this->template;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconsFile() {
    return $this->iconsFile;
  }

  /**
   * @param string $name
   * @param mixed $value
   * @return $this
   */
  public function set($name, $value) {
    $this->{$name} = $value;
    return $this;
  }

  /**
   * @return bool
   */
  public function hasIconsFile() {
    return !empty($this->iconsFile);
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return self::block;
  }
}
