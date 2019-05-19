<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\skyword\SkywordCommonTools;
use Drupal\skyword\SkywordResourceBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides a resource to get an individual taxonomy vocabulary.
 *
 * @RestResource(
 *   id = "skyword_taxonomy_rest_resource",
 *   label = @Translation("Skyword taxonomy rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/taxonomies/{taxonomy}"
 *   }
 * )
 */
class SkywordTaxonomyRestResource extends SkywordResourceBase {
    /**
     * Responds to GET requests
     *
     * @param string $id
     *   The unique identifier of the Vocabulary
     *
     * @return \Drupal\rest\ResourceResponse
     *   Code 404 if the requested Taxonomy doesn't exist
     */
    public function get($id) {
        $query = \Drupal::entityQuery('taxonomy_vocabulary')
            ->condition('vid', $id);

        SkywordCommonTools::pager($this->response, $query);

        $taxonomyIds = $query->execute();

        $taxonomyId = reset($taxonomyIds);

        if (empty($taxonomyId)) {
            $data = (object) [
                'message'     => 'Not Found',
                'description' => "Taxonomy $id not found",
            ];

            return $this->response->setStatusCode(404)
                ->setContent(Json::encode($data));
        }

        /* @var \Drupal\taxonomy\Entity\Vocabulary $taxonomy */
        $taxonomy = Vocabulary::load($taxonomyId);

        $id = $taxonomy->get('vid');

        $data = (object) [
            'id'          => $id,
            'name'        => $taxonomy->get('name'),
            'description' => $taxonomy->get('description'),
            'numTerms'    => $this->getTaxonomyTermsCount($id),
        ];

        return $this->response->setContent(Json::encode($data));
    }

    /**
     * Get the number of Taxonomy Terms via Taxonomy ID
     *
     * @param int $id
     *   The unique identifier of the Taxonomy
     *
     * @return int
     *   The number of terms
     */
    private function getTaxonomyTermsCount($id) {
        $query = \Drupal::entityQuery('taxonomy_term');
        $query->condition('vid', $id);
        $count = $query->count()->execute();

        return intval($count);
    }
}
