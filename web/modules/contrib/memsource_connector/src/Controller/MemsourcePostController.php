<?php

namespace Drupal\memsource_connector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MemsourcePostController.
 *
 * @package Drupal\memsource_connector\Controller
 */
class MemsourcePostController extends ControllerBase {

  /**
   * Returns articles/pages/content-types filtered by provided query parameters.
   *
   * @param Request $request
   *   HTTP request object.
   *
   * @return JsonResponse
   *   Articles in JSON format.
   */
  public function listArticles(Request $request) {
    $check_response = memsource_connector_check_auth($request);
    if ($check_response !== memsource_connector_get_token()) {
      return new JsonResponse($check_response);
    }
    $type = $request->get('type');
    if ($type == 'post') {
      $type = 'article';
    }
    $last_id = 0;
    $new_posts = $request->get('newPosts');
    if ($new_posts) {
      $last_id = $this->getLastProcessedId();
    }
    $statuses = $this->getMemsourceConfig()->get('list_status');
    $response = array();
    if ($type === '/') {
      /** @var \Drupal\node\Entity\NodeType[] $nodes */
      $nodes = $this->entityTypeManager()->getStorage('node_type')->loadMultiple();
      $nodesName = 'types';
    } else {
      /** @var \Drupal\node\Entity\Node[] $nodes */
      $nodes = $this->entityTypeManager()->getStorage('node')->loadMultiple();
      $nodesName = 'posts';
    }
    foreach ($nodes as $node) {
      if ($type === '/') {
        $response[] = [
          'type' => $node->id(),
          'label' => $node->get('name'),
          'folder' => true,
        ];
      } elseif ($node->bundle() == $type && $node->id() > $last_id) {
        $node_data = $this->getNodeData($node);
        if ($node_data['size'] > 0 && in_array($node_data['status'], $statuses)) {
          $response[] = $node_data;
        }
      }
    }
    return new JsonResponse(array($nodesName => $response));
  }

  /**
   * Returns a JSON representation of article for given ID.
   *
   * @param Request $request
   *   HTTP request object.
   * @param int $id
   *   Article ID.
   *
   * @return JsonResponse
   *   An article in JSON format.
   */
  public function getArticle(Request $request, $id) {
    $check_response = memsource_connector_check_auth($request);
    if ($check_response !== memsource_connector_get_token()) {
      return new JsonResponse($check_response);
    }
    $node = $this->entityTypeManager()->getStorage('node')->load($id);
    $response = $this->getNodeData($node, TRUE);
    return new JsonResponse($response);
  }

  /**
   * Inserts/updates a translation of the given article ID.
   *
   * @param Request $request
   *   HTTP request object.
   * @param int $id
   *   Article ID.
   *
   * @return JsonResponse
   *   A translated article in JSON format.
   */
  public function postTranslation(Request $request, $id) {
    $check_response = memsource_connector_check_auth($request);
    if ($check_response !== memsource_connector_get_token()) {
      return new JsonResponse($check_response);
    }
    $title = $request->get('title');
    $content = $request->get('content');
    $lang = $request->get('lang');
    $node = Node::load($id);
    if ($node->hasTranslation($lang)) {
      $translation = $node->getTranslation($lang);
      $translation->title->value = $title;
      $translation->body->value = $content;
    }
    else {
      $node->addTranslation($lang, array(
        'uid' => $this->getMemsourceConfig()->get('current_user_id'),
        'status' => $this->getMemsourceConfig()->get('insert_status'),
        'title' => $title,
        'body' => array(
          'summary' => '',
          'value' => $content,
          'format' => $node->body->format,
        ),
      ));
    }
    $node->save();
    return new JsonResponse(
      array(
        'nid' => $node->id(),
        'title' => $title,
        'body' => $content,
      )
    );
  }

  /**
   * Stores ID of article that was processed by the remote automatic routine.
   *
   * @param Request $request
   *   HTTP request object.
   *
   * @return JsonResponse
   *   Last processed article ID in JSON format.
   */
  public function storeLastProcessedId(Request $request) {
    $check_response = memsource_connector_check_auth($request);
    if ($check_response !== memsource_connector_get_token()) {
      return new JsonResponse($check_response);
    }
    // Compare with stored ID and save only a higher one.
    $stored_last_id = $this->getLastProcessedId();
    $new_last_id = $request->get('lastId');
    $overwrite = $request->get('overwrite');
    if ($overwrite || $new_last_id > $stored_last_id) {
      $this->getMemsourceConfig()->set('last_processed_id', $new_last_id)->save();
      return new JsonResponse(array('id' => $new_last_id));
    }
    return new JsonResponse(array('id' => $stored_last_id));
  }

  /**
   * Returns stored ID of the last processed article.
   *
   * @return array|mixed|null
   *   Last processed article ID.
   */
  public function getLastProcessedId() {
    return $this->getMemsourceConfig()->get('last_processed_id');
  }

  /**
   * A helper function to create a JSON representation of an article.
   *
   * @param EntityInterface $node
   *   A node object.
   * @param bool $add_body
   *   An optional parameter to specify if to add article body to the array.
   *
   * @return array
   *   An array of article data.
   */
  private function getNodeData(EntityInterface $node, $add_body = FALSE) {
    $array = $node->toArray();
    $data = [
      'nid' => $array['nid'][0]['value'],
      'uuid' => $array['uuid'][0]['value'],
      'vid' => $array['vid'][0]['value'],
      'langcode' => $array['langcode'][0]['value'],
      'type' => $array['type'][0]['target_id'],
      'title' => $array['title'][0]['value'],
      'uid' => $array['uid'][0]['target_id'],
      'status' => $array['status'][0]['value'],
      'created' => $array['created'][0]['value'],
      'changed' => $array['changed'][0]['value'],
      'size' => strlen($array['body'][0]['value']),
    ];
    if ($add_body) {
      $data['body'] = $array['body'][0]['value'];
    }
    return $data;
  }

  /**
   * Get the application config instance.
   *
   * @return \Drupal\Core\Config\Config
   *   A config instance.
   */
  private function getMemsourceConfig() {
    return $this->config('config.memsource_config');
  }

}
