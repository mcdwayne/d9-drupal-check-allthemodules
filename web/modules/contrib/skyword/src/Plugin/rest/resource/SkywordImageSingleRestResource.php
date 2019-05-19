<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\skyword\SkywordResourceBase;
use Drupal\Component\Serialization\Json;
use Drupal\file\Entity\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "skyword_image_rest_resource",
 *   label = @Translation("Skyword image single rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/images/{imageId}"
 *   }
 * )
 */
class SkywordImageSingleRestResource extends SkywordResourceBase {

    /**
     * Temporary holder of our query
     *
     * @var \Drupal\core\Entity\Query\QueryInterface
     */
    protected $query;

    /**
     * Responds to GET requests
     *
     * Returns a list of Media Entity
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected
     */
    public function get($imageId) {
        $file_entity = File::load($imageId);

        if (empty($file_entity)) {
            throw new NotFoundHttpException('File not found');
        }

        $id = $file_entity->id();
        $type = $file_entity->getMimeType();
        $url = file_create_url($file_entity->getFileUri());

        $connection = \Drupal::database();
        $query = $connection->query('SELECT alt, title FROM skyword_media WHERE skyword_media.file_ref = :id', [ ':id' => $id]);
        $metadata = $query->fetchAssoc();

        $response = [
            'id' => $id,
            'type' => $type,
            'url' => $url,
            'metadata' => [
                'alt' => $metadata['alt'],
                'title' => $metadata['title'],
            ],
        ];

        return $this->response->setContent(Json::encode($response));
    }

}
