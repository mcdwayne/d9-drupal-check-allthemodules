<?php

namespace Drupal\filefield_sources_jsonapi;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Paragraph button entities.
 */
interface FileFieldSourcesJSONAPIInterface extends ConfigEntityInterface {

  /**
   * Returns the Paragraph button description.
   *
   * @return string
   *   The Paragraph button description.
   */
  public function getApiUrl();

  /**
   * Returns the paragraphs button type.
   *
   * @return string
   *   The paragraphs button type: button, group.
   */
  public function getParams();

  /**
   * Returns the paragraphs button perent.
   *
   * @return string
   *   The parent paragraphs button id.
   */
  public function getUrlAttributePath();

  /**
   * Returns the paragraphs bundle.
   *
   * @return string
   *   The paragraphs bundle machine name.
   */
  public function getThumbnailUrlAttributePath();

  /**
   * Returns the Paragraph button uri.
   *
   * @return string
   *   The Paragraph button uri.
   */
  public function getTitleAttributePath();

  /**
   * Returns the form display modee.
   *
   * @return string
   *   The form mode machine name.
   */
  public function getAltAttributePath();

  /**
   * Returns the parade_layout value.
   *
   * @return string
   *   The parade_layout value.
   */
  public function getSortOptionList();

  /**
   * Returns the enabled parade_color_scheme values.
   *
   * @return string
   *   The parade_color_scheme values.
   */
  public function getSearchFilter();

  /**
   * Returns the parade_color_scheme value.
   *
   * @return string
   *   The parade_color_scheme value.
   */
  public function getItemsPerPage();

  /**
   * Returns the basicAuthentication value.
   *
   * @return string
   *   The basicAuthentication value.
   */
  public function getBasicAuthentication();

}
