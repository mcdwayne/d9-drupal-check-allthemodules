<?php

namespace Drupal\bibcite;

use Drupal\bibcite\Entity\CslStyleInterface;
use Drupal\bibcite\Plugin\BibCiteProcessorInterface;

/**
 * Defines an interface for Styler service.
 */
interface CitationStylerInterface {

  /**
   * Render CSL data to bibliographic citation.
   *
   * @param array|\stdClass $data
   *   Array or object of values in CSL format.
   *
   * @return string
   *   Rendered bibliographic citation.
   */
  public function render($data);

  /**
   * Set processor plugin.
   *
   * @param \Drupal\bibcite\Plugin\BibCiteProcessorInterface $processor
   *   Processor plugin object.
   *
   * @return \Drupal\bibcite\CitationStylerInterface
   *   The called Styler object.
   */
  public function setProcessor(BibCiteProcessorInterface $processor);

  /**
   * Load plugin object by identifier.
   *
   * @param string $processor_id
   *   Identifier of processor plugin.
   *
   * @return \Drupal\bibcite\CitationStylerInterface
   *   The called Styler object.
   */
  public function setProcessorById($processor_id);

  /**
   * Get current processor plugin.
   *
   * @return \Drupal\bibcite\Plugin\BibCiteProcessorInterface|null
   *   Current processor plugin.
   */
  public function getProcessor();

  /**
   * Get all available processors plugins.
   *
   * @return array
   *   List of available processor plugins.
   */
  public function getAvailableProcessors();

  /**
   * Get current CSL style.
   *
   * @return \Drupal\bibcite\Entity\CslStyleInterface|null
   *   Current CSL style.
   */
  public function getStyle();

  /**
   * Set CSL style.
   *
   * @param \Drupal\bibcite\Entity\CslStyleInterface $csl_style
   *   CSL style object.
   *
   * @return \Drupal\bibcite\CitationStylerInterface
   *   The called Styler object.
   */
  public function setStyle(CslStyleInterface $csl_style);

  /**
   * Load and set style by identifier.
   *
   * @param string $style_id
   *   CSL style identifier.
   *
   * @return \Drupal\bibcite\CitationStylerInterface
   *   The called Styler object.
   */
  public function setStyleById($style_id);

  /**
   * Get list of available bibliographic styles.
   *
   * @return array
   *   Bibliographic styles list.
   */
  public function getAvailableStyles();

  /**
   * Get current used language code.
   *
   * @return string
   *   Current language code.
   */
  public function getLanguageCode();

  /**
   * Set language code.
   *
   * @param string $lang_code
   *   Language code.
   *
   * @return \Drupal\bibcite\CitationStylerInterface
   *   The called Styler object.
   */
  public function setLanguageCode($lang_code);

}
