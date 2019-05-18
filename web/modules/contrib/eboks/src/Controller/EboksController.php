<?php
/**
 * @file
 * Contains e-Boks controller definition.
 */

namespace Drupal\eboks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\eboks\EboksSenderMSOutlook;
use Drupal\eboks\EboksSenderNets;
use Drupal\eboks\EboksStatusChecker;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class e-Boks controller.
 *
 * @package Drupal\eboks\Controller
 */
class EboksController extends ControllerBase {

  protected $config;

  /**
   * Send e-Boks message callback.
   */
  public function sendTestMessage($sender) {
    $config = \Drupal::service('config.factory')->get('eboks.' . $sender);

    $receiver = $config->get('test_receiver');
    $receiver_type = $config->get('test_receiver_type');

    $result = 'Test message sending failed';
    if (!empty($receiver) && !empty($receiver_type)) {
      $messages[] = [
        'content' => '<p>Hello world!!!</p>',
        'description' => 'Test sending',
      ];

      switch ($sender) {
        case 'nets':
          $e_boks_sender = new EboksSenderNets($receiver, $receiver_type, $messages, 'custom sender data');

          break;

        case 'msoutlook':
          $e_boks_sender = new EboksSenderMSOutlook($receiver, $receiver_type, $messages, 'custom sender data');
          break;
      }
      if ($e_boks_sender->isValid() && $e_boks_sender->init()) {
        $e_boks_sender->send();
        $result = 'Test message has been sent';
      }
      drupal_set_message($result);
    }
    else {
      drupal_set_message($result . ' Check test settings', 'warning');
    }

    return RedirectResponse::create(Url::fromRoute('entity.eboks_message.collection')->toString());
  }

  /**
   * Update e-Boks message callback.
   */
  public function check($id = FALSE) {
    $e_boks_checker = new EboksStatusChecker();
    $e_boks_checker->check($id);
    return RedirectResponse::create(Url::fromRoute('entity.eboks_message.collection')->toString());
  }

}
