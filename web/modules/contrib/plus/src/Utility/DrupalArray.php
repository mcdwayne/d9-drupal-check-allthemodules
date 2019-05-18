<?php

namespace Drupal\plus\Utility;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Template\Attribute;

/**
 * Class for managing multiple types of attributes commonly found in Drupal.
 *
 * @ingroup utility
 *
 * @see \Drupal\plus\Utility\Attributes
 * @see \Drupal\plus\Utility\Element
 * @see \Drupal\plus\Utility\Variables
 */
class DrupalArray extends ArrayObject implements AttachmentsInterface, RefinableCacheableDependencyInterface {

  /**
   * Defines the "attributes" storage type constant.
   *
   * @var string
   */
  const ATTRIBUTES = 'attributes';

  /**
   * Defines the "body_attributes" storage type constant.
   *
   * @var string
   */
  const BODY = 'body_attributes';

  /**
   * Defines the "content_attributes" storage type constant.
   *
   * @var string
   */
  const CONTENT = 'content_attributes';

  /**
   * Defines the "description_attributes" storage type constant.
   *
   * @var string
   */
  const DESCRIPTION = 'description_attributes';

  /**
   * Defines the "footer_attributes" storage type constant.
   *
   * @var string
   */
  const FOOTER = 'footer_attributes';

  /**
   * Defines the "header_attributes" storage type constant.
   *
   * @var string
   */
  const HEADER = 'header_attributes';

  /**
   * Defines the "label_attributes" storage type constant.
   *
   * @var string
   */
  const LABEL = 'label_attributes';

  /**
   * Defines the "title_attributes" storage type constant.
   *
   * @var string
   */
  const TITLE = 'title_attributes';

  /**
   * Defines the "wrapper_attributes" storage type constant.
   *
   * @var string
   */
  const WRAPPER = 'wrapper_attributes';

  /**
   * An ArrayObject reference to the #attached property.
   *
   * @var \Drupal\plus\Utility\AttachmentsInterface
   */
  protected $attachments;

  /**
   * Stored attribute instances.
   *
   * @var \Drupal\plus\Utility\Attributes[]
   */
  protected $attributes = [];

  /**
   * A prefix to use for retrieving property keys from the array.
   *
   * @var string
   */
  protected $propertyPrefix = '';

  /**
   * {@inheritdoc}
   */
  public function addCacheContexts(array $cache_contexts) {
    BubbleableMetadata::createFromRenderArray($this->__storage)->addCacheContexts($cache_contexts)->applyTo($this->__storage);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheTags(array $cache_tags) {
    BubbleableMetadata::createFromRenderArray($this->__storage)->addCacheTags($cache_tags)->applyTo($this->__storage);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheableDependency($other_object) {
    BubbleableMetadata::createFromRenderArray($this->__storage)->addCacheableDependency($other_object)->applyTo($this->__storage);
    return $this;
  }

  /**
   * Add class(es) to an attributes object.
   *
   * This is a wrapper method to retrieve the correct attributes storage object
   * and then add the class(es) to it.
   *
   * @param string|array $class
   *   An individual class or an array of classes to add.
   * @param string $type
   *   (optional) The type of attributes to use for this method.
   *
   * @return $this
   *
   * @see \Drupal\plus\Utility\Attributes::addClass()
   */
  public function addClass($class, $type = self::ATTRIBUTES) {
    $this->getAttributes($type)->addClass($class);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attach($type, $data, $merge_deep = FALSE) {
    $this->getAttachments()->attach($type, $data, $merge_deep);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attachMultiple(array $attachments = [], $merge_deep = TRUE) {
    $this->getAttachments()->attachMultiple($attachments, $merge_deep);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attachCss($file, array $data = []) {
    $this->getAttachments()->attachCss($file, $data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attachJs($file, array $data = []) {
    $this->getAttachments()->attachJs($file, $data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attachLibrary($library) {
    $this->getAttachments()->attachLibrary($library);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attachSetting($name, $value) {
    $this->getAttachments()->attachSetting($name, $value);
    return $this;
  }

  /**
   * Merges an object's cacheable metadata into the array.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|mixed $object
   *   The object whose cacheability metadata to retrieve. If it implements
   *   CacheableDependencyInterface, its cacheability metadata will be used,
   *   otherwise, the passed in object must be assumed to be uncacheable, so
   *   max-age 0 is set.
   *
   * @return $this
   */
  public function bubbleObject($object) {
    BubbleableMetadata::createFromRenderArray($this->__storage)->merge(BubbleableMetadata::createFromObject($object))->applyTo($this->__storage);
    return $this;
  }

  /**
   * Merges a render array's cacheable metadata into the array.
   *
   * @param array $build
   *   A render array.
   *
   * @return $this
   */
  public function bubbleRenderArray(array $build) {
    BubbleableMetadata::createFromRenderArray($this->__storage)->merge(BubbleableMetadata::createFromRenderArray($build))->applyTo($this->__storage);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedCss($file = NULL) {
    return $this->getAttachments()->getAttachedCss($file);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedJs($file = NULL) {
    return $this->getAttachments()->getAttachedJs($file);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedLibraries() {
    return $this->getAttachments()->getAttachedLibraries();
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings($name = NULL) {
    return $this->getAttachments()->getAttachedSettings($name);
  }


  /**
   * {@inheritdoc}
   */
  public function getAttachment($type) {
    return $this->getAttachments()->getAttachment($type);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\plus\Utility\AttachmentsInterface
   *   An AttachmentsInterface based object.
   */
  public function getAttachments() {
    if (!isset($this->attachments)) {
      $this->attachments = Attachments::reference($this->get('#attached', []));
    }
    return $this->attachments;
  }

  /**
   * Retrieve a specific attribute from an attributes object.
   *
   * This is a wrapper method to retrieve the correct attributes storage object
   * and then retrieve the attribute from it.
   *
   * @param string $name
   *   The specific attribute to retrieve.
   * @param mixed $default
   *   (optional) The default value to set if the attribute does not exist.
   * @param string $type
   *   (optional) The type of attributes to use for this method.
   *
   * @return mixed
   *   A specific attribute value, passed by reference.
   *
   * @see \Drupal\plus\Utility\Attributes::get()
   */
  public function &getAttribute($name, $default = NULL, $type = self::ATTRIBUTES) {
    return $this->getAttributes($type)->get($name, $default);
  }

  /**
   * Retrieves a specific attributes object.
   *
   * @param string $type
   *   (optional) The type of attributes to use for this method.
   *
   * @return \Drupal\plus\Utility\Attributes
   *   An attributes object for $type.
   */
  public function getAttributes($type = self::ATTRIBUTES) {
    if (!isset($this->attributes[$type])) {
      $attributes = &$this->get($this->propertyPrefix . $type, []);
      if ($attributes instanceof Attribute) {
        $attributes = $attributes->toArray();
      }
      $this->attributes[$type] = Attributes::create($attributes);
    }
    return $this->attributes[$type];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return BubbleableMetadata::createFromRenderArray($this->__storage)->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return BubbleableMetadata::createFromRenderArray($this->__storage)->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return BubbleableMetadata::createFromRenderArray($this->__storage)->getCacheMaxAge();
  }

  /**
   * Retrieves classes from an attributes object.
   *
   * This is a wrapper method to retrieve the correct attributes storage object
   * and then retrieve the set classes from it.
   *
   * @param string $type
   *   (optional) The type of attributes to use for this method.
   *
   * @return \Drupal\plus\Utility\AttributeClasses
   *   The classes array, passed by reference.
   *
   * @see \Drupal\plus\Utility\Attributes::getClasses()
   */
  public function &getClasses($type = self::ATTRIBUTES) {
    return $this->getAttributes($type)->getClasses();
  }

  /**
   * {@inheritdoc}
   */
  public function hasAttachedCss($file = NULL) {
    return $this->getAttachments()->hasAttachedCss($file);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAttachedJs($file = NULL) {
    return $this->getAttachments()->hasAttachedJs($file);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAttachedLibrary($library = NULL) {
    return $this->getAttachments()->hasAttachedLibrary($library);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAttachedSetting($name = NULL) {
    return $this->getAttachments()->hasAttachedSetting($name);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAttachment($type, $key = NULL, $check_key = TRUE) {
    return $this->getAttachments()->hasAttachment($type, $key, $check_key);
  }

  /**
   * Indicates whether an attributes object has a specific attribute set.
   *
   * This is a wrapper method to retrieve the correct attributes storage object
   * and then check there if the attribute exists.
   *
   * @param string $name
   *   The attribute to search for.
   * @param string $type
   *   (optional) The type of attributes to use for this method.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @see \Drupal\plus\Utility\Attributes::exists()
   */
  public function hasAttribute($name, $type = self::ATTRIBUTES) {
    return $this->getAttributes($type)->exists($name);
  }

  /**
   * Indicates whether an attributes object has a specific class.
   *
   * This is a wrapper method to retrieve the correct attributes storage object
   * and then check there if a class exists in the attributes object.
   *
   * @param string $class
   *   The class to search for.
   * @param string $type
   *   (optional) The type of attributes to use for this method.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @see \Drupal\plus\Utility\Attributes::hasClass()
   */
  public function hasClass($class, $type = self::ATTRIBUTES) {
    return $this->getAttributes($type)->hasClass($class);
  }

  /**
   * {@inheritdoc}
   */
  public function mergeCacheMaxAge($max_age) {
    BubbleableMetadata::createFromRenderArray($this->__storage)->mergeCacheMaxAge($max_age)->applyTo($this->__storage);
    return $this;
  }

  /**
   * Removes an attribute from an attributes object.
   *
   * This is a wrapper method to retrieve the correct attributes storage object
   * and then remove an attribute from it.
   *
   * @param string|array $name
   *   The name of the attribute to remove.
   * @param string $type
   *   (optional) The type of attributes to use for this method.
   *
   * @return $this
   *
   * @see \Drupal\plus\Utility\Attributes::removeAttribute()
   */
  public function removeAttribute($name, $type = self::ATTRIBUTES) {
    $this->getAttributes($type)->remove($name);
    return $this;
  }

  /**
   * Removes a class from an attributes object.
   *
   * This is a wrapper method to retrieve the correct attributes storage object
   * and then remove the class(es) from it.
   *
   * @param string|array $class
   *   An individual class or an array of classes to remove.
   * @param string $type
   *   (optional) The type of attributes to use for this method.
   *
   * @return $this
   *
   * @see \Drupal\plus\Utility\Attributes::removeClass()
   */
  public function removeClass($class, $type = self::ATTRIBUTES) {
    $this->getAttributes($type)->removeClass($class);
    return $this;
  }

  /**
   * Replaces a class in an attributes object.
   *
   * This is a wrapper method to retrieve the correct attributes storage object
   * and then replace the class(es) in it.
   *
   * @param string $old
   *   The old class to remove.
   * @param string $new
   *   The new class. It will not be added if the $old class does not exist.
   * @param string $type
   *   (optional) The type of attributes to use for this method.
   *
   * @return $this
   *
   * @see \Drupal\plus\Utility\Attributes::replaceClass()
   */
  public function replaceClass($old, $new, $type = self::ATTRIBUTES) {
    $this->getAttributes($type)->replaceClass($old, $new);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttachments(array $attachments) {
    $this->getAttachments()->replace($attachments);
    return $this;
  }

  /**
   * Sets an attribute on an attributes object.
   *
   * This is a wrapper method to retrieve the correct attributes storage object
   * and then set an attribute on it.
   *
   * @param string $name
   *   The name of the attribute to set.
   * @param mixed $value
   *   The value of the attribute to set.
   * @param string $type
   *   (optional) The type of attributes to use for this method.
   *
   * @return $this
   *
   * @see \Drupal\plus\Utility\Attributes::set()
   */
  public function setAttribute($name, $value, $type = self::ATTRIBUTES) {
    $this->getAttributes($type)->set($name, $value);
    return $this;
  }

  /**
   * Sets multiple attributes on an attributes object.
   *
   * This is a wrapper method to retrieve the correct attributes storage object
   * and then merge multiple attributes into it.
   *
   * @param array $values
   *   An associative key/value array of attributes to set.
   * @param string $type
   *   (optional) The type of attributes to use for this method.
   *
   * @return $this
   *
   * @see \Drupal\plus\Utility\Attributes::setAttributes()
   */
  public function setAttributes(array $values = [], $type = self::ATTRIBUTES) {
    $this->getAttributes($type)->replace($values);
    return $this;
  }

}
