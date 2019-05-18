<?php

namespace Drupal\mailjet_api\Plugin\Mail;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Render\RendererInterface;
use Drupal\mailjet_api\MailjetApiHandler;
use Html2Text\Html2Text;
use Drupal\Component\Utility\Html;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Render\Markup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;

/**
 * Modify the Drupal mail system to use Mailjet API when sending emails.
 *
 * @Mail(
 *   id = "mailjet_api_mail",
 *   label = @Translation("Mailjet API mailer"),
 *   description = @Translation("Sends the message using Mailjet API.")
 * )
 */
class MailjetApiMail implements MailInterface, ContainerFactoryPluginInterface {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $mailjetApiConfig;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Mailjet API handler.
   *
   * @var \Drupal\mailjet_api\MailjetApiHandler
   */
  protected $mailjetApiHandler;

  /**
   * The default list of HTML tags allowed.
   *
   * @var array
   *
   * @see \Drupal\Component\Utility\Xss::filter()
   */
  protected static $tags = ['a', 'em', 'strong', 'cite', 'blockquote', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'p', 'br'];

  /**
   * MailjetApiMail constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $settings
   *   The mailjet api config.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue Factory.
   * @param \Drupal\mailjet_api\MailjetApiHandler $mailjet_api_handler
   *   The mailjet API Handler.
   */
  public function __construct(ImmutableConfig $settings, LoggerInterface $logger, RendererInterface $renderer, QueueFactory $queueFactory, MailjetApiHandler $mailjet_api_handler) {
    $this->mailjetApiConfig = $settings;
    $this->logger = $logger;
    $this->renderer = $renderer;
    $this->queueFactory = $queueFactory;
    $this->mailjetApiHandler = $mailjet_api_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory')->get('mailjet_api.settings'),
      $container->get('logger.factory')->get('mailjet_api'),
      $container->get('renderer'),
      $container->get('queue'),
      $container->get('mailjet_api.mail_handler')
    );
  }

  /**
   * Concatenate and wrap the e-mail body for either plain-text or HTML e-mails.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    $message = $this->massageMessageBody($message);

    // Wrap body with theme function.
    if ($this->mailjetApiConfig->get('use_theme')) {
      $render = [
        '#theme' => isset($message['params']['theme']) ? $message['params']['theme'] : 'mailjet',
        '#message' => $message,
      ];
      $message['body'] = $this->renderer->renderRoot($render);

      $converter = new Html2Text($message['body']);
      $message['plain'] = $converter->getText();
    }

    if ($this->mailjetApiConfig->get('embed_image')) {
      // Process any images specified by a src attribute and replace them with
      // their base64 encoded data.
      $embeddable_images = [];
      $processed_images = [];
      $cid = 1;

      preg_match_all('/src="([^"]+)"/', $message['body'], $embeddable_images);
      for ($i = 0; $i < count($embeddable_images[0]); $i++) {
        $image_id = $embeddable_images[0][$i];
        if (isset($processed_images[$image_id])) {
          continue;
        }
        $image_path = trim($embeddable_images[1][$i]);

        // Remove query parameter if present.
        $image_url = parse_url($image_path);
        $image_path = isset($image_url['path']) ? $image_url['path'] : $image_path;

        // We need to remove the / at the beginning if provided to get the
        // relative path for file_get_contents.
        if (Unicode::substr($image_path, 0, 1) == '/') {
          $image_path = Unicode::substr($image_path, 1);
        }

        $image_data = base64_encode(file_get_contents($image_path));
        $image_mime = \Drupal::service('file.mime_type.guesser')->guess($image_path);
        $image_src = 'data:' . $image_mime . ';base64, ' . $image_data;

        $message['body'] = preg_replace('#' . $image_id . '#', 'src="' . $image_src . '"', $message['body']);
        $processed_images[$image_id] = 1;
        $cid++;
      }
    }

    return $message;
  }

  /**
   * Send the e-mail message.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *   $message['params'] may contain additional parameters.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted or queued, FALSE otherwise.
   */
  public function mail(array $message) {
    // Build the body attended by Mailjet API.
    $body = $this->mailjetApiHandler->buildMessagesBody($message);

    if ($this->mailjetApiConfig->get('use_queue')) {
      /** @var \Drupal\Core\Queue\QueueInterface $queue */
      $queue = $this->queueFactory->get('mailjet_api_cron_worker');

      $item = new \stdClass();
      $item->body = $body;
      $queue->createItem($item);

      // Debug mode: log all messages.
      if ($this->mailjetApiConfig->get('debug_mode')) {
        $this->logger->notice('Successfully queued message from %from to %to.',
          [
            '%from' => $message['from'],
            '%to' => $message['to'],
          ]
        );
      }
      return TRUE;
    }

    return $this->mailjetApiHandler->sendMail($body);
  }

  /**
   * Massages the message body into the format expected for rendering.
   *
   * @param array $message
   *   The message.
   *
   * @return array
   *   The message array with the body formatted.
   */
  public function massageMessageBody(array $message) {
    // Get default mail line endings and merge all lines in the e-mail body
    // separated by the mail line endings. Keep Markup objects and filter others
    // and then treat the result as safe markup.
    // Join the body array into one string.
    $line_endings = Settings::get('mail_line_endings', PHP_EOL);
    $format = $this->mailjetApiConfig->get('format_filter');

    if (is_array($message['body'])) {
      foreach ($message['body'] as $key => $body) {
        $message['body'][$key] = $this->massageBody($body, $message, $format);
      }
      $message['body'] = Markup::create(implode($line_endings, $message['body']));
    }
    else {
      $message['body'] = $this->massageBody($message['body'], $message, $format);
    }
    return $message;
  }

  /**
   * Create safe string passed to the message body.
   *
   * @param string|MarkupInterface $body
   *   The body to check.
   * @param array $message
   *   The message array.
   * @param string $format
   *   the format_id to use.
   *
   * @return MarkupInterface;
   */
  protected function massageBody($body, $message, $format = '') {
    // If text format is specified in settings, run the message through it,
    // except if it has been already marked as safe.
    if ($body instanceof MarkupInterface) {
      return $body;
    }
    if (!empty($format)) {
      $body = check_markup($body, $format, $message['langcode']);
    }
    // Fallback to Xss::filter with our custom tags list (The default list plus
    // br and p tags.
    else {
      $body = Xss::filter($body, self::$tags);
    }

    return $body;
  }

}
