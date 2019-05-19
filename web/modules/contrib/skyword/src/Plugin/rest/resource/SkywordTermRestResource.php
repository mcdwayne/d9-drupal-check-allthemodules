<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\skyword\SkywordCommonTools;
use Drupal\skyword\SkywordResourceBase;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get all of an individual taxonomy vocabulary terms.
 *
 * @RestResource(
 *   id = "skyword_term_rest_resource",
 *   label = @Translation("Skyword term rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/taxonomies/{taxonomy}/terms",
 *     "https://www.drupal.org/link-relations/create" = "/skyword/v1/taxonomies/{taxonomy}/terms"
 *   }
 * )
 */
class SkywordTermRestResource extends SkywordResourceBase {
    /**
     * Temporary holder of our query
     *
     * @var \Drupal\core\Entity\Query\QueryInterface
     */
    private $query;

    /**
     * Responds to GET requests.
     *
     * @param string $vid
     *   The unique identifier of the Vocabulary
     *
     * @return \Drupal\rest\ResourceResponse
     */
    public function get($vid) {
        $data = [];

        $this->validateVocabulary($vid);

        $this->query = \Drupal::entityQuery('taxonomy_term')->condition('vid', $vid);

        SkywordCommonTools::pager($this->response, $this->query);

        $termIds = $this->query->execute();

        $terms = \Drupal::service('entity_type.manager')
            ->getStorage('taxonomy_term')
            ->loadMultiple($termIds);

        /** @var Term $term */
        foreach ($terms as $term) {
            $data[] = [
                'id' => $term->id(),
                'value' => $term->getName(),
                'parent' => ''
            ];
        }

        return $this->response->setContent(Json::encode($data));
    }

    /**
     * Responds to POST requests
     *
     * @param string $vid
     *   The unique identifier of the Vocabulary
     * @param array|null $data
     *   The post request data object.
     *
     * @return \Drupal\rest\ResourceResponse
     *   Code 201
     */
    public function post($vid, $data) {

        $this->validatePostData($vid, $data);

        $parent = [];

        if (!empty($data['parent'])) {
            $parent = [$data['parent']];
        }

        $term = Term::create([
            'name' => $data['value'],
            'vid' => $vid,
            'parent' => $parent,
        ]);

        $term->save();

        $this->response->setStatusCode(201);
        $response = [
            'id' => $term->id(),
            'value' => $term->getName(),
            'parent' => $parent
        ];

        $aliasManager = \Drupal::service('path.alias_manager');
        // The second argument to getAliasByPath is a language code such as "en" or LanguageInterface::DEFAULT_LANGUAGE.
        $alias = $aliasManager->getAliasByPath('/taxonomy/term/' . $term->id());
        $alias = str_replace('/term/', '/terms/', $alias);

        $this->response->headers->set('Link', $alias);

        return $this->response->setContent(Json::encode($response));
    }

    /**
     * Validate the post request data if it has the minimal required fields
     *
     * @param string $vid
     *   The unique identifier of the Vocabulary
     * @param array $data
     *   The post request data object
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected
     */
    protected function validatePostData($vid, array $data) {
        $validation_error = new UnprocessableEntityHttpException('A validation error has occurred');

        $this->validateVocabulary($vid);

        if (empty($data['value'])) {
            throw $validation_error;
        }

        if (!empty($data['parent'])
            && !$this->validateTerm($vid, $data['parent'])) {
            throw $validation_error;
        }
    }

    /**
     * Helper function to validate a vocabulary by id
     *
     * @param $vid
     *   The vocabulary id to check
     */
    protected function validateVocabulary($vid) {
        $query = \Drupal::entityQuery('taxonomy_vocabulary')
            ->condition('vid', $vid);

        $vocabs = $query->execute();

        $entities = \Drupal::service('entity_type.manager')
            ->getStorage('taxonomy_vocabulary')
            ->loadMultiple($vocabs);

        $entity = reset($entities);

        if (!$entity) {
            throw new NotFoundHttpException('Taxonomy not found');
        }
    }

    /**
     * Check Vocabulary to see if Term already exists
     *
     * @param $vid
     *   The unix identifier of the Vocabulary (vid)
     * @param $tid
     *   The unique identifier of the Term (tid)
     *
     * @return bool
     *   FALSE if none were found
     */
    protected function validateTerm($vid, $tid) {
        $query = \Drupal::entityQuery('taxonomy_term')
            ->condition('vid', $vid)
            ->condition('tid', $tid);

        $number = $query->count()->execute();

        if ($number == 0) {
            return FALSE;
        }
        return TRUE;
    }

}
