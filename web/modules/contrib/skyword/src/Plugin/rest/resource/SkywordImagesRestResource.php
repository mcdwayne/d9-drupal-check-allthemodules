<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\skyword\SkywordResourceBase;
use Drupal\skyword\SkywordCommonTools;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "skyword_images_rest_resource",
 *   label = @Translation("Skyword images rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/images",
 *     "https://www.drupal.org/link-relations/create" = "/skyword/v1/images"
 *   }
 * )
 */
class SkywordImagesRestResource extends SkywordResourceBase {

    /**
     * Temporary holder of our query
     *
     * @var \Drupal\core\Entity\Query\QueryInterface
     */
    protected $query;

    /**
     * Responds to GET requests
     *
     * @return \Drupal\Rest\ResourceResponse
     *   Array of all images and their associated metadata
     */
    public function get() {
        $data = [];

        $this->query = \Drupal::entityQuery('file')
            ->condition('filemime', 'image/%', 'LIKE');

        SkywordCommonTools::pager($this->response, $this->query);

        $files = $this->query->execute();

        $entities = \Drupal::service('entity_type.manager')
            ->getStorage('file')
            ->loadMultiple($files);

        /** @var \Drupal\file\Entity\File $file_entity */
        foreach ($entities as $file_entity) {
            $id = $file_entity->id();
            $type = $file_entity->getMimeType();
            $url = file_create_url($file_entity->getFileUri());

            $connection = \Drupal::database();
            $query = $connection->query('SELECT alt, title FROM skyword_media WHERE skyword_media.file_ref = :id', [ ':id' => $id]);
            $metadata = $query->fetchAssoc();

            $data[] = [
                'id' => $id,
                'type' => $type,
                'url' => $url,
                'metadata' => [
                    'alt' => $metadata['alt'],
                    'title' => $metadata['title'],
                ],
            ];
        }

        return $this->response->setContent(Json::encode($data));
    }

    /**
     * Responds to POST requests
     *
     * Creates a File Entity based on the POST Request Payload
     *
     * @return \Drupal\Rest\ResourceResponse
     *   Code 201
     *   The ID and URL for the created Post Node
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected
     */
    public function post($data) {
        $filename = $this->getFilenameFromHeaders();

        $this->validatePostData($data, $filename);
        $id = null;
        $type = $data['type'];

        try {
            $filepath = 'skyword';

            $uri = $this->createDestination($filename, $filepath);

            if ($file = file_save_data($this->extractFileData($data), $uri)) {
                \Drupal::service('file.usage')->add($file, 'skyword', 'files', $file->id());
                $url = file_create_url($file->getFileUri());
                $id = $file->id();
                $type = $file->getMimeType();
            }
            else {
                throw new HttpException('500', 'An error occurred when saving the file.');
            }

            $this->response->headers->set('Link', $url);
            $this->response->setStatusCode(201);
            $response = array();
            $response['id'] = $id;
            $response['url'] = $url;
            $response['type'] = $type;
            $this->response->setContent(Json::encode($response));
            return $this->response;
        }
        catch (HttpException $e) {
            throw new UnprocessableEntityHttpException('An error occurred when saving the file.');
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Helper function to extract filename from headers
     *
     * @return string|null
     *   The name of the file as extracted from the request
     */
    protected function getFilenameFromHeaders() {
        /** @var \Symfony\Component\HttpFoundation\Request $request */
        global $request;

        $content_disposition = $request->headers->get('Content-Disposition');

        if (!empty($content_disposition)) {
            preg_match('/filename\=\"(.*)\"/', $content_disposition, $matches);
            $filename = $matches[1];
            return $filename;
        }
        return NULL;
    }

    /**
     * Validate the post request data if it has the minimal required fields
     *
     * @param array $data
     *   The post request payload submitted to the API
     *
     * @param string $filename
     *   Null, or the extracted filename from the Content-Disposition header
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected via calls to helper functions
     */
    protected function validatePostData(array $data, $filename) {

        if (!isset($data['file'])) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Missing file.');
        }

        if (!$this->validateFileData($data['file'])) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. File could not be decoded.');
        }

        if (empty($filename)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Missing filename in Content-Disposition header.');
        }
    }

    /**
     * Helper function to validate a base_64'ed file that was POSTed
     *
     * @param $data string
     *   The POST data for the request
     *
     * @return bool
     *   Success if the posted value is able to be decoded
     */
    protected function validateFileData($data) {
        $result = base64_decode($data);

        if ($result === FALSE) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Helper method to create the destination folders
     *
     * @param string $filename
     *   The filename to save
     *
     * @param string $filepath
     *  The path to the file we are wanting to save
     *
     * @return string|bool
     */
    protected function createDestination($filename, $filepath = NULL) {
        $path = FALSE;

        if (!empty($filepath)) {
            // @todo: Make the scheme configurable.
            $filepath = file_default_scheme() . '://' . $filepath;

            \Drupal::service('file_system')->mkdir($filepath, NULL, TRUE, NULL);

            if (file_prepare_directory($filepath)) {
                $path = $filepath . '/' . $filename;
            }

        } else {
            $path = file_default_scheme() . '://' . $filename;
        }

        return $path;
    }

    /**
     * Helper function to decode a base_64'ed file that was POSTed
     *
     * @param $data
     *   The POST data for the request
     *
     * @return bool|string
     *   False if unsuccessful, otherwise a binary string for the file
     */
    protected function extractFileData($data) {
        return base64_decode($data['file']);
    }

}
