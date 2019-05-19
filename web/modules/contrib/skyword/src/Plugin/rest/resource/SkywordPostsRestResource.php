<?php

namespace Drupal\skyword\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\skyword\Entity\SkywordMedia;
use Drupal\skyword\Entity\SkywordPost;
use Drupal\skyword\SkywordCommonTools;
use Drupal\skyword\SkywordContentTypeTools;
use Drupal\skyword\SkywordResourceBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "skyword_posts_rest_resource",
 *   label = @Translation("Skyword posts rest resource"),
 *   uri_paths = {
 *     "canonical" = "/skyword/v1/posts",
 *     "https://www.drupal.org/link-relations/create" = "/skyword/v1/posts"
 *   }
 * )
 */
class SkywordPostsRestResource extends SkywordResourceBase {
    /**
     * Temporary holder of our query.
     *
     * @var \Drupal\core\Entity\Query\QueryInterface
     */
    private $query;

    private $trackingTagModel = "<script async='' type='text/javascript' src='//tracking.skyword.com/tracker.js?contentId={id}'></script>";
    private $anonymousTrackingTagModel = "<script async='' type='text/javascript' src='//tracking.skyword.com/tracker.js?contentId={id}&anonymize=yes'></script>";

    private $skyword_content_id = '';
    private $track_tag = '';

    /**
     * Responds to GET requests.
     *
     * @return \Drupal\Rest\ResourceResponse
     *   A list of Posts from the site
     */
    public function get() {
        $posts = $this->buildPosts();
        return $this->response->setContent(Json::encode($posts));
    }

    /**
     * Build the Posts Object.
     *
     * @param $postId int Optional
     *   If not NULL this is the only post we care about
     *
     * @return array or Node
     *   If postId is not NULL we need to return a single Node rather than an array with size 1 to Skyword
     *   otherwise we return an array of all Post Nodes
     */
    protected function buildPosts($postId = NULL) {
        $data = [];

        /** @var \Drupal\core\Entity\Query\QueryInterface query */
        $this->query = \Drupal::entityQuery('node');

        if (NULL != $postId) {
            $this->query->condition('nid', $postId);
        }

        SkywordCommonTools::pager($this->response, $this->query);

        $result = $this->query->execute();

        foreach ($result as $nid) {
            $node = Node::load($nid);

            if (empty($fields_by_type[$node->bundle()])) {
                $fields_by_type[$node->bundle()] = $this->_getTypeFields($node->bundle());

            }

            $id = $node->id();
            $options = ['absolute' => TRUE];
            $urlObj = Url::fromRoute(
                'entity.node.canonical',
                ['node' => $id],
                $options
            );

            // A toString(FALSE) results in a LogicException with leaking metadata.
            $url = $urlObj->toString(TRUE)->getGeneratedUrl();

            // Get the skywordId from the skyword_post table
            $skywordId = \Drupal::database()->select('skyword_post', 'n')
                ->fields('n', array('skywordId'))
                ->where('n.node_ref = :id', array(':id' => $id))
                ->execute();

            $element = [
                'id' => $id,
                'skywordId' => $skywordId->fetchObject()->skywordId,
                'type' => $node->bundle(),
                'title' => $node->getTitle(),
                'url' => $url,
                'created' => $node->getCreatedTime(),
                'author' => $this->buildAuthorData($node),
                'fields' => $this->buildFieldsData($node, $fields_by_type[$node->bundle()]),
            ];

            // The api can't parse the response to a request for a single post if it's in an array
            if (NULL != $postId)
                return $element;

            $data[] = $element;
        }

        return $data;
    }

    /**
     * Helper function that gets node fields by a given bundle
     *
     * @param $bundle string
     *   The node bundle to check
     *
     * @param $filtered bool Optional
     *   Filter out typically internal fields
     *
     * @return array
     *   Array of fields keyed by field name
     */
    protected function _getTypeFields($bundle, $filtered = TRUE) {
        $fields_by_type = [];

        $_fields = SkywordContentTypeTools::getTypeFields('node', $bundle);

        $skips = [
            'langcode',
            'revision_timestamp',
            'revision_uid',
            'revision_log',
            'status',
            'uid',
            'created',
            'changed',
            'promote',
            'sticky',
            'default_langcode',
        ];

        foreach ($_fields as $_field) {
            if ($filtered && in_array($_field['name'], $skips)) {
                continue;
            }

            $fields_by_type[$_field['name']] = $_field;
        }

        return $fields_by_type;
    }

    /**
     * Build the Authors' data
     *
     * @param object $node
     *   The node entity object
     *
     * @return string
     *   The byline for the author (stored in the 'name' column)
     */
    protected function buildAuthorData($node) {
        $byline = '';

        /** @var \Drupal\user\Entity\User $user */
        $user = $node->getOwner();

        if ($user->hasField('name')) {
            $byline = $user->get('name')->value;
        }

        return $byline;
    }

    /**
     * Helper to build the field definitions for the given Node type
     *
     * @param object $node
     *   The node entity
     * @param array $fields_by_type
     *   An array of all non-internal fields for a given content type, keyed by
     *   the field name
     * @param string $field (optional)
     *   Return data for a single field, possibly bypassing some field filters
     *
     * @return array
     *   Array of fields
     */
    protected function buildFieldsData($node, array $fields_by_type, $field = NULL) {
        $element = [];
        foreach ($fields_by_type as $field_name => $field_definition) {
            // @todo: enumerate specific fields to be excluded.
            if (empty($field) and $field_name == '') {
                continue;
            }

            if (empty($field) or $field === $field_name) {
                $val = null;

                // Try to get a field with this name
                $val = $node->get($field_name)->value;

                // Don't bother including a field with a null value
                if (empty($val))
                    continue;

                $record = [
                    'name' => $field_definition['name'],
                    'type' => $field_definition['type'],
                    'value' => $val,
                ];

                $element[] = $record;
            }
        }
        return $element;
    }

    /**
     * Responds to POST requests
     *
     * @return \Drupal\Rest\ResourceResponse
     *   Code 201
     */
    public function post($data) {
        \Drupal::logger("skyword")->notice("POST");
        $this->fieldTypeToLowerCase($data);

        $this->validatePostData($data);

        $this->skyword_content_id = $data['skywordId'];

        if ( $data['trackingTag'] === 'true' )
            $this->track_tag = $this->trackingTagModel;
        else if ( $data['trackingTag'] === 'anonymous' )
            $this->track_tag = $this->anonymousTrackingTagModel;

        $this->track_tag = $this->track_tag === '' ? $this->track_tag : str_replace('{id}', $data['skywordId'], $this->track_tag);

        $node = $this->createNode($data);

        $node->save();
        \Drupal::logger("skyword")->notice("POST - Node Save Success");

        $skyword_post = SkywordPost::create([
            'node_ref' => $node->id(),
            'skywordId' => $data['skywordId'],
            'trackingTag' => $this->track_tag
        ]);

        $skyword_post->save();
        \Drupal::logger("skyword")->notice("POST - Skyword Save Success");

        $options = ['absolute' => TRUE];
        $urlObj = Url::fromRoute(
            'entity.node.canonical',
            ['node' => $node->id()],
            $options
        );
        \Drupal::logger("skyword")->notice("POST - Url Obj Acquired");


        // A toString(FALSE) results in a LogicException with leaking metadata.
        $url = $urlObj->toString(TRUE)->getGeneratedUrl();
        \Drupal::logger("skyword")->notice("POST - Url Generated");


        $this->response->headers->set('Link', $url);
        $this->response->setStatusCode(201);
        $this->response->setContent(Json::encode($data));

        return $this->response;
    }

    /**
     * @param $data
     */
    private function fieldTypeToLowerCase(&$data) {
        $lowerCaseFieldType = function (&$field) {
            $field['type'] = strtolower($field['type']);
        };
        array_walk($data['fields'], $lowerCaseFieldType);
    }

    /**
     * Validate the post request data if it has the minimal required fields
     *
     * @param array $data
     *   The post request data object
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected via calls to helper functions
     */
    protected function validatePostData($data) {
        foreach ($this->_requiredFields() as $field => $type) {
            if (empty($data[$field])) {
                throw new UnprocessableEntityHttpException('A validation error has occurred. Missing field: ' . $field);
            }
            $this->_checkType($data[$field], $type);
        }

        $_node_fields_spec = $this->_getTypeFields($data['type']);

        $_post_fields = $this->addNameKeysToDataFields($data);

        // Title comes in via _required_fields(), not data[fields].
        unset($_node_fields_spec['title']);

        foreach ($_node_fields_spec as $field => $field_spec_data) {

            if (!empty($field_spec_data['required']) && $field_spec_data['required'] && !array_key_exists($field, $_post_fields)) {
                throw new UnprocessableEntityHttpException('A validation error has occurred. Missing field: ' . $field);
            }

            if (!empty($_post_fields[$field]['value'])) {
                $this->_checkType($_post_fields[$field]['value'], $field_spec_data['type']);
            }
        }
    }

    /**
     * Helper function to declare absolutely required fields
     */
    protected function _requiredFields() {
        return [
            'skywordId' => 'int',
            'type' => 'text',
            'title' => 'text',
            'author' => 'user',
            'trackingTag' => 'text',
            'fields' => 'array',
        ];
    }

    /**
     * Helper function to check type of input
     */
    protected function _checkType($input, $type) {
        $types = $this->_getTypeMap();

        if (!array_key_exists($type, $types)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Unsupported type ' . $type);
        }

        if (method_exists($this, '_check_type_' . $types[$type])) {
            $this->{'_check_type_' . $types[$type]}($input);
        } else {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not find validation func for ' . $type);
        }
    }

    /**
     * Helper function to associate types with their correct validate and save
     * methods
     *
     * @return array
     *   What types are supported, and what callback functions to use for them
     */
    protected function _getTypeMap() {
        $types = [
            'int' => 'int',
            'number' => 'number',
            'float' => 'float',
            'boolean' => 'boolean',
            'text' => 'text',
            'html' => 'textarea',
            'meta' => 'text',
            'email' => 'text',
            'link' => 'text',
            'string' => 'text',
            'text_long' => 'textarea',
            'string_long' => 'textarea',
            'textarea' => 'textarea',
            'text_with_summary' => 'textarea',
            'array' => 'array',
            'datetime' => 'datetime',
            'date' => 'datetime',
            'user' => 'user',
            'file' => 'file',
            'image' => 'file',
            'taxonomy' => 'taxonomy',
            'entity_reference' => 'text', //TODO How to handle entity references?
            // 'taxonomy'             => 'entityreference',
            // 'media'             => 'entityreference',
            // 'entityreference'   => 'entityreference',
        ];
        return $types;
    }

    /**
     * @param $data array The post data array
     *
     * @return array
     */
    protected function addNameKeysToDataFields($data) {
        $fields = [];

        foreach ($data['fields'] as $field) {
            $fields[$field['name']] = $field;
        }

        return $fields;
    }

    /**
     * Create the Node
     *
     * @return Node
     *   If a new field needs added it needs setup and then the node needs to be re-created the see the changes
     */
    private function createNode($data) {
        $newFieldsResolved = false;
        $node = null;
        while (!$newFieldsResolved) {
            $node = Node::create([
                'type' => $data['type'],
            ]);

            $node->setTitle($data['title']);
            $node->setOwnerId($data['author']);
            if ( $data['publishAsDraft'] ) {
                $node->setPublished(FALSE);
            }

            $newFieldsResolved = $this->_nodeSetFields($node, $data);
        }
        return $node;
    }

    /**
     * @param $node \Drupal\node\Entity\Node
     * @param $data array
     *
     * @return boolean
     *   true if no new fields were added (the node doesn't need re-created)
     */
    protected function _nodeSetFields(&$node, $data) {
        $_post_fields = $this->addNameKeysToDataFields($data);
        $return = true;
        foreach ($_post_fields as $field_name => $field_spec_data) {
            if (!empty($_post_fields[$field_name]['value'])) {
                if (!$this->_save_type($node, $_post_fields[$field_name], $field_spec_data['type']))
                    $return = false;
            }
        }
        return $return;
    }

    /**
     * @param $node \Drupal\node\Entity\Node
     * @param $input array
     * @param $field_type string
     *
     * @return boolean
     *   true if this field existed already and was filled out (the node doesn't need re-created)
     */
    protected function _save_type(&$node, $input, $field_type) {
        $types = $this->_getTypeMap();

        if (method_exists($this, '_check_type_' . $types[$field_type])) {
            if (!$node->hasField($input['name'])) {
                $storageConfig = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', $input['name']);

                if (empty($storageConfig)) {
                    \Drupal\field\Entity\FieldStorageConfig::create(array(
                        'field_name' => $input['name'],
                        'entity_type' => 'node',
                        'type' => $types[$field_type],
                        'cardinality' => -1,
                    ))->save();
                    \Drupal::logger("skyword")->notice('Created FieldStorageConfig');


                    \Drupal\field\Entity\FieldConfig::create([
                        'field_name' => $input['name'],
                        'entity_type' => 'node',
                        'bundle' => $node->getType(),
                        'label' => $input['name'],
                    ])->save();
                }

                \Drupal::configFactory()->clearStaticCache();
                \Drupal::entityDefinitionUpdateManager()->applyUpdates();

                // This field needed to be added, we need to re-create the node
                return false;
            }
            if ( 'body' === $input['name'] )
                $input['value'] .= $this->track_tag;

            $this->{'_save_type_' . $types[$field_type]}($node, $input);
        }

        // Field filled out successfully
        return true;
    }

    /**
     * Helper function to validate input format
     *
     * @param $input
     */
    protected function _check_type_int($input) {
        if (!is_int($input)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not validate int.');
        }
    }

    /**
     * Helper function to validate input format
     *
     * @param $input
     */
    protected function _check_type_number($input) {
        if (!is_numeric($input)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not validate number.');
        }
    }

    /**
     * Helper function to validate input format
     *
     * @param $input
     */
    protected function _check_type_float($input) {
        if (!is_float($input)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not validate float.');
        }
    }

    /**
     * Helper function to validate input format
     *
     * @param $input
     */
    protected function _check_type_boolean($input) {
        if (!is_bool((bool)$input)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not validate boolean.');
        }
    }

    /**
     * Helper function to validate input format
     *
     * @param $input
     *
     * @param $max_len int optional
     */
    protected function _check_type_text($input, $max_len = 255) {
        if (!is_string($input) || strlen($input) >= $max_len) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not validate text.');
        }
    }

    /**
     * Helper function to validate input format
     *
     * @param $input
     */
    protected function _check_type_textarea($input) {
        if (!is_string($input)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not validate textarea.');
        }
    }

    /**
     * Helper function to validate input format
     *
     * @param $input
     */
    protected function _check_type_taxonomy($input)
    {
        if (!is_string($input)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not validate taxonomy.');
        }
    }

    /**
     * Helper function to validate input format
     *
     * @param $input
     */
    protected function _check_type_datetime($input) {
        // @todo: add more in depth validation for datetime.
        if (!is_string($input)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not validate datetime.');
        }
    }

    /**
     * Helper function to validate input format
     *
     * @param $input
     */
    protected function _check_type_user($input) {
        if (!is_int((int)$input)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not validate user.');
        }

        if (empty(User::load($input))) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not find user.');
        }
    }

    /**
     * Helper function to validate input format
     *
     * @param $input
     */
    protected function _check_type_file($input) {
        if (!is_int((int)$input)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not validate file.');
        }

        if (empty(File::load($input))) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not find file.');
        }
    }

    /**
     * Helper function to validate input format
     *
     * @param $input
     */
    protected function _check_type_array($input) {
        if (!is_array($input)) {
            throw new UnprocessableEntityHttpException('A validation error has occurred. Could not validate array.');
        }
    }

    /**
     * Helper function to save input data to a node
     *
     * @param $node \Drupal\node\Entity\Node
     *
     * @param $input
     */
    protected function _save_type_int(&$node, $input) {
        $node->set($input['name'], $input['value']);
    }

    /**
     * Helper function to save input data to a node
     *
     * @param $node \Drupal\node\Entity\Node
     *
     * @param $input
     */
    protected function _save_type_number(&$node, $input) {
        $node->set($input['name'], $input['value']);
    }

    /**
     * Helper function to save input data to a node
     *
     * @param $node \Drupal\node\Entity\Node
     *
     * @param $input
     */
    protected function _save_type_float(&$node, $input) {
        $node->set($input['name'], $input['value']);
    }

    /**
     * Helper function to save input data to a node
     *
     * @param $node \Drupal\node\Entity\Node
     *
     * @param $input
     */
    protected function _save_type_boolean(&$node, $input) {
        $node->set($input['name'], $input['value']);
    }

    /**
     * Helper function to save input data to a node
     *
     * @param $node \Drupal\node\Entity\Node
     *
     * @param $input
     */
    protected function _save_type_text(&$node, $input) {
        $node->set($input['name'], $input['value']);
    }

    /**
     * Helper function to save input data to a node
     *
     * @param $node \Drupal\node\Entity\Node
     *
     * @param $input
     */
    protected function _save_type_textarea(&$node, $input) {
        // @todo: Should there be an endpoint to get valid input formats?
        $format = 'basic_html';
        if (strtolower($input['type']) == 'html') {
            $format = 'full_html';
        }

        $params = [
            'value' => $input['value'],
            'format' => $format,
        ];

        $node->set($input['name'], $params);
    }

    /**
     * Helper function to save input data to a node
     *
     * @param $node \Drupal\node\Entity\Node
     *
     * @param $input
     */
    protected function _save_type_taxonomy(&$node, $input) {
        $termIds = explode(',', $input['value']);

        $node->set($input['name'], $termIds);
    }

    /**
     * Helper function to save input data to a node
     *
     * @param $node \Drupal\node\Entity\Node
     *
     * @param $input
     */
    protected function _save_type_datetime(&$node, $input) {
        $field_storage_settings = $node->getFieldDefinition($input['name'])
            ->getFieldStorageDefinition()
            ->getSettings();

        $date_storage_format = $field_storage_settings['datetime_type'] == 'date' ?
            \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATE_STORAGE_FORMAT
            : \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT;

        $date_obj = new DrupalDateTime($input['value']);
        $date_obj->setTimezone(timezone_open('UTC'));
        $date = $date_obj->format($date_storage_format);

        $node->set($input['name'], $date);
    }

    /**
     * Helper function to save input data to a node
     *
     * @param $node \Drupal\node\Entity\Node
     *
     * @param $input
     */
    protected function _save_type_user(&$node, $input) {
        $node->set($input['name'], $input['value']);
    }

    /**
     * Helper function to save input data to a node
     *
     * @param $node \Drupal\node\Entity\Node
     *   The node we are using.
     * @param $input
     *   The input file reference to decode and attach
     */
    protected function _save_type_file(&$node, $input) {
        $file_entity = File::load($input['value']);

        if (empty($file_entity)) {
            throw new NotFoundHttpException('File not found - ' . $input);
        }

        $connection = \Drupal::database();
        $query = $connection->query('SELECT alt, title FROM skyword_media WHERE skyword_media.file_ref = :id', [ ':id' => $file_entity->id()]);
        $metadata = $query->fetchAssoc();

        $file_value = [
            'target_id' => $file_entity->id(),
            //'filename' => $file_entity->filename,
            //'filemime' => $file_entity->filemime,
            'alt' => $metadata['alt'],
            'title' => $metadata['title'],
        ];

        \Drupal::service('file.usage')->add($file_entity, 'skyword', $node->getEntityTypeId(), $node->getOwnerId());

        $node->set($input['name'], $file_value);
    }
}
