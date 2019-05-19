<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\skyword\SkywordResourceBase;
use Drupal\skyword\SkywordCommonTools;
use Drupal\Component\Serialization\Json;

/**
 * Provides a resource to get taxonomy vocabularies
 *
 * @RestResource(
 *   id = "skyword_taxonomies_rest_resource",
 *   label = @Translation("Skyword taxonomies rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/taxonomies",
 *     "https://www.drupal.org/link-relations/create" = "/skyword/v1/taxonomies"
 *   }
 * )
 */
class SkywordTaxonomiesRestResource extends SkywordResourceBase {
    /**
     * Temporary holder of our query
     *
     * @var \Drupal\core\Entity\Query\QueryInterface
     */
    private $query;

    /**
     * Responds to GET requests
     *
     * @return \Drupal\rest\ResourceResponse
     */
    public function get() {
        $data = [];

        $this->query = \Drupal::entityQuery('taxonomy_vocabulary');

        SkywordCommonTools::pager($this->response, $this->query);

        $taxonomyIds = $this->query->execute();

        $taxonomies = \Drupal::service('entity_type.manager')
            ->getStorage('taxonomy_vocabulary')
            ->loadMultiple($taxonomyIds);

        /** @var \Drupal\taxonomy\Entity\Vocabulary $entity */
        foreach ($taxonomies as $entity) {
            $id = $entity->id();

            $data[] = [
                'id' => $id,
                'name' => $entity->get('name'),
                'description' => $entity->get('description'),
                'numTerms' => $this->getTaxonomyTermsCount($id),
            ];
        }

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
