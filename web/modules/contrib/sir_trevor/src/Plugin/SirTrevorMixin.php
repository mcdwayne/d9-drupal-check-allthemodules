<?php

namespace Drupal\sir_trevor\Plugin;

use Drupal\Component\Utility\NestedArray;

/**
 * The Sir Trevor javascript library allows for code re-use through what they
 * call mixins, which are javascript objects with a set of functions and
 * properties. Examples of what these mixins are and how they work can be found
 * throughout the Sir Trevor source code.
 *
 * @see https://github.com/madebymany/sir-trevor-js/tree/master/src/block_mixins
 * @see https://github.com/madebymany/sir-trevor-js/blob/master/src/block.js#L54
 *
 * This class holds the definition of a single custom Sir Trevor mixin.
 */
class SirTrevorMixin implements SirTrevorPlugin {

  private $editorCss;
  private $editorDependencies;
  private $editorJs;
  private $machineName;
  private $definingModule;
  private $iconsFile;

  public function __construct(array $definition) {
    $this->editorCss = NestedArray::getValue($definition, ['assets', 'editor', 'css']);
    $this->editorDependencies = NestedArray::getValue($definition, ['assets', 'editor', 'dependencies']);
    $this->editorJs = NestedArray::getValue($definition, ['assets', 'editor', 'js']);
    $this->iconsFile = NestedArray::getValue($definition, ['assets', 'icon_file']);

    $this->machineName = $definition['id'];
    $this->definingModule = $definition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEditorCss() {
    return $this->editorCss;
  }

  /**
   * {@inheritdoc}
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
  public function hasIconsFile() {
    return !empty($this->getIconsFile());
  }

  /**
   * {@inheritdoc}
   */
  public function getIconsFile() {
    return $this->iconsFile;
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return self::mixin;
  }
}
