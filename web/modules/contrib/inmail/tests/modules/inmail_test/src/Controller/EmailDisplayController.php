<?php

namespace Drupal\inmail_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\inmail\MIME\MimeEncodings;
use Drupal\inmail\MIME\MimeEntityInterface;
use Drupal\inmail\MIME\MimeMultipartMessage;
use Drupal\past\PastEventInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Test email display controller.
 */
class EmailDisplayController extends ControllerBase {

  /**
   * Renders the email argument display of an past event.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception in case of invalid event.
   */
  public function formatDisplay(PastEventInterface $past_event, $view_mode) {
    $message = $this->getMessage($past_event);

    $build['#title'] = t('Email display');
    $build['email'] = [
      '#type' => 'inmail_message',
      '#message' => $message,
      '#view_mode' => $view_mode,
      '#download_url' => Url::fromRoute('inmail_test.attachment_download', ['past_event' => $past_event->id()]),
    ];

    return $build;
  }

  /**
   * Provides a view support for the given attachment (MIME entity) path.
   *
   * @param \Drupal\past\PastEventInterface $past_event
   *   The past event.
   * @param string $path
   *   The path to find a corresponding MIME entity. In case "*" is passed,
   *   the raw mail message content will be returned.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception in case of invalid event.
   */
  public function getAttachment(PastEventInterface $past_event, $path) {
    /** @var \Drupal\Inmail\MIME\MimeMultipartMessage $message */
    $message = $this->getMessage($past_event);

    // @todo: Inject the service.
    /** @var \Drupal\inmail\MIME\MimeMessageDecomposition $message_decomposition */
    $message_decomposition = \Drupal::service('inmail.message_decomposition');

    // Offer download of the raw email message.
    if ($path == '~') {
      $headers = [
        'Content-Disposition' => 'attachment; filename=original_message.eml',
        'Content-Type' => 'message/rfc822',
      ];
      return new Response($message->toString(), Response::HTTP_OK, $headers);
    }

    // Filter-out non-multipart messages.
    if (!$message instanceof MimeMultipartMessage) {
      return new Response(NULL, Response::HTTP_NOT_FOUND);
    }

    // @todo: Do not allow direct access to mail parts.
    // Get the entity from the given path.
    if (!$entity = $message_decomposition->getEntityByPath($message, $path)) {
      return new Response(NULL, Response::HTTP_NOT_FOUND);
    }

    // Decode the attachment body.
    $decoded_body = MimeEncodings::decode($entity->getBody(), $entity->getContentTransferEncoding());

    return new Response($decoded_body, Response::HTTP_OK, $this->getHeaders($entity));
  }

  /**
   * Validates the past event and returns the parsed message.
   *
   * @param \Drupal\past\PastEventInterface $past_event
   *   The past event.
   *
   * @return \Drupal\inmail\MIME\MimeMessage|\Drupal\inmail\MIME\MimeMessageInterface
   *   Returns the parsed message.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception in case of invalid event.
   */
  protected function getMessage(PastEventInterface $past_event) {
    // Throw an exception if the event is not created by Inmail or if the raw
    // message is not logged.
    if ($past_event->getModule() != 'inmail' || (!$raw_email_argument = $past_event->getArgument('email'))) {
      throw new NotFoundHttpException();
    }

    // @todo: Inject the parser service.
    /** @var \Drupal\inmail\MIME\MimeParser $parser */
    $parser = \Drupal::service('inmail.mime_parser');

    return $parser->parseMessage($raw_email_argument->getData());
  }

  /**
   * Returns the HTTP headers for the given entity.
   *
   * @param \Drupal\inmail\MIME\MimeEntityInterface $entity
   *   The entity to get headers for.
   *
   * @return string[]
   *   An array of HTTP headers.
   */
  protected function getHeaders(MimeEntityInterface $entity) {
    // Get the parsed content type.
    $content_type = $entity->getContentType();
    // Create the file name.
    $filename = !empty($content_type['parameters']['name']) ? $content_type['parameters']['name'] : 'mime_entity';
    // Display images in the browser.
    $content_disposition = $content_type['type'] == 'image' ? 'inline; filename="' . $filename . '"' : 'attachment; filename="' . $filename . '"';
    // Use default content type or fallback to one defined in RFC 2045 sec 5.2.
    $content_type = $entity->getHeader()->getFieldBody('Content-Type') ?: 'text/plain; charset=us-ascii';

    return [
      'Content-Disposition' => $content_disposition,
      'Content-Type' => $content_type,
      'Content-Transfer-Encoding' => $entity->getContentTransferEncoding(),
    ];
  }

}
