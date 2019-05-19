<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\skyword\SkywordResourceBase;
use Drupal\file\Entity\File;
use Drupal\Component\Serialization\Json;
use Drupal\skyword\Entity\SkywordMedia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "skyword_image_single_metadata_rest_resource",
 *   label = @Translation("Skyword image single metadata rest resource"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/skyword/v1/images/{imageId}/metadata"
 *   }
 * )
 */
class SkywordImageSingleMetadataRestResource extends SkywordResourceBase {

    /** @inheritdoc */
    public function post($imageId, $data) {
        $file_entity = File::load($imageId);

        if (empty($file_entity)) {
            throw new NotFoundHttpException('File not found');
        }

        $media_metadata = SkywordMedia::create([
            'file_ref' => $imageId,
            'title' => array_key_exists('title', $data) ? $data['title'] : '',
            'alt' => array_key_exists('alt', $data) ? $data['alt'] : '',
        ]);

        $media_metadata->save();

        // @todo: Check for file usage, and mark skyword if it was not already done.
        $url = file_create_url($file_entity->getFileUri());

        $this->response->headers->set('Link', $url);
        $this->response->setStatusCode(201);
        $response = array();
        $response['url'] = $url;
        $response['id'] = $imageId;
        $response['type'] = $file_entity->getMimeType();
        $response['metadata'] = [
            'title' => $data['title'],
            'alt' => $data['alt']
        ];
        $this->response->setContent(Json::encode($response));
        return $this->response;
    }

}
