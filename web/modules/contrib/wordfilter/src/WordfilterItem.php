<?php

namespace Drupal\wordfilter;

use Drupal\wordfilter\Entity\WordfilterConfigurationInterface;

class WordfilterItem {

  /**
   * The parent entity, which is holding the filtering item.
   *
   * @var \Drupal\wordfilter\Entity\WordfilterConfigurationInterface
   */
  protected $parent;

  /**
   * The item data as array.
   *
   * Contains the following keys:
   *   'delta': The delta of the filtering item.
   *   'substitute': The substitution string.
   *   'filter_words': The list of words to filter.
   *                   This list is being stored as a string,
   *                   words are being separated by ", ".
   *
   * @var array
   */
  protected $data;

  /**
   * WordfilterItem constructor.
   *
   * @param array &$data
   *   The item values as array.
   *   Must contain the keys 'delta', 'substitute' and 'filter_words',
   *   which are all strings.
   */
  public function __construct(WordfilterConfigurationInterface $parent, array &$data) {
    $this->setParent($parent);
    $this->setData($data);
  }

  /**
   * Get the parent entity.
   *
   * @return \Drupal\wordfilter\Entity\WordfilterConfigurationInterface
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Set the parent entity.
   *
   * @param \Drupal\wordfilter\Entity\WordfilterConfigurationInterface $parent
   */
  protected function setParent(WordfilterConfigurationInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * Set the item data.
   *
   * @param array &$data
   */
  protected function setData(&$data) {
    $this->data = &$data;
  }

  /**
   * Get the delta of this filtering item.
   *
   * @return integer
   */
  public function getDelta() {
    return $this->data['delta'];
  }

  /**
   * Set the delta of this filtering item.
   *
   * @param $delta
   */
  public function setDelta($delta) {
    $this->data['delta'] = $delta;
  }

  /**
   * Get the substitution string.
   *
   * @return string
   */
  public function getSubstitute() {
    return $this->data['substitute'];
  }

  /**
   * Set the substitution string.
   *
   * @param string $substitute
   *   The substitution string.
   */
  public function setSubstitute($substitute) {
    $this->data['substitute'] = $substitute;
  }

  /**
   * Get the filter words.
   *
   * @return array
   *  An array of the filter words.
   */
  public function getFilterWords() {
    return $this->wordsToArray($this->data['filter_words']);
  }

  /**
   * Set the filter words.
   *
   * @param mixed $words
   *   A string or array of filter words.
   *   As a string, multiple words are to be separated by ", ".
   */
  public function setFilterWords($words) {
    if (is_string($words)) {
      $words = $this->wordsToArray($words);
    }
    // Filter out duplicates.
    // Todo Replace with a case-insensitive filter.
    $words = array_unique($words);

    $words = implode(", ", $words);
    $this->data['filter_words'] = $words;
  }

  /**
   * Helper function to transform a string of words to an array.
   *
   * @param $words_string
   *  The string of words, usually separated by ", ".
   * @return array
   *  The array of words.
   */
  protected function wordsToArray($words_string) {
    $words_array = !empty($words_string) ? explode(',', $words_string) : [];
    $words_array = array_map('trim', $words_array);
    return $words_array;
  }

  /**
   * Array representation of this object.
   *
   * @return array
   */
  public function toArray() {
    return $this->data;
  }
}
