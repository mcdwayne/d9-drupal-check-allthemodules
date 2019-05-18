<?php

namespace Drupal\transcoding_aws\Controller;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\transcoding\TranscodingStatus;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SNS extends ControllerBase {

  public function handlePost() {
    try {
      $message = Message::fromRawPostData();
      if (!(new MessageValidator())->isValid($message) || empty($message->toArray()['Type'])) {
        throw new \Exception();
      }
    }
    catch (\Exception $e) {
      throw new AccessDeniedHttpException();
    }
    $data = $message->toArray();
    switch ($data['Type']) {
      case 'SubscriptionConfirmation':
      case 'UnsubscribeConfirmation':
        $this->handleSubscriptionChange($message);
        break;
      case 'Notification':
        $this->processNotification($message);
        break;
      default:
        break;
    }
    return new Response();
  }

  /**
   * Handle subscription changes by pinging their callback.
   *
   * @param \Aws\Sns\Message $message
   */
  protected function handleSubscriptionChange(Message $message) {
    $client = new Client();
    $client->get($message->toArray()['SubscribeURL']);
  }

  protected function processNotification(Message $message) {
    $data = $message->toArray()['Message'];
    try {
      $data = \GuzzleHttp\json_decode($data, TRUE);
    }
    catch (\InvalidArgumentException $e) {
      throw new BadRequestHttpException();
    }
    $job = $this->entityTypeManager()->getStorage('transcoding_job')
      ->loadByProperties(['remote_id' => $data['jobId']]);
    if ($job) {
      /** @var \Drupal\transcoding\TranscodingJobInterface $job */
      $job = reset($job);
    }
    else {
      throw new BadRequestHttpException();
    }
    switch ($data->state) {
      case 'ERROR':
        $job->set('status', TranscodingStatus::FAILED);
        break;
      case 'COMPLETED':
        $job->set('status', TranscodingStatus::PROCESSED);
        break;
    }
    $job->setServiceData([
      'result' => $data,
    ] + $job->getServiceData());
    $job->save();
  }

}
