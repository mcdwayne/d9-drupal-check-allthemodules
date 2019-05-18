<?php

namespace Drupal\micon;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderableInterface;

/**
 * Defines the Micon icon.
 */
class MiconIcon implements MiconIconInterface, RenderableInterface {

  /**
   * The Micon type. Either 'font' or 'image'.
   *
   * @var string
   */
  protected $type;

  /**
   * The Micon icon data.
   *
   * @var array
   */
  protected $data;

  /**
   * The Attribute object.
   *
   * @var \Drupal\Core\Template\Attribute
   */
  protected $attributes;

  /**
   * Constructs a new MiconIcon.
   *
   * @param string $type
   *   The type of icon. Either 'font' or 'image'.
   * @param array $data
   *   The icon data array provided from the Micon package info file.
   * @param array $attributes
   *   The attributes to add to the group wrapper.
   */
  public function __construct($type, array $data, array $attributes = []) {
    $this->type = $type;
    $this->data = $data;
    $this->setAttributes($attributes);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageId() {
    return $this->data['package_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageLabel() {
    return $this->data['package_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefix() {
    return $this->data['prefix'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTag() {
    return $this->data['tag'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSelector() {
    return $this->getPrefix() . $this->getTag();
  }

  /**
   * {@inheritdoc}
   */
  public function getHex() {
    return $this->type == 'font' ? '\\' . dechex($this->data['properties']['code']) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getWrappingElement() {
    return $this->type == 'image' ? 'svg' : 'i';
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren() {
    $build = [];
    if ($this->type == 'font') {
      // Font glyphs cannot have more than one color by default. Using CSS,
      // IcoMoon layers multiple glyphs on top of each other to implement
      // multicolor glyphs. As a result, these glyphs take more than one
      // character code and cannot have ligatures. To avoid multicolor glyphs,
      // reimport your SVG after changing all its colors to the same color.
      if (!empty($this->data['properties']['codes']) && count($this->data['properties']['codes'])) {
        for ($i = 1; $i <= count($this->data['properties']['codes']); $i++) {
          $build[]['#markup'] = '<span class="path' . $i . '"></span>';
        }
      }
    }
    if ($this->type == 'image') {
      $build['#markup'] = Markup::create('<use xlink:href="' . $this->data['directory'] . '/symbol-defs.svg#' . $this->getSelector() . '"></use>');
      $build['#allowed_tags'] = ['use', 'xlink'];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function addClass($classes) {
    $this->attributes->addClass($classes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttributes(array $attributes) {
    $this->attributes = new Attribute($attributes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttribute($attribute, $value) {
    $this->attributes->setAttribute($attribute, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    return [
      '#theme' => 'micon_icon',
      '#icon' => $this,
      '#attributes' => $this->attributes->toArray(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function toMarkup() {
    $elements = $this->toRenderable();
    return \Drupal::service('renderer')->render($elements);
  }

  /**
   * {@inheritdoc}
   */
  public function toJson() {
    return json_encode(trim(preg_replace('/<!--(.|\s)*?-->/', '', $this->toMarkup()->jsonSerialize())));
  }

}
