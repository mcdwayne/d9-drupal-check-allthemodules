<?php

namespace Drupal\ckeditor_mentions\Controller;

use Drupal\ckeditor_mentions\CKEditorMentionSuggestionEvent;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Database\Database;
use Drupal\image\Entity\ImageStyle;

/**
 * Route callback for matches.
 */
class CKMentionsController extends ControllerBase {

  /**
   * The Event Dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher')
    );
  }

  /**
   * CKMentionsController constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The Event dispatcher service.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Return a list of suggestions based in the keyword provided by the user.
   *
   * @param string $match
   *   Match value.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json of matches.
   */
  public function getRealNameMatch($match = '') {
    $message = ['result' => 'fail'];

    $str = trim(str_replace('@', '', $match));
    $str = strip_tags($str);

    if ($str) {
      $uid = \Drupal::currentUser()->id();
      $database = Database::getConnection('default');

      $query = $database->select('realname', 'rn');
      $query->leftJoin('users_field_data', 'ud', 'ud.uid = rn.uid');
      $query->leftJoin('user__user_picture', 'up', 'up.entity_id = rn.uid');
      $query->leftJoin('file_managed', 'fm', 'fm.fid = up.user_picture_target_id');
      $query->fields('rn', ['uid', 'realname']);
      $query->fields('fm', ['uri']);
      $query->condition('rn.realname', '%' . $query->escapeLike($str) . '%', 'LIKE');
      $query->isNotNull('rn.realname');
      $query->condition('ud.status', 1);

      // Exclude currently logged in user from returned list.
      if ($uid) {
        $query->condition('rn.uid', $uid, '!=');
      }

      $results = $query->execute();
      $matches = [];

      foreach ($results as $result) {
        $url = '';
        if ($result->uri) {
          $url = ImageStyle::load('mentions_icon')->buildUrl($result->uri);
        }
        $matches[] = [
          'uid' => $result->uid,
          'name' => $result->realname,
          'image' => $url,
        ];
      }

      $suggestion_event = new CKEditorMentionSuggestionEvent($match);
      $suggestion_event = $this->eventDispatcher->dispatch('ckeditor_mentions.suggestion', $suggestion_event);
      $matches = array_merge($suggestion_event->getSuggestions(), $matches);
      $message = ['result' => 'success', 'data' => $matches];
    }

    return new JsonResponse($message);
  }

}
