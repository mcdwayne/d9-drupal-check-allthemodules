<?php

namespace Drupal\blogapi;

use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Config\ConfigFactory;

/**
 * Class BlogapiCommunicator.
 *
 * This is a class containing almost all the helpers for BlogAPI.
 *
 * @package Drupal\blogapi
 */
class BlogapiCommunicator {

  const BLOGAPI_XML_ERROR_AUTH = 1;
  const BLOGAPI_XML_ERROR_NODE_NOT_FOUND = 2;
  const BLOGAPI_XML_ERROR_NODE_ACCESS = 3;
  const BLOGAPI_XML_ERROR_NODE_UPDATE = 4;
  const BLOGAPI_XML_ERROR_CT = 5;
  const BLOGAPI_XML_ERROR_NODE_CREATE = 6;
  const BLOGAPI_XML_ERROR_NODE_DELETE = 7;
  const BLOGAPI_XML_ERROR_IMG_SIZE = 8;
  const BLOGAPI_XML_ERROR_IMG_SAVE = 9;

  public $entityTypeManager;
  public $entityFieldManager;
  public $pluginManager;
  public $moduleManager;
  public $blogapiConfig;

  /**
   * BlogapiCommunicator constructor.
   */
  public function __construct(EntityTypeManager $entityTypeManager, EntityFieldManager $entityFieldManager, BlogapiProviderManager $blogapiProviderManager, ModuleHandler $moduleHandler, ConfigFactory $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->pluginManager = $blogapiProviderManager;
    $this->moduleManager = $moduleHandler;
    $this->blogapiConfig = $configFactory->get('blogapi.settings');
  }

  /**
   * Returns implemented BlogAPI provider plugins.
   *
   * @return array
   *   Returns an array of available API calls.
   */
  public function getMethodImplementations() {
    $plugins = $this->pluginManager->getDefinitions();
    $implementations = array();
    foreach ($plugins as $plugin) {
      $value = $this->pluginManager->invoke($plugin['provider'], 'xmlrpc');
      if (isset($value[0])) {
        $implementations[] = $value[0];
      }
    }
    return $implementations;
  }

  /**
   * Performs login authentications for a user.
   *
   * @param $user
   *   Drupal username.
   *
   * @param $pass
   *   Drupal password.
   *
   * @param bool $return_object
   *   Boolean var to decide on returning the user object.
   *
   * @return bool|object
   *   Returns the user object or the user ID.
   */
  public function authenticate($user, $pass, $return_object = FALSE) {
    // Login check.
    $auth = \Drupal::service('user.auth');
    if ($auth->authenticate($user, $pass)) {
      // Drupal permission check.
      $user_load = user_load_by_name($user);
      // Possibly return the loaded user object.
      if ($return_object) {
        return $user_load;
      }
      $id = $user_load->id();
      return (int) $id;
    }

    // Return false if authentication fails.
    return FALSE;
  }

  /**
   * Check if the node is manageable with BlogAPI.
   *
   * @param $ct
   *   Content type machine name.
   *
   * @return bool
   *   Returns the validation result.
   */
  public function validateBlogId($ct) {
    $content_types = $this->blogapiConfig->get('content_types');
    if (in_array($ct, $content_types)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns taxonomy terms saved in the defined taxonomy field on a node.
   *
   * @param $nid
   *   The node ID.
   *
   * @param $user
   *   Drupal username.
   *
   * @param $pass
   *   Drupal password.
   *
   * @return array|object
   *   Returns either an error or terms.
   */
  public function getNodeCategories($nid, $user, $pass) {
    // Check user authentication.
    $user = $this->authenticate($user, $pass, TRUE);
    if (!$user) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_AUTH);
    }

    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_NOT_FOUND, $nid);
    }
    if (!$node->access('view', $user)) {
      // User does not have permission to view the node.
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_ACCESS, $nid);
    }

    $ct = $node->getType();
    $taxonomy_fields = $this->blogapiConfig->get('taxonomy_' . $ct);
    $taxonomy_terms = $this->getTaxonomyTerms($node, [$taxonomy_fields]);

    return $taxonomy_terms;
  }

  /**
   * Helper method to find the taxonomy fields in a content type.
   *
   * @param $node
   *   A node object.
   *
   * @return array
   *   Returns the taxonomy fields array.
   */
  public function getTaxonomyFields($node) {
    $taxonomy_fields = [];
    $fields = $node->getFieldDefinitions();
    foreach ($fields as $i => $field) {
      if ($this->fieldIsTaxonomy($field)) {
        $taxonomy_fields[] = $i;
      }
    }
    return $taxonomy_fields;
  }

  /**
   * Check if the passed field is a taxonomy field.
   *
   * @param $field
   *   The field ID.
   *
   * @return bool
   *   Returns the bool result.
   */
  public function fieldIsTaxonomy($field) {
    $type = $field->getType();
    $handler = $field->getSetting('handler');
    if ($handler == 'default:taxonomy_term' && $type == 'entity_reference') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Helper method to get terms from taxonomy fields on a node.
   *
   * @param $node
   *   A node object.
   *
   * @param $taxonomy_fields
   *   An array of taxonomy field IDs.
   *
   * @return array
   *   An array of taxonomy terms.
   */
  public function getTaxonomyTerms($node, $taxonomy_fields) {
    $terms = [];

    if (is_array($taxonomy_fields) && !empty($taxonomy_fields)) {
      foreach ($taxonomy_fields as $field) {
        $field_load = $node->get($field);
        $values = $field_load->getValue();
        if (!empty($values)) {
          foreach ($values as $term) {
            $term_load = Term::load($term['target_id']);
            $terms[] = [
              'categoryName' => $term_load->label(),
              'categoryId' => (int) $term_load->id(),
              'isPrimary' => TRUE,
            ];
          }
        }
      }
    }
    return $terms;
  }

  /**
   * Returns a list of available categories on a content type.
   *
   * @param $ct
   *   Content type machine name.
   *
   * @param $username
   *   Drupal username.
   *
   * @param $pass
   *   Drupal password.
   *
   * @return array|object
   *   Either an error or an array with categories.
   */
  public function getCategoryList($ct, $username, $pass) {
    // Check user authentication.
    if (!$this->authenticate($username, $pass)) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_AUTH);
    }

    // Check if content type is manageable with blogapi.
    if (!$this->validateBlogId($ct)) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_CT);
    }

    $categories = [];
    $vocabularies = $this->getCtVocabularies($ct);

    if (!empty($vocabularies)) {
      foreach ($vocabularies as $vocabulary_machine_name) {
        $vocab_manager = $this->entityTypeManager->getStorage('taxonomy_term');

        // Get all the terms from a vocabulary.
        $terms = $vocab_manager->loadTree($vocabulary_machine_name);

        foreach ($terms as $term) {
          $categories[] = array('categoryName' => $term->name, 'categoryId' => $term->tid);
        }
      }
    }

    return $categories;
  }

  /**
   * Returns all the vocabularies whose terms that can be stored in a CT.
   *
   * @param $ct
   *   Content type machine name.
   *
   * @return array
   *   An array of vocabularies.
   */
  public function getCtVocabularies($ct) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $ct);
    $vocabs = [];
    foreach ($definitions as $field) {
      if ($this->fieldIsTaxonomy($field)) {

        // Get the field handler setting from which
        // we determine supported vocabulary bundles.
        $settings = $field->getSetting('handler_settings');
        if (!empty($settings['target_bundles'])) {
          foreach ($settings['target_bundles'] as $target) {

            // If the vocabulary is not already in the return array.
            if (!in_array($target, $vocabs)) {
              $vocabs[] = $target;
            }
          }
        }
      }
    }
    return $vocabs;
  }

  /**
   * Callback for editing a node.
   *
   * @param $nid
   *   The node ID.
   *
   * @param $username
   *   Drupal username.
   *
   * @param $pass
   *   Drupal password.
   *
   * @param $data
   *   The node contents.
   *
   * @return object|string
   *   A node ID or an error.
   */
  public function editPost($nid, $username, $pass, $data) {
    // Check user authentication.
    $user = $this->authenticate($username, $pass, TRUE);
    if (!$user) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_AUTH);
    }

    // Check is node exists.
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_NOT_FOUND, $nid);
    }

    // Check node access for the loaded user.
    if (!$node->access('update', $user)) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_UPDATE, $nid);
    }
    if (!$this->checkUserNodeAccess($user, $node)) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_UPDATE, $nid);
    }

    $content_type = $node->getType();
    $body_field = $this->blogapiConfig->get('body_' . $content_type);
    $comment_field = $this->blogapiConfig->get('comment_' . $content_type);

    $body = [
      'value' => html_entity_decode($data['body']),
      'format' => $this->getValidTextFormat($data, $user),
    ];

    $comment = [
      'status' => (isset($data['comments']) ? $data['comments'] : $this->getDefaultCommentSetting($content_type)),
    ];

    $node->set($body_field, $body);
    $node->set($comment_field, $comment);
    $node->setTitle($data['title']);
    $node->setPublished($data['publish']);
    $node->save();
    return (string) $node->id();
  }

  /**
   * Try and load a default text format if the passed one doesn't exist.
   *
   * @param $data
   *   The node data.
   *
   * @param $user
   *   The Drupal user object.
   *
   * @return array|bool|mixed|null|string
   *   A valid text format ID.
   */
  public function getValidTextFormat($data, $user){
    $format_load = FALSE;
    if (isset($data['format'])) {
      $format_load = $data['format'];
    }
    if (!$format_load || !array_key_exists($format_load, filter_formats())) {
      $format_load = $this->getDefaultFormat($user);
    }
    return $format_load;
  }

  /**
   * Callback for saving tags on a node.
   *
   * @param $nid
   *   The node ID.
   *
   * @param $username
   *   Drupal username.
   *
   * @param $pass
   *   Drupal password.
   *
   * @param $data
   *   The taxonomy data.
   *
   * @return object|bool
   *   Either error os a success bool.
   */
  public function setPostCategories($nid, $username, $pass, $data) {
    // Check user authentication.
    $user = $this->authenticate($username, $pass, TRUE);
    if (!$user) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_AUTH);
    }

    // Check if the node exists.
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_NOT_FOUND, $nid);
    }

    // Check node access for the loaded user.
    if (!$node->access('update', $user)) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_UPDATE, $nid);
    }

    if (!$this->checkUserNodeAccess($user, $node)) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_UPDATE, $nid);
    }

    $content_type = $node->getType();
    $field_storage = [];
    $taxonomy_fields = $this->getTaxonomyFields($node);

    // Get the primary taxonomy field from the module settings page.
    $taxonomy_primary_field = $this->blogapiConfig->get('taxonomy_' . $content_type);
    $primary_field_bundles = [];
    if (!is_null($taxonomy_primary_field)) {
      $primary_field_bundles = $this->getCtFieldTargetBundles($content_type, $taxonomy_primary_field);
    }

    foreach ($data as $item) {
      $term = Term::load($item['categoryId']);
      $vocab = $term->getVocabularyId();

      // Try to save the taxonomy term in the primary
      // taxonomy field from the settings form.
      if (in_array($vocab, $primary_field_bundles)) {
        $field_storage[$taxonomy_primary_field][] = $term->id();
      }
      // Find the first taxonomy field that accepts the vocabulary of the term.
      else {
        foreach ($taxonomy_fields as $tax_field) {
          $bundles = $this->getCtFieldTargetBundles($content_type, $tax_field);
          if (in_array($vocab, $bundles)) {
            $field_storage[$tax_field][] = $term->id();
            break;
          }
        }
      }
    }
    if (!empty($field_storage)) {
      foreach ($field_storage as $field_id => $tags) {
        $node->set($field_id, $tags);
      }
      $node->save();

    }
    return TRUE;
  }

  /**
   * Returns true if a user is allowed to edit a node with BlogAPI.
   * False otherwise.
   *
   * @param $user
   *   Loaded user object.
   *
   * @param $node
   *   Loaded node object.
   *
   * @return bool
   *   Return the access boolean.
   */
  public function checkUserNodeAccess($user, $node) {
    if ($user->hasPermission('manage any content blogapi')) {
      return TRUE;
    }
    $owner = $node->getOwnerId();
    if ($user->id() === $owner) {
      if ($user->hasPermission('manage own content blogapi')) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Returns all vocabulary ids that can be saved in a field.
   *
   * @param $ct
   *   Content type machine name.
   *
   * @param $field_name
   *   The field name.
   *
   * @return array
   *   Array of strings.
   */
  private function getCtFieldTargetBundles($ct, $field_name) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $ct);
    $field = $definitions[$field_name];
    $settings = $field->getSetting('handler_settings');
    return array_keys($settings['target_bundles']);
  }

  /**
   * Callback for creating a new node.
   *
   * @param $ct
   *   Content type machine name.
   *
   * @param $username
   *   Drupal username.
   *
   * @param $pass
   *   Drupal password.
   *
   * @param $data
   *   The node contents.
   *
   * @return bool|object|string
   *   Either an error or the create node ID.
   */
  public function newPost($ct, $username, $pass, $data) {
    // Check user authentication.
    $user = $this->authenticate($username, $pass, TRUE);
    if (!$user) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_AUTH);
    }

    // Check if content type is manageable with blogapi.
    if (!$this->validateBlogId($ct)) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_CT);
    }
    if (!$user->hasPermission('create ' . $ct . ' content')) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_CREATE);
    }

    $body_field = $this->blogapiConfig->get('body_' . $ct);
    $comment_field = $this->blogapiConfig->get('comment_' . $ct);

    $values = [
      'type' => $ct,
      'title' => $data['title'],
      $body_field => [
        'value' => html_entity_decode($data['body']),
        'format' => $this->getValidTextFormat($data, $user),
      ],
      $comment_field => [
        'status' => (isset($data['comments']) ? $data['comments'] : $this->getDefaultCommentSetting($ct)),
      ],
      'uid' => $user->id(),
    ];

    $node_manager = $this->entityTypeManager->getStorage('node');
    $node = $node_manager->create($values);
    $node->setPublished($data['publish']);
    $node->save();
    $id = $node->id();
    if (is_numeric($id)) {
      return (string) $id;
    }
    return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_NOT_FOUND);
  }

  /**
   * Returns the default text format ID.
   *
   * @param $user
   *   Drupal user object.
   *
   * @return array|mixed|null|string
   *   Return the text format ID.
   */
  public function getDefaultFormat($user) {
    $format_load = $this->blogapiConfig->get('text_format');
    if ($format_load) {
      return $format_load;
    }
    return filter_default_format($user);
  }

  /**
   * Returns a loaded node object.
   *
   * @param $nid
   *   The node ID.
   *
   * @param $username
   *   Drupal username.
   *
   * @param $pass
   *   Drupal password.
   *
   * @return object
   *   Either an error or a node object.
   */
  public function getPost($nid, $username, $pass) {
    // Check user authentication.
    $user = $this->authenticate($username, $pass, TRUE);
    if (!$user) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_AUTH);
    }

    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (!$node) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_NOT_FOUND, $nid);
    }
    if (!$node->access('view', $user)) {
      // User does not have permission to view the node.
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_ACCESS, $nid);
    }

    return $node;
  }

  /**
   * Callback for uploading a new image.
   *
   * @param $ct
   *   Content type machine name.
   *
   * @param $username
   *   Drupal username.
   *
   * @param $pass
   *   Drupal password.
   *
   * @param $data
   *   The image contents.
   *
   * @return array|object
   *   Either an error or the image URL.
   */
  public function newMedia($ct, $username, $pass, $data) {
    // Check user authentication.
    $user = $this->authenticate($username, $pass, TRUE);
    if (!$user) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_AUTH);
    }

    // Check if content type is manageable with blogapi.
    if (!$this->validateBlogId($ct)) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_CT);
    }

    $uri = 'public://' . $data['name'];
    $bits = $data['bits'];
    $entity = file_save_data($bits, $uri);
    if ($entity) {

      // Check the upload filesize.
      $max_filesize = file_upload_max_size();
      if ($max_filesize && $entity->getSize() > $max_filesize) {
        return $this->returnXmlError(self::BLOGAPI_XML_ERROR_IMG_SIZE, $max_filesize);
      }

      $new_uri = $entity->getFileUri();
      $url = Url::fromUri(file_create_url($new_uri))->toString();
      return ['url' => $url, 'struct'];
    }
    return $this->returnXmlError(self::BLOGAPI_XML_ERROR_IMG_SAVE);
  }

  /**
   * Callback for deleting a post.
   *
   * @param $nid
   *   The node ID.
   *
   * @param $user
   *   Drupal user.
   *
   * @param $pass
   *   Drupal password.
   *
   * @return bool|object
   *   The operation success.
   */
  public function deletePost($nid, $user, $pass) {
    // Check user authentication.
    $user = $this->authenticate($user, $pass, TRUE);
    if (!$user) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_AUTH);
    }

    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (!$node) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_NOT_FOUND, $nid);
    }
    if (!$this->checkUserNodeAccess($user, $node)) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_UPDATE, $nid);
    }
    if (!$node->access('delete', $user)) {
      // User does not have permission to view the node.
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_DELETE, $nid);
    }

    $node->delete();
    return TRUE;
  }

  /**
   * Helper that returns all available text formats.
   *
   * @return array
   *   An array of available formats.
   */
  public function getTextFormats() {
    $plugins = filter_formats();
    $filters = [];

    foreach ($plugins as $format) {
      $filter['key'] = $format->id();
      $filter['label'] = $format->get('name');
      $filters[] = $filter;
    }

    return $filters;
  }

  /**
   * Method that sets a nodes status.
   *
   * @param $nid
   *   The node ID.
   *
   * @param $user
   *   Drupal username.
   *
   * @param $pass
   *   Drupal password.
   *
   * @param bool $publish
   *   Boolean Drupal publish status.
   *
   * @return bool|object
   *   A success bool.
   */
  public function publishPost($nid, $user, $pass, $publish = TRUE) {
    $user = $this->authenticate($user, $pass, TRUE);
    if (!$user) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_AUTH);
    }

    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (!$node) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_NOT_FOUND, $nid);
    }
    if (!$this->checkUserNodeAccess($user, $node)) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_UPDATE, $nid);
    }
    if (!$node->access('edit', $user)) {
      // User does not have permission to view the node.
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_NODE_UPDATE, $nid);
    }

    $node->setPublished($publish);
    return TRUE;
  }

  /**
   * Method that returns recent posts.
   *
   * @param $ct
   *   Content type machine name.
   *
   * @param $user
   *   Drupal username.
   *
   * @param $pass
   *   Drupal password.
   *
   * @param $nr
   *   Number of recent posts to return.
   *
   * @param bool $bodies
   *   TRUE if the node contents are to be returned also.
   *
   * @return array|object
   *   The operation response.
   */
  public function getRecentPosts($ct, $user, $pass, $nr, $bodies = TRUE) {
    $user = $this->authenticate($user, $pass, TRUE);

    // Check user authentication.
    if (!$user) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_AUTH);
    }

    // Check if content type is manageable with blogapi.
    if (!$this->validateBlogId($ct)) {
      return $this->returnXmlError(self::BLOGAPI_XML_ERROR_CT);
    }

    // Run the query for recent nodes.
    $query = \Drupal::entityQuery('node')
      ->condition('type', $ct)
      ->sort('created', 'DESC')
      ->range(0, $nr);

    // Find only the users content if the users role can not edit any content.
    if (!$user->hasPermission('manage any content blogapi')) {
      $query->condition('uid', $user->id());
    }

    $result = $query->execute();
    $response = [];

    // Format the response for each node.
    foreach ($result as $nid) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      $response[] = $this->formatXml($node, $bodies);
    }

    return $response;
  }

  /**
   * Helper function to format xml output.
   *
   * @param $node
   *   A node object.
   *
   * @param bool $bodies
   *   TRUE if the node contents are to be returned also.
   *
   * @return array
   *   The XML output.
   */
  public function formatXml($node, $bodies = TRUE) {
    $url = $node->url();
    $options = ['absolute' => TRUE];
    $externalUrl = Url::fromUri('internal:' . $url, $options)->toString();

    if ($node->isPublished()) {
      $post_status = 'published';
    }
    else {
      $post_status = 'draft';
    }

    $xmlrpcval = array(
      'userid' => $node->getOwnerId(),
      'dateCreated' => xmlrpc_date($node->getCreatedTime()),
      'title' => $node->getTitle(),
      'postid' => $node->id(),
      'link' => $externalUrl,
      'permaLink' => $externalUrl,
      'post_status' => $post_status,
    );

    // Fetch also the node contents.
    if ($bodies) {
      $content_type = $node->getType();

      // Get the body field.
      $body_field = $this->blogapiConfig->get('body_' . $content_type);
      $body = $node->get($body_field)->getValue();
      if (empty($body)) {
        $body_value = '';
        $format = $this->getDefaultFormat(NULL);
      }
      else {
        $body_value = $body[0]['value'];
        $format = $body[0]['format'];
      }

      // Get the comment field.
      $comment_field = $this->blogapiConfig->get('comment_' . $content_type);
      $node_comment = $node->$comment_field->getValue();

      if ($node_comment[0]['status'] == CommentItemInterface::CLOSED) {
        $comment = 2;
      }
      elseif ($node_comment[0]['status'] == CommentItemInterface::OPEN) {
        $comment = 1;
      }
      else {
        $comment = CommentItemInterface::HIDDEN;
      }

      $xmlrpcval['content'] = '<title>' . $node->getTitle() . '</title>' . $body_value;
      $xmlrpcval['description'] = $body_value;
      $xmlrpcval['mt_allow_comments'] = $comment;
      $xmlrpcval['mt_convert_breaks'] = $format;
    }

    return $xmlrpcval;
  }

  /**
   * Return TRUE if the passed response is an xmlrpc error.
   *
   * @param $response
   *   The response to evaluate.
   *
   * @return bool
   *   Bool that decides if the response is an error or not.
   */
  public function responseIsError($response) {
    if ($response instanceof \stdClass && isset($response->is_error)) {
      if ($response->is_error) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Helper function to determine default comment settings on a content type.
   *
   * @param $ct
   *   Drupal content type.
   *
   * @return int
   *   Return comment status code.
   */
  public function getDefaultCommentSetting($ct) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $ct);
    $field_name = $this->blogapiConfig->get('comment_' . $ct);
    $field = $definitions[$field_name];
    $setting = $field->getSetting('default_mode');
    if (!is_null($setting)) {
      return $setting;
    }
    return CommentItemInterface::CLOSED;
  }

  /**
   * Handles the returning of errors.
   *
   * @param $error_code
   *   The error code.
   *
   * @param int $arg
   *   Optional arg for t().
   *
   * @return object
   *   The xmlrpc error object.
   */
  public function returnXmlError($error_code, $arg = 0) {
    module_load_include('inc', 'xmlrpc', 'xmlrpc');
    switch ($error_code) {
      case self::BLOGAPI_XML_ERROR_AUTH:
        return xmlrpc_error(401, t('Access denied.'));

      case self::BLOGAPI_XML_ERROR_NODE_NOT_FOUND:
        return xmlrpc_error(402, t('Node @nid not found.', ['@nid' => $arg]));

      case self::BLOGAPI_XML_ERROR_NODE_ACCESS:
        return xmlrpc_error(403, t('Access to node @nid denied.', ['@nid' => $arg]));

      case self::BLOGAPI_XML_ERROR_NODE_UPDATE:
        return xmlrpc_error(404, t('You do not have permission to update node @nid.', ['@nid' => $arg]));

      case self::BLOGAPI_XML_ERROR_CT:
        return xmlrpc_error(405, t('Can not access content type with BlogAPI.'));

      case self::BLOGAPI_XML_ERROR_NODE_CREATE:
        return xmlrpc_error(406, t('You do not have permission to create this type of node.'));

      case self::BLOGAPI_XML_ERROR_NODE_DELETE:
        return xmlrpc_error(407, t('You do not have permission to delete node @nid.', $arg));

      case self::BLOGAPI_XML_ERROR_IMG_SIZE:
        return xmlrpc_error(408, t('Error uploading file because it exceeded the maximum filesize of @maxsize.', array('@maxsize' => format_size($arg))));

      case self::BLOGAPI_XML_ERROR_IMG_SAVE:
        return xmlrpc_error(409, t('Error storing file.'));

      default:
        return xmlrpc_error(400, t('Fatal error.'));
    }
  }

}
