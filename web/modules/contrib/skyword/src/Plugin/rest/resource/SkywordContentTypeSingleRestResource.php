<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\skyword\SkywordResourceBase;
use Drupal\Component\Serialization\Json;
use Drupal\skyword\SkywordContentTypeTools;

/**
 * Provides a resource to get a single content type definition.
 *
 * @RestResource(
 *   id = "skyword_content_type_single_rest_resource",
 *   label = @Translation("Skyword content type single rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/content-types/{contentTypeId}",
 *   }
 * )
 */
class SkywordContentTypeSingleRestResource extends SkywordResourceBase {

    /**
     * Responds to GET requests
     *
     * Returns a single content types
     */
    public function get($contentTypeId) {
        $type = SkywordContentTypeTools::getTypes($contentTypeId);

        return $this->response->setContent(Json::encode($type[0]));
    }
}
