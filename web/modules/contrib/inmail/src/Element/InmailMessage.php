<?php

namespace Drupal\inmail\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Markup;
use Drupal\inmail\MIME\MimeMessageInterface;

/**
 * Provides a render element for displaying Inmail MimeMessage.
 *
 * If a #download_url url is provided, the element will display attachments
 * with a download link. Otherwise just attachment labels.
 *
 * Properties:
 * - #message: The parsed message object.
 *    An instance of \Drupal\inmail\MIME\MimeMessageInterface.
 * - #view_mode: The view mode ("teaser" or "full").
 * - (optional) #body: Identified mail body.
 *
 * Properties available for MIME messages:
 * - (optional) #attachments: A list of mail attachments. The build array
 *   should follow the structure defined in: inmail_message_build_attachment().
 * - (optional) #unknown: A list of non-identified mail parts.
 * - (optional) #download_url: An URL object with the attachment download route.
 *   the object already needs to point to the specific message and maintain a
 *   parameter named "index" to deal with multipart references.
 *
 * Usage example:
 * @code
 * $build['inmail_message_example'] = [
 *   '#title' => $this->t('Inmail MimeMessage Example'),
 *   '#type' => 'inmail_message',
 *   '#message' => $message,
 *   '#view_mode' => 'full',
 *   '#attachments' => $attachments,
 *   '#body' => $body,
 *   '#download_url' => $url,
 * ];
 * @endcode
 *
 * @RenderElement("inmail_message")
 */
class InmailMessage extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return ['#theme' => 'inmail_message'];
  }

  /**
   * Provides markup for the plain text for the given view mode.
   *
   * @param string $plain_text
   *   The raw plain text.
   * @param string $view_mode
   *   (optional) The view mode. Defaults to "full".
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The markup.
   */
  public static function getPlainTextMarkup($plain_text, $view_mode = 'full') {
    $plain_text = Html::escape(trim($plain_text));
    if ($view_mode == 'teaser') {
      $plain_text = substr($plain_text, 0, 300);
    }
    else {
      $plain_text = nl2br($plain_text);
    }

    return Markup::create($plain_text);
  }

  /**
   * Provides HTML markup for the given view mode.
   *
   * @param string $html
   *   The raw HTML.
   * @param string $view_mode
   *   (optional) The view mode. Defaults to "full".
   *
   * @return \Drupal\Component\Render\MarkupInterface|null
   *   The markup or null in case of teaser mode.
   */
  public static function getHtmlMarkup($html, $view_mode = 'full') {
    if ($view_mode == 'teaser') {
      return NULL;
    }
    $filtered_html = Xss::filterAdmin(trim($html));
    return Markup::create($filtered_html);
  }

  /**
   * Returns the link for unsubscribing if present.
   *
   * RFC2369 defines header List-Unsubscribe.
   * List-Unsubscribe: <mailto:unsubscribe-espc-tech-12345N@domain.com>,
   * <http://domain.com/member/unsubscribe/?listname=espc-tech@domain.com?id=12345N>
   *
   * We only identify http links and skip mailto.
   *
   * @param \Drupal\inmail\MIME\MimeMessageInterface $message
   *   The parsed message object.
   *
   * @return string|null
   *   The link for unsubscribing.
   */
  public static function getUnsubsciptionLink(MimeMessageInterface $message) {
    $unsubsribe_string = $message->getHeader()->getFieldBody('List-Unsubscribe');
    if (preg_match('/<(http[^>]+)/', $unsubsribe_string, $matches)) {
      return $matches[1];
    }

    return NULL;
  }

}
