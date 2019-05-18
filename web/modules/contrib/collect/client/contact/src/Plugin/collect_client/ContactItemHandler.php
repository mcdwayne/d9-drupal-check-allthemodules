<?php
/**
 * @file
 * Contains \Drupal\collect_client_contact\Plugin\collect_client\ContactItemHandler.
 */

namespace Drupal\collect_client_contact\Plugin\collect_client;

use Drupal\collect_client\CollectItem;
use Drupal\collect_client\Plugin\collect_client\ItemHandlerInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\contact\MessageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Serializes a submitted contact message to a HAL+JSON object.
 *
 * @CollectClientItemHandler(
 *   id = "contact"
 * )
 */
class ContactItemHandler extends PluginBase implements ItemHandlerInterface {

  /**
   * The schema URI for contact submissions.
   */
  const SCHEMA_URI = 'https://drupal.org/project/collect_client/contact';

  /**
   * The mime type of the submitted data.
   */
  const MIMETYPE = 'application/json';

  /**
   * {@inheritdoc}
   */
  public function supports($item) {
    return is_array($item)
      && array_key_exists('date', $item)
      && array_key_exists('account', $item)
      && $item['account'] instanceof AccountInterface
      && array_key_exists('message', $item)
      && $item['message'] instanceof MessageInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function handle($item) {
    /* @var \Drupal\contact\MessageInterface $message */
    $message = $item['message'];
    $result = new CollectItem();

    $result->schema_uri = static::SCHEMA_URI;
    $result->type = static::MIMETYPE;
    $result->date = $item['date'];

    /* @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface|\Symfony\Component\Serializer\SerializerInterface $serializer */
    $serializer = \Drupal::service('serializer');

    /* @var \Drupal\Core\Session\AccountInterface $account */
    $account = $item['account'];
    if ($account instanceof UserInterface) {
      $user = $account;
    }
    elseif ($account->isAnonymous()) {
      $user = NULL;
    }
    else {
      $user = \Drupal::entityManager()->getStorage('user')->load($account->id());
    }

    $data = array();
    $data['user'] = $serializer->normalize($user, 'hal_json');
    $data['fields'] = $serializer->normalize($message->getFieldDefinitions(), 'json');
    $data['values'] = $serializer->normalize($message, 'hal_json');

    if (!empty($data['values']['_links']['self']['href'])) {
      $result->uri = $data['values']['_links']['self']['href'];
    }
    else {
      $result->uri = \Drupal::url('<front>', array(), array('absolute' => TRUE));
      $result->uri .= 'entity/message/' . $message->bundle() . '/' . \Drupal::service('uuid')->generate();
    }

    $result->data = $serializer->serialize($data, 'json');

    return $result;
  }
}
