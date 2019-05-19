<?php

namespace Drupal\sms_ui;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\sms_ui\Entity\SmsHistory;
use Drupal\sms_ui\Entity\SmsHistoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SmsHistoryController extends ControllerBase {

  /**
   * Drupal's renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Dispatches queued SMS messages.
   */
  public function dispatchQueued(SmsHistoryInterface $sms_history) {
//    \Drupal::service('cron')->run();
//    return new RedirectResponse(Url::fromRoute('sms_ui.send_status',
//      ['sms_history' => $sms_history->id()], ['query' => $this->getDestinationArray()])->toString());
  }

  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * Returns the themed SMS History item.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   */
  public function ajaxItem(Request $request) {
    if ($request->query->has('item_id')) {
      $sms_history = SmsHistory::load($request->query->get('item_id'));
      if ($sms_history) {
        $build = [
          '#theme' => 'sms_history',
          '#history' => $sms_history,
          '#user' => $this->currentUser(),
          '#cache' => [
            'max-age' => 0,
          ],
        ];
        return new Response($this->renderer->renderPlain($build));
      }
      else {
        throw new NotFoundHttpException(sprintf('The item with ID %s was not found.', $request->query->get('item_id')));
      }
    }
    throw new NotAcceptableHttpException('The item ID has not been specified.');
  }

}
