<?php

namespace Drupal\sir_trevor\Plugin;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\NestedArray;
use Webmozart\Assert\Assert;

class SirTrevorBlock implements SirTrevorBlockPlugin {

  /** @var string */
  private $displayCss;
  /** @var string[] */
  private $displayDependencies;
  /** @var string */
  private $displayJs;
  /** @var string */
  private $editorCss;
  /** @var string[] */
  private $editorDependencies;
  /** @var string */
  private $editorJs;
  /** @var string */
  private $machineName;
  /** @var string */
  private $definingModule;
  /** @var string */
  private $template;
  /** @var string */
  private $iconsFile;

  /**
   * SirTrevorBlockPlugin constructor.
   * @param array $definition
   *
   * @throws InvalidPluginDefinitionException
   */
  public function __construct($definition) {
    $this->validateDefinition($definition);
    $this->displayCss = NestedArray::getValue($definition, ['assets', 'display', 'css']);
    $this->displayDependencies = NestedArray::getValue($definition, ['assets', 'display', 'dependencies']);
    $this->displayJs = NestedArray::getValue($definition, ['assets', 'display', 'js']);
    $this->editorCss = NestedArray::getValue($definition, ['assets', 'editor', 'css']);
    $this->editorDependencies = NestedArray::getValue($definition, ['assets', 'editor', 'dependencies']);
    $this->editorJs = NestedArray::getValue($definition, ['assets', 'editor', 'js']);
    $this->iconsFile = NestedArray::getValue($definition, ['assets', 'icon_file']);

    $this->machineName = $definition['id'];
    $this->definingModule = $definition['provider'];
    $this->template = $definition['template'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayCss() {
    return $this->displayCss;
  }

  /**
   * @return string[]
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
   * @return string[]
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
   * @param array $definition
   * @throws InvalidPluginDefinitionException
   */
  private function validateDefinition(array $definition) {
    try {
      Assert::keyExists($definition, 'template', "\"{$definition['id']}\" must define \"template\".");
    }
    catch (\InvalidArgumentException $e) {
      throw new InvalidPluginDefinitionException($definition['id'], $e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return self::block;
  }
}
