<?php

namespace Drupal\node_like_dislike_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Database\Connection;
use Drupal\Core\Ajax\AlertCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node_like_dislike_field\Helper\LikeDislikeHelper;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Render\Renderer;

/**
 * Controller routines for like-dislike routes.
 */
class LikesDislikesController extends ControllerBase {

  /**
   * The entitymanager object for node.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The database object for node.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The currentUser object for node.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The config object for node.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The mailManager object for node.
   *
   * @var Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * The render object for rendering data.
   *
   * @var Drupal\Core\Render\Renderer
   */
  protected $render;

  /**
   * Implements __construct().
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity object.
   * @param \Drupal\Core\Database\Connection $database
   *   The database object for node.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current_user object for node.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config_factory object for node.
   * @param \Drupal\Core\Mail\MailManager $mail_manager
   *   The mail_manager object for node.
   * @param Drupal\Core\Render\Renderer $render
   *   The render object for rendering data.
   */
  public function __construct(EntityTypeManagerInterface $entityManager, Connection $database, AccountProxy $current_user, ConfigFactoryInterface $config_factory, MailManager $mail_manager, Renderer $render) {
    $this->entityManager = $entityManager;
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->config = $config_factory;
    $this->mailManager = $mail_manager;
    $this->render = $render;
  }

  /**
   * Create function return static configuration.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('plugin.manager.mail'),
      $container->get('renderer')
    );
  }

  /**
   * Controller function returns ajax response.
   *
   * @param mixed $clicked
   *   The $clicked parameter for node.
   * @param mixed $data
   *   The $data parameter for node.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Return the ajaxResponse.
   */
  public function content($clicked, $data) {

    // Object of LikeDislikeHelper class.
    $likedislike = LikeDislikeHelper::getInstance();
    $ip_address = $likedislike->getClientIp();
    $response = new AjaxResponse();
    $now = strtotime(date("Y/m/d"));
    $decode_data = json_decode(base64_decode($data));
    $entity_data = $this->entityManager->getStorage($decode_data->entity_type)->load($decode_data->entity_id);
    $field_name = $decode_data->field_name;
    if ($clicked == 'report-abuse') {
      $key = 'report_abuse';
      $module = 'node_like_dislike_field';
      $to = $this->currentUser->getEmail();
      $mail_body = [
        '#theme' => 'contact-mail-body',
        '#entity_id' => $decode_data->entity_id,
        '#label' => $entity_data->label(),
        '#cache' => ['max-age' => 0],
      ];
      $params['message'] = $this->render->render($mail_body);
      $params['title'] = 'Notification for report abuse';
      $langcode = $this->currentUser->getPreferredLangcode();
      $send = TRUE;
      $result = $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
      $response->addCommand(new AlertCommand('You anonymously reported the node abuse!'));
    }
    $querydata1 = $this->database->select('user_count', 'x')->fields('x', ['likedislike_flag']);
    $and = db_and();
    $and->condition('nid', $decode_data->entity_id);
    $and->condition('ip_address', $ip_address);
    $querydata1->condition($and);
    $res = $querydata1->execute()->fetchCol();
    if (empty($res)) {
      if ($clicked == 'like') {
        $entity_data->$field_name->likes++;
        $entity_data->save();
        $response->addCommand(new HtmlCommand('#like', $entity_data->$field_name->likes));
        $querydata = $this->database->insert('user_count')->fields([
          'nid' => $decode_data->entity_id,
          'ip_address' => $ip_address,
          'likedislike_flag' => 1,
        ]);
        $querydata->execute();
      }
      if ($clicked == 'dislike') {
        $entity_data->$field_name->dislikes++;
        $entity_data->save();
        $response->addCommand(new HtmlCommand('#dislike', $entity_data->$field_name->dislikes));
        $querydata = $this->database->insert('user_count')->fields([
          'nid' => $decode_data->entity_id,
          'ip_address' => $ip_address,
          'likedislike_flag' => 0,
        ]);
        $querydata->execute();
      }
    }
    else {
      if (($clicked == 'like' && $res[0] == 1) || ($clicked == 'dislike' && $res[0] == 0)) {
        $response->addCommand(new AlertCommand('Oops! Your response already submitted'));
      }
      if ($clicked == 'like' && $res[0] == 0) {
        $entity_data->$field_name->likes++;
        $entity_data->$field_name->dislikes--;
        $entity_data->save();
        $response->addCommand(new HtmlCommand('#like', $entity_data->$field_name->likes));
        $response->addCommand(new HtmlCommand('#dislike', $entity_data->$field_name->dislikes));
        $querydata2 = $this->database->update('user_count')->fields([
          'likedislike_flag' => 1,
        ]);
        $and = db_and();
        $and->condition('nid', $decode_data->entity_id);
        $and->condition('ip_address', $ip_address);
        $querydata2->condition($and);
        $querydata2->execute();
      }
      if ($clicked == 'dislike' && $res[0] == 1) {
        $entity_data->$field_name->dislikes++;
        $entity_data->$field_name->likes--;
        $entity_data->save();
        $response->addCommand(new HtmlCommand('#dislike', $entity_data->$field_name->dislikes));
        $response->addCommand(new HtmlCommand('#like', $entity_data->$field_name->likes));
        $querydata2 = $this->database->update('user_count')->fields([
          'likedislike_flag' => 0,
        ]);
        $and = db_and();
        $and->condition('nid', $decode_data->entity_id);
        $and->condition('ip_address', $ip_address);
        $querydata2->condition($and);
        $querydata2->execute();
      }
    }
    $query1 = $this->database->select('like_count', 'x')->fields('x', ['likes']);
    $and = db_and();
    $and->condition('nid', $decode_data->entity_id);
    $and->condition('date_timestamp', $now);
    $query1->condition($and);
    $result = $query1->execute()->fetchCol();
    if (empty($result)) {
      $query = $this->database->insert('like_count')->fields([
        'nid' => $decode_data->entity_id,
        'likes' => $entity_data->$field_name->likes,
        'dislikes' => $entity_data->$field_name->dislikes,
        'date_timestamp' => $now,
      ]);
      $query->execute();
    }
    else {
      $query2 = $this->database->update('like_count')->fields([
        'likes' => $entity_data->$field_name->likes,
        'dislikes' => $entity_data->$field_name->dislikes,
        'date_timestamp' => $now,
      ]);
      $and = db_and();
      $and->condition('nid', $decode_data->entity_id);
      $and->condition('date_timestamp', $now);
      $query2->condition($and);
      $query2->execute();
    }
    return $response;
  }

}
