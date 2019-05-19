<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\user\Entity\User;
use Drupal\skyword\SkywordResourceBase;
use Drupal\skyword\SkywordCommonTools;
use Drupal\skyword\SkywordUserTools;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "skyword_authors_rest_resource",
 *   label = @Translation("Skyword authors rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/authors",
 *     "https://www.drupal.org/link-relations/create" = "/skyword/v1/authors"
 *   }
 * )
 */
class SkywordAuthorsRestResource extends SkywordResourceBase {

    /**
     * Temporary holder of our query
     *
     * @var \Drupal\core\Entity\Query\QueryInterface
     */
    private $query;

    /**
     * Responds to POST requests
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected
     */
    public function post($data) {
        $this->validatePostData($data);

        try {

            /** @var \Drupal\user\Entity\User $user */
            $user = $this->createNewUser($data);

            $options = ['absolute' => TRUE];
            $urlObj = Url::fromRoute(
                'entity.user.canonical',
                ['user' => $user->id()],
                $options
            );

            // A toString(FALSE) results in a LogicException with leaking metadata.
            $url = $urlObj->toString(TRUE)->getGeneratedUrl();
            $url = str_replace('/user/', '/authors/', $url);

            $this->response->headers->set('Link', $url);
            $this->response->setStatusCode(201);
            $resultSuccess = [
                'id' => $user->id(),
                'firstName' => $data["firstName"],
                'lastName' => $data["lastName"],
                'email' => $data["email"],
                'byline' => $data["firstName"] . " " . $data["lastName"],
            ];
            $this->response->setContent(Json::encode($resultSuccess));
            return $this->response;
        }
        catch (EntityStorageException $e) {
            $message = $e->getMessage();
            if(strpos($message, 'Duplicate entry') !== FALSE) {
                $data = (object) [
                    'description' => 'Not Found',
                    'message' => 'Author already exists'
                ];

                return $this->response->setStatusCode(422)
                    ->setContent(Json::encode($data));
            }

            throw $e;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Validate the post request data if it has the minimal required fields
     *
     * @param array $data
     *   The post request payload submitted to the API
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected via calls to helper functions
     */
    protected function validatePostData(array $data) {
        if (!isset($data['firstName'])) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Missing firstName.');
        }

        if (!isset($data['lastName'])) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Missing lastName.');
        }

        if (!isset($data['email'])) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Missing email.');
        }
    }

    /**
     * Save our new user object
     *
     * @param array $data
     *   The post data from the request
     *
     * @return \Drupal\User\Entity\User
     *   The newly created user entity
     *
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    private function createNewUser(array $data) {
        $userName = str_replace(' ', '', strtolower($data['byline']));//strtolower($data['firstName']) . strtolower($data['lastName']));

        $user = User::create([
            'name' => $userName,
            'pass' => $this->createRandomString(),
            'mail' => $data['email'],
            'status' => 1,
        ]);

        $roles =  \Drupal\user\Entity\Role::loadMultiple();
        foreach ($roles as $role => $rolesObj) {
            if($rolesObj->get('label') == "Skyword Author") {
                $user->addRole($rolesObj->get('id'));
            }
        }

        $user->save();
        return $user;
    }

    /**
     * Generate a random string using best available RNG
     *
     * @param int $length
     *   How long of a string should we have?
     *
     * @return string
     *   This is the string you are looking for (waves hand)
     */
    protected function createRandomString($length = 10) {
        $our_string = '';
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max_int = mb_strlen($keyspace, '8bit') - 1;

        for ($i = 0; $i < $length; $i++) {
            if (function_exists('random_int')) {
                $our_string .= $keyspace[random_int(0, $max_int)];
            } else {
                $our_string .= $keyspace[rand(0, $max_int)];
            }
        }

        return $our_string;
    }

    /**
     * Responds to GET requests
     *
     * Returns a list of users/authors
     */
    public function get() {
        $data = [];

        $our_roles = SkywordUserTools::getAuthorRoles();

        $this->query = \Drupal::entityQuery('user')
            ->condition('roles', $our_roles, 'in');

        SkywordCommonTools::pager($this->response, $this->query);

        $results = $this->query->execute();
        $entities = User::loadMultiple($results);

        /** @var \Drupal\user\Entity\user $user */
        foreach ($entities as $user) {

            if ($user->id() != 0) {
                $data[] = [
                    'id' => $user->id(),
                    'email' => $user->getEmail(),
                    'firstName' => '',
                    'lastName' => '',
                    'byline' => SkywordUserTools::getByline($user),
                ];
            }
        }

        return $this->response->setContent(Json::encode($data));
    }

}