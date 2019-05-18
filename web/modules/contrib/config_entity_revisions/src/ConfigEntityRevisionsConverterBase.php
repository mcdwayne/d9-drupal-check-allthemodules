<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting entity IDs to full objects.
 *
 * This is useful in cases where the dynamic elements of the path can't be
 * auto-determined; for example, if your path refers to multiple of the same
 * type of entity ("example/{node1}/foo/{node2}") or if the path can act on any
 * entity type ("example/{entity_type}/{entity}/foo").
 *
 * In order to use it you should specify some additional options in your route:
 *
 * @code
 * example.route:
 *   path: foo/{example}
 *   options:
 *     parameters:
 *       example:
 *         type: entity:node
 * @endcode
 *
 * If you want to have the entity type itself dynamic in the url you can
 * specify it like the following:
 * @code
 * example.route:
 *   path: foo/{entity_type}/{example}
 *   options:
 *     parameters:
 *       example:
 *         type: entity:{entity_type}
 * @endcode
 *
 * If your route needs to support pending revisions, you can specify the
 * "load_latest_revision" parameter. This will ensure that the latest revision
 * is returned, even if it is not the default one:
 * @code
 * example.route:
 *   path: foo/{example}
 *   options:
 *     parameters:
 *       example:
 *         type: entity:node
 *         load_latest_revision: TRUE
 * @endcode
 *
 * When dealing with translatable entities, the "load_latest_revision" flag will
 * make this converter load the latest revision affecting the translation
 * matching the content language for the current request. If none can be found
 * it will fall back to the latest revision. For instance, if an entity has an
 * English default revision (revision 1) and an Italian pending revision
 * (revision 2), "/foo/1" will return the former, while "/it/foo/1" will return
 * the latter.
 *
 * @see entities_revisions_translations
 */
abstract class ConfigEntityRevisionsConverterBase extends EntityConverter implements ConfigEntityRevisionsConverterBaseInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    $storage = $this->entityManager->getStorage($entity_type_id);

    /* @var $entity \Drupal\config_entity_revisions\ConfigEntityRevisionsInterface */
    $entity = $storage->load($value);

    if (!$entity) {
      return NULL;
    }

    // Get the config_entity_revisions entity, if one exists.
    $revisionsID = $entity->getContentEntityID();

    if (!$revisionsID) {
      return $entity;
    }

    /* @var $storage ConfigEntityRevisionsStorageInterface */
    $storage = $this->entityManager->getStorage('config_entity_revisions');

    // If a specific revision is provided or implied by a submission ID, use it.
    $specific_revision = FALSE;

    if (!empty($defaults['revision_id'])) {
      $specific_revision = $defaults['revision_id'];
    }
    elseif ($entity->has_own_content() && !empty($defaults[$entity->revisions_entity_name()])) {
      // Load the content entity and get the config entity revision from it (if any).
      $content_storage = $this->entityManager->getStorage($entity->revisions_entity_name());
      $content = $content_storage->load($defaults[$entity->content_parameter_name()]);
      if ($content->{$entity->content_parent_reference_field()}) {
        $specific_revision = $content->{$entity->content_parent_reference_field()}->target_id;
      }
    }

    // If no specific revision has been given, check to see whether we should
    // use the latest revision or the latest published revision.

    /* @var $revisionsEntity ConfigEntityRevisionsEntityInterface */
    $revisionsEntity = NULL;

    if ($specific_revision) {
      $revisionsEntity = $storage->loadRevision($specific_revision);
    }
    elseif (array_key_exists('load_latest_revision', $definition)) {
      $revisionsEntity = $storage->getLatestRevision($revisionsID);
    }
    else {
      $revisionsEntity = $storage->getLatestPublishedRevision($revisionsID);

      // If there's no latest published revision and the user has admin
      // permissions, get the latest revision instead.
      if (\Drupal::currentUser()->hasPermission($entity->admin_permission()) &&
        is_null($revisionsEntity)) {
        $revisionsEntity = $storage->getLatestRevision($revisionsID);
      }
    }

    if (is_null($revisionsEntity)) {
      return NULL;
    }

    $entity = \Drupal::getContainer()->get('serializer')->deserialize(
      $revisionsEntity->get('configuration')->value,
      get_class($entity),
      'json');

    // The result of serialising and then deserialising is not an exact
    // copy of the original. This causes problems downstream if we don't fix
    // a few attributes here.
    $entity->set('settingsOriginal', $entity->get('settings'));
    $entity->set('enforceIsNew', FALSE);

    // Record the revision ID in the config entity so we can quickly and
    // easily access the revision record if needed (eg for edit form revision
    // message).
    $entity->updateLoadedRevisionId($revisionsEntity->getRevisionId());

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && strpos($definition['type'], 'entity:') === 0) {
      $entity_type_id = substr($definition['type'], strlen('entity:'));
      if (strpos($definition['type'], '{') !== FALSE) {
        $entity_type_slug = substr($entity_type_id, 1, -1);
        return $name != $entity_type_slug && in_array($entity_type_slug, $route->compile()
            ->getVariables(), TRUE);
      }
      if ($entity_type_id == $this->config_entity_name()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Determines the entity type ID given a route definition and route defaults.
   *
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return string
   *   The entity type ID.
   *
   * @throws ParamNotConvertedException
   *   Thrown when the dynamic entity type is not found in the route defaults.
   */
  public function getEntityTypeFromDefaults($definition, $name, array $defaults) {
    $entity_type_id = substr($definition['type'], strlen('entity:'));

    // If the entity type is dynamic, it will be pulled from the route defaults.
    if (strpos($entity_type_id, '{') === 0) {
      $entity_type_slug = substr($entity_type_id, 1, -1);
      if (!isset($defaults[$entity_type_slug])) {
        throw new ParamNotConvertedException(sprintf('The "%s" parameter was not converted because the "%s" parameter is missing', $name, $entity_type_slug));
      }
      $entity_type_id = $defaults[$entity_type_slug];
    }
    return $entity_type_id;
  }

}
