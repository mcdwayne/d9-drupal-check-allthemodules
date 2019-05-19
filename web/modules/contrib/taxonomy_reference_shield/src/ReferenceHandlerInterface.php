<?php

namespace Drupal\taxonomy_reference_shield;

use Drupal\taxonomy\TermInterface;

/**
 * Provides an interface for reference handlers.
 */
interface ReferenceHandlerInterface {

  /**
   * Retrieves a list of entities that reference the supplied term.
   *
   * @param \Drupal\taxonomy\Entity\TermInterface $term
   *   The taxonomy term to query for.
   * @param bool $faster
   *   Specify whether fast mode should be used.
   *
   * @return array|false
   *   A multidimensional array with keys corresponding to machine
   *   names for existing Drupal entity types.
   *
   *   An entity type has two fields: 'label' and 'bundles'. The former
   *   returns the translated label and the later, another
   *   multidimensional array with the keys corresponding to machine
   *   names for existing Drupal bundles.
   *
   *   A bundle has two fields: 'label' and 'entities'. The former
   *   returns the translated label and the later, another
   *   multidimensional array with the keys corresponding to ids for
   *   existing drupal entities.
   *
   *   An entity has two fields: 'label' and 'fields'. The former
   *   returns the translated label and the later, an
   *   array whose keys correspond to field machine names and whose
   *   values correspond to field labels.
   *
   *   In case there are no references found, the method will
   *   return FALSE.
   *
   *   The following code snippet depicts what the array structure
   *   looks like.
   *
   * @code
   *     array(
   *       'node' => array(
   *         'label' => 'Node',
   *         'bundles' => array(
   *           'article' => array(
   *             'label' > 'Article',
   *             'entities' => array(
   *               12 => array(
   *                 'label' => 'Article name',
   *                 'fields' => array(
   *                   'field_tags' => array(
   *                     'label' => 'Tags',
   *                   ),
   *                 ),
   *               ),
   *             ),
   *           ),
   *         ),
   *       ),
   *     );
   * @endcode
   *
   *   The $faster flag will enable this method to return TRUE
   *   if the term is being used by at least another entity,
   *   and will return FALSE otherwise.
   */
  public function getReferences(TermInterface $term, $faster = FALSE);

}
