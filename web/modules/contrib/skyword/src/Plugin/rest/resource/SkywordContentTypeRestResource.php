<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\skyword\SkywordResourceBase;
use Drupal\Component\Serialization\Json;
use Drupal\skyword\SkywordContentTypeTools;

/**
 * Provides a resource to get a list of content type definitions.
 *
 * @RestResource(
 *   id = "skyword_content_type_rest_resource",
 *   label = @Translation("Skyword content type rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/content-types",
 *     "https://www.drupal.org/link-relations/create" = "/skyword/v1/content-types"
 *   }
 * )
 */
class SkywordContentTypeRestResource extends SkywordResourceBase {

    /**
     * Responds to GET requests
     *
     * Returns a list of content types
     */
    public function get() {
        $types = SkywordContentTypeTools::getTypes(NULL, $this->response);

        return $this->response->setContent(Json::encode($types));
    }

}
