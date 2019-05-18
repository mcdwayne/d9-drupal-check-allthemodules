<?php

namespace Drupal\plus\Utility;

/**
 * Defines an interface objects that contains #attached metadata.
 *
 * @see \Drupal\Core\Render\AttachmentsTrait
 */
interface AttachmentsInterface {

  /**
   * Attaches a specific type of attachment.
   *
   * @param string $type
   *   The type of attachment to add.
   * @param mixed $data
   *   The attachment data to add.
   * @param bool $merge_deep
   *   Flag indicating whether to deeply merge $data with existing data, if
   *   $data is an array.
   *
   * @return static
   */
  public function attach($type, $data, $merge_deep = FALSE);

  /**
   * Attaches CSS.
   *
   * @param string $file
   *   The name of the CSS file to attach.
   * @param array $data
   *   The CSS data to add.
   *
   * @return static
   */
  public function attachCss($file, array $data = []);

  /**
   * Attaches JavaScript.
   *
   * @param string $file
   *   The name of the JavaScript file to add.
   * @param array $data
   *   The JavaScript data to attach.
   *
   * @return static
   */
  public function attachJs($file, array $data = []);

  /**
   * Adds JavaScript data.
   *
   * @param string $library
   *   The library to add.
   *
   * @return static
   */
  public function attachLibrary($library);

  /**
   * Attaches multiple attachments.
   *
   * @param array $attachments
   *   The attachments to merge in.
   * @param bool $merge_deep
   *   Flag indicating whether to deeply merge $data with existing data, if
   *   $data is an array.
   *
   * @return static
   */
  public function attachMultiple(array $attachments = [], $merge_deep = TRUE);

  /**
   * Attaches a specific drupalSetting.
   *
   * @param string $name
   *   The name of the drupalSetting to attach.
   * @param array $value
   *   The drupalSetting value for $name.
   *
   * @return static
   */
  public function attachSetting($name, array $value);

  /**
   * Retrieves CSS attachments.
   *
   * @param string $file
   *   A specific CSS file to return. If not passed, then it will return all
   *   CSS files that are currently attached.
   *
   * @return \Drupal\plus\Utility\ArrayObjectInterface
   *   The CSS attachments, if any.
   */
  public function getAttachedCss($file = NULL);

  /**
   * Retrieves JavaScript attachments.
   *
   * @param string $file
   *   A specific JavaScript file to return. If not passed, then it will
   *   return all JavaScript files that are currently attached.
   *
   * @return \Drupal\plus\Utility\ArrayObjectInterface
   *   The JavaScript attachments, if any.
   */
  public function getAttachedJs($file = NULL);

  /**
   * Retrieves library attachments.
   *
   * @return \Drupal\plus\Utility\ArrayObjectInterface
   *   The JavaScript attachments, if any.
   */
  public function getAttachedLibraries();

  /**
   * Retrieves drupalSetting attachments.
   *
   * @param string $name
   *   The name of the drupalSetting to retrieve. If not passed, then it all
   *   drupalSettings will be returned, if any.
   *
   * @return \Drupal\plus\Utility\ArrayObjectInterface
   *   The JavaScript attachments, if any.
   */
  public function getAttachedSettings($name = NULL);

  /**
   * Retrieves a specific type of attachment.
   *
   * @param string $type
   *   The type of attachment to return.
   *
   * @return \Drupal\plus\Utility\ArrayObjectInterface
   *   The attachment $type array.
   */
  public function getAttachment($type);

  /**
   * Retrieves all the attachments.
   *
   * @return \Drupal\plus\Utility\ArrayObjectInterface
   *   An array of values the can be used with #attached in a render array.
   */
  public function getAttachments();

  /**
   * Indicates whether there is a specific type of attachment already set.
   *
   * @param string $type
   *   The type of attachment to check.
   * @param string $key
   *   Optional. A specific key to check inside $type.
   * @param bool $check_key
   *   Flag indicating whether to check if the $key exists or if a value is set.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function hasAttachment($type, $key = NULL, $check_key = TRUE);

  /**
   * Indicates whether there is currently CSS attached.
   *
   * @param string $file
   *   A specific CSS file to check for. If not passed, then it will check if
   *   any CSS files are currently attached.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function hasAttachedCss($file = NULL);

  /**
   * Indicates whether there is currently CSS attached.
   *
   * @param string $file
   *   A specific JavaScript file to check for. If not passed, then it will
   *   check if any JavaScript files are currently attached.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function hasAttachedJs($file = NULL);

  /**
   * Indicates whether a specific or any library is set.
   *
   * @param string $library
   *   The name of the library to check for. If not passed, then it will
   *   check if any libraries are currently attached.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function hasAttachedLibrary($library = NULL);

  /**
   * Indicates whether a specific or any drupalSetting that is attached.
   *
   * @param string $name
   *   The name of the drupalSetting to check for. If not passed, then it will
   *   check if any drupalSettings are currently attached.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function hasAttachedSetting($name = NULL);

}
