<?php

namespace Drupal\elastic_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\elastic_email\Service\ElasticEmailManager;
use ElasticEmailClient\ApiException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for the elastic_email module.
 */
class ElasticEmailController extends ControllerBase {

  public function dashboard() {
    try {
      /** @var ElasticEmailManager $service */
      $service = \Drupal::service('elastic_email.api');
      $accountData = (array) $service->getAccount()->Load();

      return [
        '#theme' => 'elastic_email_dashboard',
        '#data' => $accountData,
        '#attached' => [
          'library' => ['elastic_email/admin'],
        ],
      ];
    }
    catch (ApiException $e) {
      drupal_set_message(t('You need to configure your Elastic Email before you can continue to use this module.'), 'error');
      return new RedirectResponse(Url::fromRoute('elastic_email.admin_settings')->toString());
    }
  }

  public function viewEmail($msgId) {
    if (empty($msgId)) {
      return [
        '#markup' => 'This is a problem with loading this email, please try again.'
      ];
    }

    try {
      /** @var ElasticEmailManager $service */
      $service = \Drupal::service('elastic_email.api');
      $data = (array) $service->getEmail()->Status($msgId);
      $emailData = $this->getEmailData($msgId);

      return [
        '#theme' => 'elastic_email_view_email',
        '#title' => $this->t('View Email: "%subject"', ['%subject' => $emailData['subject']]),
        '#data' => array_merge($data, $emailData, ['msgId' => $msgId]),
        '#attached' => [
          'library' => [
            'elastic_email/admin'
          ],
        ],
      ];
    }
    catch (ApiException $e) {
      drupal_set_message(t('You need to configure your Elastic Email before you can continue to use this module.'), 'error');
      return new RedirectResponse(Url::fromRoute('elastic_email.admin_settings')->toString());
    }
  }

  public function viewEmailContent($msgId) {
    if (empty($msgId)) {
      return [
        '#markup' => 'This is a problem with loading this email, please try again.'
      ];
    }

    $build = [
      'page' => [
        '#theme' => 'elastic_email_view_email_content',
        '#data' => $this->getEmailData($msgId),
      ],
    ];
    $html = \Drupal::service('renderer')->renderRoot($build);
    $response = new Response();
    $response->setContent($html);
    return $response;
  }

  /**
   * Gets the email data for a specific message id.
   *
   * @param string $msgId
   *   The email message id
   *
   * @return array|null
   */
  protected function getEmailData($msgId) {
    $data = &drupal_static(__METHOD__);
    $cid = 'elastic_email:' . __FUNCTION__ . ':' . $msgId;

    if ($cache = \Drupal::cache()->get($cid)) {
      $data = $cache->data;
    }
    else {
      try {
        /** @var ElasticEmailManager $service */
        $service = \Drupal::service('elastic_email.api');
        $data = (array) $service->getEmail()->View($msgId);
        \Drupal::cache()->set($cid, $data, 60 * 60 * 6);
      }
      catch (ApiException $e) {
        $message = $this->t('There was a problem with retriving email message with id @msgId. The error message was: @errMsg', ['@msgId' => $msgId, '@errMsg' => $e->getMessage()]);
        \Drupal::logger('elastic_email')->error($message);
        $data['body'] = 'There has been a problem with retrieving your email message.';
      }
    }
    return $data;
  }

}
