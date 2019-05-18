<?php

namespace Drupal\plus\Utility;

/**
 * Class Attachments.
 */
class Attachments extends ArrayObject implements AttachmentsInterface {

  /**
   * {@inheritdoc}
   */
  public function attach($type, $data, $merge_deep = FALSE) {
    $existing = $this->getAttachment($type);
    if ($merge_deep) {
      $existing->mergeDeep($data);
    }
    else {
      $existing->merge($data);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attachMultiple(array $attachments = [], $merge_deep = TRUE) {
    foreach ($attachments as $type => $data) {
      $this->attach($type, $data, $merge_deep);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function attachCss($file, array $data = []) {
    return $this->attach('css', [$file => ArrayObject::reference($data)]);
  }

  /**
   * {@inheritdoc}
   */
  public function attachJs($file, array $data = []) {
    return $this->attach('js', [$file => ArrayObject::reference($data)]);
  }

  /**
   * {@inheritdoc}
   */
  public function attachLibrary($library) {
    return $this->attach('library', $library);
  }

  /**
   * {@inheritdoc}
   */
  public function attachSetting($name, array $value) {
    return $this->attach('drupalSettings', [$name => $value], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachment($type) {
    return ArrayObject::reference($this->get($type, []));
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return $this->copy();
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedCss($file = NULL) {
    $css = $this->getAttachment('css');
    return isset($file) ? $css->findAll($file) : $css;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings($name = NULL) {
    $settings = &$this->getAttachment('drupalSettings');
    return isset($name) ? $settings->get($name) : $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedJs($file = NULL) {
    $js = $this->getAttachment('js');
    return isset($file) ? $js->findAll($file) : $js;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedLibraries() {
    return $this->getAttachment('library');
  }

  /**
   * {@inheritdoc}
   */
  public function hasAttachment($type, $key = NULL, $check_key = TRUE) {
    return isset($key) ? $this->getAttachment($type)->exists($key, $check_key) : $this->exists($type, $check_key);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAttachedCss($file = NULL) {
    return $this->hasAttachment('css', $file);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAttachedJs($file = NULL) {
    return $this->hasAttachment('js', $file);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAttachedLibrary($library = NULL) {
    return $this->hasAttachment('library', $library);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAttachedSetting($name = NULL) {
    return $this->hasAttachment('drupalSettings', $name);
  }

}
