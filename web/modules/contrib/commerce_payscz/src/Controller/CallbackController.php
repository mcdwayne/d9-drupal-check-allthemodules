<?php

namespace Drupal\commerce_payscz\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Pages where Pays redirects user after using gateway or call it asynchronously.
 */
class CallbackController extends ControllerBase {

  /**
   * Page for successful payment.
   */
  public function okPage(Request $request) {
    if ($MerchantOrderNumber = $request->query->get('MerchantOrderNumber'))
      return $this->redirect(
        'commerce_payment.checkout.return',
        ['commerce_order' => $MerchantOrderNumber, 'step' => 'payment'],
        ['query' => $request->query->all()]
      );
    else
      return ['#markup' => t('Thank you!')];
  }

  /**
   * Page for failed payment.
   */
  public function errPage() {
  	$mail_url = 'mailto:' . \Drupal::config('system.site')->get('mail');
    $link = Link::fromTextAndUrl(t('Contact us'), Url::fromUri($mail_url))->toRenderable();
    return ['link' => $link];
  }

  /**
   * Page for asynchronous confirmation.
   */
  public function confirmPage(Request $request) {
    return $this->redirect(
      'commerce_payment.notify',
      // Hard coded gateway ID because it is difficult to find it there.
      ['commerce_payment_gateway' => 'pays'],
      ['query' => $request->query->all()]
    );
  }

}
