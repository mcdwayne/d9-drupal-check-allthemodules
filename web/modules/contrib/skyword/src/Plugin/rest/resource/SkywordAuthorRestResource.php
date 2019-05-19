<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\skyword\SkywordResourceBase;
use Drupal\skyword\SkywordUserTools;
use Drupal\user\Entity\User;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "skyword_author_rest_resource",
 *   label = @Translation("Skyword author rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/authors/{authorId}"
 *   }
 * )
 */
class SkywordAuthorRestResource extends SkywordResourceBase {

    /**
     * Temporary holder of our query
     */
    private $query;

    /**
     * Responds to GET requests
     *
     * Returns a specific of user/author
     *
     * @param int $authorId
     *   The unique identifier of the User/Author
     */
    public function get($authorId) {
        try {
            $data = [];

            $our_roles = SkywordUserTools::getAuthorRoles();

            $this->query = \Drupal::entityQuery('user')
                ->condition('roles', $our_roles, 'in')
                ->condition('uid', $authorId);

            $uids = $this->query->execute();

            $uid = reset($uids);

            if(empty($uid)) {
                $data = (object) [
                    'description' => 'Not Found',
                    'message' => "Author $authorId not found"
                ];

                return $this->response->setStatusCode(404)
                    ->setContent(Json::encode($data));
            }

            $user = User::load($uid);

            if ($user->id() !== 0) {
                $data = [
                    'id' => $user->id(),
                    'email' => $user->getEmail(),
                    'firstName' => '',
                    'lastName' => '',
                    'byline' => SkywordUserTools::getByline($user),
                ];
            }

            return $this->response->setContent(Json::encode($data));
        } catch (Exception $e) {
            \Drupal::logger("skyword")->notice('AuthorRestResource Exception');

            return $e->getMessage();
        }
    }
}
