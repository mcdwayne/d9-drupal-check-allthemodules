<?php

namespace Drupal\onlyone;

/**
 * Interface OnlyOneInterface.
 */
interface OnlyOneInterface {

  /**
   * Returns a content type label list.
   *
   * @return array
   *   An associative array with the content type machine name as key
   *   and his label as value.
   */
  public function getContentTypesList();

  /**
   * Returns if exists nodes of a content type in the actual language.
   *
   * @param string $content_type
   *   Content type machine name.
   * @param string $language
   *   The language to check. If the variable is not provided and the site is
   *   multilingual the actual language will be taken.
   *
   * @return int
   *   If exists nodes return the first node nid otherwise return 0.
   */
  public function existsNodesContentType($content_type, $language = NULL);

  /**
   * Delete the content type config variable.
   *
   * @param string $content_type
   *   Content type machine name.
   *
   * @return bool
   *   Return TRUE if the content type config was deleted or FALSE if not
   *   exists.
   */
  public function deleteContentTypeConfig($content_type);

  /**
   * Returns the language label.
   *
   * @param string $language
   *   The language code.
   *
   * @return string
   *   The language label.
   */
  public function getLanguageLabel($language);

  /**
   * Return the available content types.
   *
   * @return array
   *   An array with the available content types machine name.
   */
  public function getAvailableContentTypes();

  /**
   * Return the not available content types.
   *
   * @return array
   *   An array with the not available content types machine name.
   */
  public function getNotAvailableContentTypes();

  /**
   * Return the available content types with their number of nodes.
   *
   * @return array
   *   An array of objects with the available content types keyed by content
   *   type machine name.
   */
  public function getAvailableContentTypesSummarized();

  /**
   * Return the not available content types with their number of nodes.
   *
   * @return array
   *   An array of objects with the not available content types keyed by content
   *   type machine name.
   */
  public function getNotAvailableContentTypesSummarized();

  /**
   * Set the formatter for printing.
   *
   * @param \Drupal\onlyone\OnlyOnePrintStrategyInterface $formatter
   *   The formatter type.
   *
   * @return \Drupal\onlyone\OnlyOneInterface
   *   An object implementing the OnlyOneInterface interface.
   */
  public function setFormatter(OnlyOnePrintStrategyInterface $formatter);

  /**
   * Return a list of available content types for print.
   *
   * @return array
   *   An array of available content types to print keyed by content type
   *   machine name.
   */
  public function getAvailableContentTypesForPrint();

  /**
   * Return a list of non-available content types for print.
   *
   * @return array
   *   An array of non-available content types to print keyed by content type
   *   machine name.
   */
  public function getNotAvailableContentTypesForPrint();

}
