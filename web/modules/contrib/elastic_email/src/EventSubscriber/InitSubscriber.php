<?php

namespace Drupal\elastic_email\EventSubscriber;

use Drupal\elastic_email\Service\ElasticEmailManager;
use ElasticEmailClient\ApiException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InitSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  public function onEvent() {
    if (!\Drupal::currentUser()->hasPermission('administer site configuration')
      && !\Drupal::service('router.admin_context')->isAdminRoute()) {
      return;
    }

    try {
      /** @var ElasticEmailManager $service */
      $service = \Drupal::service('elastic_email.api');
      $accountData = (array) $service->getAccount()->Load();

      $creditLowThreshold = \Drupal::config('elastic_email.settings')->get('credit_low_threshold');
      if (!empty($creditLowThreshold) && $accountData['credit'] <= $creditLowThreshold) {
        drupal_set_message(t('Your Elastic Email credit is getting low - currently at %credit %currency', [
          '%credit' => $accountData['credit'],
          '%currency' => $accountData['currency'],
        ]), 'warning', FALSE);
      }
    }
    catch (ApiException $e) {
    }
  }

}
