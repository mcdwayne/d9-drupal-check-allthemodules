<?php

namespace Drupal\pagedesigner\Plugin\rest\resource;

use Drupal\Core\Language\LanguageInterface;
use Drupal\node\Entity\Node;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Locking endpoint.
 *
 * Provides methods for locking and unlocking pages.
 *
 * @RestResource(
 *   id = "pagdesigner_lock",
 *   label = @Translation("Pagedesigner locks"),
 *   uri_paths = {
 *      "canonical" = "/pagedesigner/lock/{id}",
 *      "create" = "/pagedesigner/lock"
 *   }
 * )
 */
class LockResource extends ResourceBase
{
    /**
     * Get lock info.
     *
     * Returns info about the lock on a certain node.
     *
     * @param int entity The node id.
     * @return \Drupal\rest\ResourceResponse The responce containing the info.
     */
    public function get($id = null)
    {
        $language = \Drupal::languageManager()->getcurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        $locker = \Drupal::service('pagedesigner.locker');
        $node = Node::load($id);
        if ($node->hasTranslation($language)) {
            $node = $node->getTranslation($language);
            $locker->setEntity($node);

            return new ResourceResponse([$locker->hasLock()]);
        }
        return new ResourceResponse([false]);

    }

    /**
     * Create new lock.
     *
     * Create a new lock on the node.
     *
     * @param int $id The node id.
     * @param array $data Additional data for the lock, e.g. tabId for multitab lock.
     * @return \Drupal\rest\ResourceResponse The response containing either true on success of info about the existing lock.
     */
    public function post($request)
    {
        $language = \Drupal::languageManager()->getcurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        $nodeId = $request['nodeId'];
        $identifier = $request['identifier'];
        $node = Node::load($id);
        if ($node->hasTranslation($language)) {
            $node = $node->getTranslation($language);
            $locker = \Drupal::service('pagedesigner.locker');
            $locker->setEntity($node);
            if ($locker->acquire($identifier)) {
                return new ModifiedResourceResponse([true, $identifier]);
            }
        }
        return new ResourceResponse([false]);
    }

    /**
     * Update a lock.
     *
     * Update a lock on the node.
     *
     * @param int $id The node id.
     * @param array $data Additional data for the lock, e.g. tabId for multitab lock.
     * @return \Drupal\rest\ResourceResponse The response containing either true on success of info about the existing lock.
     */
    public function patch($id, $request)
    {
        $language = \Drupal::languageManager()->getcurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        $identifier = $request['identifier'];
        $node = Node::load($id);
        if ($node->hasTranslation($language)) {
            $node = $node->getTranslation($language);
            $locker = \Drupal::service('pagedesigner.locker');
            $locker->setEntity($node);
            // $locker->setPage($id, $language);
            if ($locker->acquire($identifier)) {
                return new ModifiedResourceResponse([true, $identifier]);
            }
        }
        return new ResourceResponse([false]);
    }

    /**
     * Delete a lock.
     *
     * Delete the lock on the node.
     *
     * @param int $id The node id.
     * @param array $data Additional data for the lock, e.g. tabId for multitab lock.
     * @return \Drupal\rest\ModifiedResourceResponse An empty response.
     */
    public function delete($id, $request)
    {
        $language = \Drupal::languageManager()->getcurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        $node = Node::load($id);
        $identifier = $request['identifier'];
        if ($node->hasTranslation($language)) {
            $node = $node->getTranslation($language);
            $locker = \Drupal::service('pagedesigner.locker');
            $locker->setEntity($node);
            if ($locker->release($identifier)) {
              die();
                return new ModifiedResourceResponse([true, $identifier]);
            }
        }
        return new ResourceResponse([false]);
    }
}
