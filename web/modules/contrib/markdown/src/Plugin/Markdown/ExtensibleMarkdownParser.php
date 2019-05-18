<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * Class ExtensibleMarkdownParser.
 */
abstract class ExtensibleMarkdownParser extends BaseMarkdownParser implements ExtensibleMarkdownParserInterface {

  /**
   * MarkdownExtension plugins specific to a parser.
   *
   * @var array
   */
  protected static $extensions;

  /**
   * {@inheritdoc}
   */
  public function alterGuidelines(array &$guides = []) {
    // Allow enabled extensions to alter existing guides.
    foreach ($this->getExtensions() as $plugin_id => $extension) {
      if ($extension instanceof MarkdownGuidelinesAlterInterface) {
        $extension->alterGuidelines($guides);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGuidelines() {
    $guides = parent::getGuidelines();

    // Allow enabled extensions to provide their own guides.
    foreach ($this->getExtensions() as $plugin_id => $extension) {
      if ($extension instanceof MarkdownGuidelinesInterface && ($element = $extension->getGuidelines())) {
        $guides['extensions'][$plugin_id] = $element;
      }
    }

    return $guides;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions($enabled = NULL) {
    if (!isset(static::$extensions["$enabled:$this->pluginId"])) {
      /** @var \Drupal\markdown\MarkdownExtensions $markdown_extensions */
      $markdown_extensions = \Drupal::service('plugin.manager.markdown.extension');
      static::$extensions["$enabled:$this->pluginId"] = $this->filter && $this->filter->isEnabled() ? $markdown_extensions->getExtensions($this->pluginId, $enabled) : [];
    }

    /* @type \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface $extension */
    foreach (static::$extensions["$enabled:$this->pluginId"] as $id => $extension) {
      if (isset($this->settings[$id])) {
        $extension->setSettings($this->settings[$id]);
      }
    }

    /** @var \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[] $extensions */
    $extensions = static::$extensions["$enabled:$this->pluginId"];
    return $extensions;
  }

}
