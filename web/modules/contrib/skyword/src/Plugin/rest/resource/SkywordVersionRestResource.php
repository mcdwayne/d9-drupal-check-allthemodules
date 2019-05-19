<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\skyword\SkywordResourceBase;
use Drupal\Component\Serialization\Json;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "skyword_version_rest_resource",
 *   label = @Translation("Skyword version rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/version"
 *   }
 * )
 */
class SkywordVersionRestResource extends SkywordResourceBase {
    /**
     * Responds to GET requests.
     *
     * @return \Drupal\Rest\ResourceResponse
     *   A list of bundles for specified entity
     */
    public function get() {
        $data = [
            'plugin' => [
                'version' => drupal_get_installed_schema_version('skyword'),
                'language' => [
                    'name' => 'PHP',
                    'version' => phpversion(),
                ],
            ],
            'cms' => [
                'name' => 'Drupal',
                'version' => \Drupal::VERSION,
            ],
        ];

        return $this->response->setContent(Json::encode($data));
    }
}
