<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "skyword_post_rest_resource",
 *   label = @Translation("Skyword post rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/posts/{postId}",
 *   }
 * )
 */
class SkywordPostRestResource extends SkywordPostsRestResource {

    /**
     * Responds to GET requests
     *
     * @param int $postId
     *   The unique identifier of a node
     *
     * @return \Drupal\Rest\ResourceResponse
     *   The body contains a single Node representing the requested Post (as postId)
     */
    public function get($postId) {
        try {
            $posts = $this->buildPosts($postId);
            return $this->response->setContent(Json::encode($posts));
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}
