<?php

namespace Drupal\private_taxonomy\Controller;

use Drupal\Component\Utility\Tags;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Html;

/**
 * Returns autocomplete responses for taxonomy terms.
 */
class TermAutocompleteController implements ContainerInjectionInterface {

  /**
   * Taxonomy term entity query interface.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $termEntityQuery;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs \Drupal\taxonomy\Controller\TermAutocompleteController object.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $term_entity_query
   *   The entity query service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(QueryInterface $term_entity_query, EntityManagerInterface $entity_manager) {
    $this->termEntityQuery = $term_entity_query;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')->get('taxonomy_term'),
      $container->get('entity.manager')
    );
  }

  /**
   * Retrieves suggestions for private taxonomy term autocompletion.
   *
   * This function outputs term name suggestions in response to Ajax requests
   * made by the taxonomy autocomplete widget for taxonomy term reference
   * fields. The output is a JSON object of plain-text term suggestions, keyed
   * by the user-entered value with the completed term name appended.
   * Term names containing commas are wrapped in quotes.
   *
   * For example, suppose the user has entered the string 'red fish, blue' in
   * the field, and there are two taxonomy terms, 'blue fish' and 'blue moon'.
   * The JSON output would have the following structure:
   * @code
   *   {
   *     "red fish, blue fish": "blue fish",
   *     "red fish, blue moon": "blue moon",
   *   };
   * @endcode
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $entity_type
   *   The entity_type.
   * @param string $field_name
   *   The name of the term reference field.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
   *   When valid field name is specified, a JSON response containing the
   *   autocomplete suggestions for taxonomy terms. Otherwise a normal response
   *   containing an error message.
   */
  public function autocomplete(Request $request, $entity_type, $field_name) {
    // A comma-separated list of term names entered in the autocomplete form
    // element. Only the last term is used for autocompletion.
    $tags_typed = $request->query->get('q');

    // Make sure the field exists and is a taxonomy field.
    $field_storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type);

    if (!isset($field_storage_definitions[$field_name]) || $field_storage_definitions[$field_name]->getType() !== 'private_taxonomy_term_reference') {
      // Error string. The JavaScript handler will realize this is not JSON and
      // will display it as debugging information.
      return new Response(t('Private taxonomy field @field_name not found.', ['@field_name' => $field_name]), 403);
    }
    $field_storage = $field_storage_definitions[$field_name];

    // The user enters a comma-separated list of tags. We only autocomplete the
    // last tag.
    $tags_typed = Tags::explode($tags_typed);
    $tag_last = mb_strtolower(array_pop($tags_typed));

    $matches = [];
    if ($tag_last != '') {

      // Part of the criteria for the query come from the field's own settings.
      $vids = [];
      $allowed_values = $field_storage->getSetting('allowed_values');
      foreach ($allowed_values as $tree) {
        $vids[] = $tree['vocabulary'];
      }

      $matches = $this->getMatchingTerms($tags_typed, $vids, $tag_last,
        $allowed_values);
    }

    return new JsonResponse($matches);
  }

  /**
   * Gets terms which matches some typed terms.
   *
   * @param string $tags_typed
   *   The full typed tags string.
   * @param array $vids
   *   An array of vocabulary IDs.
   * @param string $tag_last
   *   The lasted typed tag.
   * @param array $allowed_values
   *   Allowed values from settings.
   *
   * @return array
   *   Returns an array of matching terms.
   */
  protected function getMatchingTerms($tags_typed,
    array $vids,
    $tag_last,
    array $allowed_values) {

    $tags_return = [];
    $query = \Drupal::database()->select('taxonomy_term_field_data', 't');
    $query->addTag('term_access');

    // Do not select already entered terms.
    if (!empty($tags_typed)) {
      $query->condition('t.name', $tags_typed, 'NOT IN');
    }
    // For some reason this fails so do it the hard way.
    $query->join('user_term', 'ut', 't.tid = ut.tid');
    $query->fields('t', ['tid', 'name', 'vid'])
      ->condition('t.name', '%' . $query->escapeLike($tag_last) . '%', 'LIKE')
      ->range(0, 10);
    $users = $allowed_values[0]['users'];
    switch ($users) {
      case 'all':
        $query->join('users_field_data', 'u', 'ut.uid = u.uid');
        $results = $query
          ->fields('u', ['name'])
          ->execute();
        foreach ($results as $option) {
          if (in_array($option->vid, $vids)) {
            $tags_return[$option->tid] = $option->name .
              ' (' . $option->u_name . ')';
          }
        }
        break;

      case 'owner':
        // Owner's terms.
        $user = \Drupal::currentUser();
        $query->join('users_field_data', 'u', 'ut.uid = u.uid');
        $results = $query->condition('ut.uid', $user->id())
          ->fields('u', ['name'])
          ->execute();
        foreach ($results as $option) {
          if (in_array($option->vid, $vids)) {
            $tags_return[$option->tid] = $option->name .
              ' (' . $option->u_name . ')';
          }
        }
        break;

      default:
        // This is a role.
        $user = \Drupal::currentUser();
        $sql = \Drupal::database()->select('users_data', 'u');
        $sql->join('user__roles', 'ur', 'ur.entity_id = u.uid');
        $role = substr($users, 1, strlen($users) - 2);
        $uids = $sql->condition('ur.roles_target_id', $role)
          ->fields('u', ['uid'])
          ->execute()
          ->fetchCol();
        if (!in_array($user->id(), $users)) {
          $uids[] = $user->id();
        }
        $query->join('users_field_data', 'u', 'ut.uid = u.uid');
        $results = $query->condition('ut.uid', $uids, 'IN')
          ->fields('u', ['name'])
          ->execute();
        foreach ($results as $option) {
          if (in_array($option->vid, $vids)) {
            $tags_return[$option->tid] = $option->name .
              ' (' . $option->u_name . ')';
          }
        }

        break;

    }

    $matches = [];
    $prefix = count($tags_typed) ? Tags::implode($tags_typed) . ', ' : '';
    foreach ($tags_return as $tid => $name) {
      $matches[] = [
        'value' => $prefix . Tags::encode($name),
        'label' => Html::escape($name),
      ];
    }
    return $matches;
  }

}
