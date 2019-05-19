<?php

namespace Drupal\textrazor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Class TextrazorManager.
 *
 * @package Drupal\textrazor
 */
interface TextrazorManagerInterface {

  /**
   * Returns the data types to use form TextRazor response.
   *
   * @return array
   *   Array of types names
   */
  public function getDataTypes();

  /**
   * Returns the tags that already exist in the database.
   *
   * @return array
   *   Array of labels
   */
  public function getCurrentTags();

  /**
   * Returns an array of bundle with TextRazor active.
   *
   * @return array
   *   Array of bundle IDs.
   */
  public function getActiveBundles();

  /**
   * Checks if a form is a the create/edit form of node bundle with TextRazor active.
   *
   * @param string form_id
   *   The ID of the form to check.
   *
   * @return bool
   *   TRUE if the form is a bundle with active TextRazor, otherwise FALSE.
   */
  public function hasTextrazorEnabled($form_id = '');

  /**
   * Modifys an existing form to add and move TextRazor fields.
   *
   * @param array $form
   *   Array containing the form to modify.
   */
  public function prepareForm(array &$form);

  /**
   * Returns a merged option list of all available textrazor form fields.
   *
   * These options are used to select existing terms even if the used
   * field config to not set access to the related entities.
   *
   * @param array $form
   *   Array containing the form to get the options for.
   *
   * @return array
   */
  public function getMergedOptionList(array $form): array;

  /**
   * Removes the TextRazor fields of the given bundle.
   *
   * @param string $bundle_id
   *   The bundle ID to remove the fields from.
   */
  public function removeTextrazorFields($bundle_id);

  /**
   * Appends the TextRazor fields of the given bundle and set up the form display.
   *
   * @param string $bundle_id
   *   The bundle ID to remove the fields from.
   */
  public function appendTextrazorFields($bundle_id);

  /**
   * Get Textrazor classification and store it to node's fields.
   *
   * @param EntityInterface $node
   *   The node entity.
   *
   * @return EntityInterface
   *   The node with the fields filled up.
   */
  public function classifyNode(EntityInterface $node);

  /**
   * Get the text to use for classify the node.
   *
   * Collects values from teaser_text and text-like paragraphs.
   *
   * @param EntityInterface $node
   *   The node entity to get the text from.
   *
   * @return string
   *   The text to use for classify the node.
   */
  public function getTextToClassify(EntityInterface $node);

  /**
   * Performs request to Textrazor service.
   *
   * @param string $text
   *   The text to classify in Texrazor
   *
   * @return array
   *   Terms and categories classifying the node.
   */
  public function getTextrazorResponse(string $text);

  /**
   * Get translations of all terms.
   *
   * Uses already existing data in the response, Wikipedia and Wikidata API to
   * collect translations.
   * The translations are collected sequentially to keep the Wikipedia
   * translations precedence and to reduce at maximum requests to Wikidata as
   * long is slower.
   *
   * @param array $response
   *   The textrazor response.
   * @param string $langcode
   *   The language 2 characters code to look for translations.
   *
   * @return array
   *   The array with the translated terms.
   */
  public function getTranslatedLabels(array $response, string $langcode);

  /**
   * Get translated term labels classified by field.
   *
   * Converts the data from Textrazor response format to an array ready
   * to import to the node. Return format looks like:
   * ```
   * $terms = [
   *  'field_1' => [
   *     'term_1',
   *     'term_2',
   *     ...,
   *   ],
   *   'field_2' => [...]
   * ];
   * ```
   *
   * @param array $response
   *   The Textrazor response.
   *
   * @return array
   *   Translated and classified terms labels.
   */
  public function prepareTerms($response);

}
