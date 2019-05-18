<?php

namespace Drupal\shortcode\Plugin;

use Drupal\media\Entity\Media;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

/**
 * Provides a base class for Shortcode plugins.
 *
 * @see \Drupal\filter\Annotation\Filter
 * @see \Drupal\shortcode\Shortcode\ShortcodePluginManager
 * @see \Drupal\shortcode\Plugin\ShortcodeInterface
 * @see plugin_api
 */
abstract class ShortcodeBase extends PluginBase implements ShortcodeInterface {

  /**
   * The plugin ID of this filter.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The name of the provider that owns this filter.
   *
   * @var string
   */
  public $provider;

  /**
   * A Boolean indicating whether this filter is enabled.
   *
   * @var bool
   */
  public $status = FALSE;

  /**
   * An associative array containing the configured settings of this filter.
   *
   * @var array
   */
  public $settings = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->provider = $this->pluginDefinition['provider'];

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'provider' => $this->pluginDefinition['provider'],
      'status' => $this->status,
      'settings' => $this->settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['status'])) {
      $this->status = (bool) $configuration['status'];
    }
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'provider' => $this->pluginDefinition['provider'],
      'status' => FALSE,
      'settings' => $this->pluginDefinition['settings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->pluginDefinition['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Implementations should work with and return $form. Returning an empty
    // array here allows the text format administration form to identify whether
    // this shortcode plugin has any settings form elements.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
  }

  /**
   * Combines user attributes with known attributes.
   *
   * The $defaults should be considered to be all of the attributes which are
   * supported by the caller and given as a list. The returned attributes will
   * only contain the attributes in the $defaults list.
   *
   * If the $attributes list has unsupported attributes, they will be ignored
   * and removed from the final return list.
   *
   * @param array $defaults
   *   Entire list of supported attributes and their defaults.
   * @param array $attributes
   *   User defined attributes in Shortcode tag.
   *
   * @return array
   *   Combined and filtered attribute list.
   */
  public function getAttributes(array $defaults, array $attributes) {
    $attributes = (array) $attributes;
    $out = [];
    foreach ($defaults as $name => $default) {
      if (array_key_exists($name, $attributes)) {
        $out[$name] = $attributes[$name];
      }
      else {
        $out[$name] = $default;
      }
    }
    return $out;
  }

  /**
   * Add a class into a classes string if not already inside.
   *
   * @param mixed|string|array $classes
   *   The classes string or array.
   * @param string $new_class
   *   The class to add.
   *
   * @return string
   *   The proper classes string.
   */
  public function addClass($classes = '', $new_class = '') {
    if (empty($classes)) {
      $classes = [];
    }
    elseif (!is_array($classes)) {
      $classes = explode(' ', Html::escape($classes));
    }

    $classes[] = Html::escape($new_class);
    $classes = array_unique($classes);

    return implode(' ', $classes);
  }

  /**
   * Returns a url to be used in a link element given path or url.
   *
   * If a path is supplied, an absolute url will be returned.
   *
   * @param string $path
   *   The internal path to be translated.
   * @param bool $media_file_url
   *   TRUE If a media path is supplied, return the file url.
   */
  public function getUrlFromPath($path, $media_file_url = FALSE) {

    if ($path === '<front>') {
      $path = '/';
    }

    // Path validator. Return the path if an absolute url is detected.
    if (UrlHelper::isValid($path, TRUE)) {
      return $path;
    }

    // Add a leading slash if not present.
    $path = '/' . ltrim($path, '/');

    if (!empty($media_file_url) && substr($path, 0, 6) === "/media") {
      $mid = $this->getMidFromPath($path);
      if ($mid) {
        return $this->getMediaFileUrl($mid);
      }
    }
    else {
      /** @var \Drupal\Core\Path\AliasManager $alias_manager */
      $alias_manager = \Drupal::service('path.alias_manager');
      $alias = $alias_manager->getAliasByPath($path);
    }

    // Convert relative URL to absolute.
    $url = Url::fromUserInput($alias, ['absolute' => TRUE])->toString();

    return $url;
  }

  /**
   * Extracts the media id from a 'media/x' system path.
   *
   * @param string $path
   *   The internal path to be translated.
   *
   * @return mixed|int|bool
   *   The media id if found.
   */
  public function getMidFromPath($path) {
    if (preg_match('/media\/(\d+)/', $path, $matches)) {
      return $matches[1];
    }
    return FALSE;
  }

  /**
   * Get the file url for a media object.
   *
   * @param int $mid
   *   Media id.
   *
   * @return mixed|int|bool
   *   The media id if found.
   */
  public function getMediaFileUrl($mid) {
    $media_entity = Media::load($mid);
    $field_media = $this->getMediaField($media_entity);
    if ($field_media) {
      $file = $field_media->entity;
      return file_create_url($file->getFileUri());
    }
    return FALSE;
  }

  /**
   * Get a media entity field.
   *
   * Loop through Drupal media file fields, and return a field object if
   * found.
   *
   * @param \Drupal\media\Entity\Media $entity
   *   Drupal media entity.
   *
   * @return mixed|object|bool
   *   If available, the field object.
   */
  public function getMediaField(Media $entity) {
    $media_file_fields = [
      'field_media_file',
      'field_media_image',
      'field_media_video_file',
      'field_media_audio_file',
    ];
    foreach ($media_file_fields as $field_name) {
      if ($entity->hasField($field_name)) {
        return $entity->get($field_name);
      }
    }
    return FALSE;
  }

  /**
   * Returns image properties for a given image media entity id.
   *
   * @param int $mid
   *   Media entity id.
   *
   * @return array
   *   File properties: `alt` and `path` where available.
   */
  public function getImageProperties($mid) {
    $properties = [
      'alt' => '',
      'path' => '',
    ];
    if (intval($mid)) {
      $media_entity = Media::load($mid);
    }
    if ($media_entity) {
      $field_media = $this->getMediaField($media_entity);
    }
    if ($field_media) {
      $file = $field_media->entity;
      if (isset($field_media->alt)) {
        $properties['alt'] = $field_media->alt;
      }
    }
    if ($file) {
      $properties['path'] = $file->getFileUri();
    }
    return $properties;
  }

  /**
   * Returns a suitable title string given the user provided title and text.
   *
   * @param string $title
   *   The user provided title.
   * @param string $text
   *   The user provided text.
   *
   * @return string
   *   The title to be used.
   */
  public function getTitleFromAttributes($title, $text) {

    // Allow setting no title.
    if ($title === '<none>') {
      $title = '';
    }
    else {
      $title = empty($title) ? trim(strip_tags($text)) : Html::escape($title);
    }

    return $title;
  }

  /**
   * Wrapper for renderPlain.
   *
   * We use renderplain so that the shortcode's cache tags would not bubble up
   * to the parent and affect cacheability. Shortcode should be part of content
   * and self-container.
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   *
   * @return \Drupal\Component\Render\MarkupInterface|mixed
   *   Element stripped of any bubbleable metadata.
   */
  public function render(array &$elements) {
    /** @var \Drupal\Core\Render\Renderer $renderer */
    $renderer = \Drupal::service('renderer');
    return $renderer->renderPlain($elements);
  }

}
