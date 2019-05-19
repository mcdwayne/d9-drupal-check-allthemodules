<?php

namespace Drupal\ubercart_funds\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the plugin Mail.
 *
 * @Mail(
 *   id = "ubercart_funds_mail",
 *   label = @Translation("Ubercart funds HTML mailer"),
 *   description = @Translation("Sends HTML emails")
 * )
 */
class FundsMail extends PHPMail implements MailInterface, ContainerFactoryPluginInterface {

  /**
   * Set variables to be reused.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Ubercart_funds mail constructor.
   *
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The service renderer.
   */
  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {

    $message = $this->cleanBody($message);

    $render = [
      '#theme' => 'uc_funds_mail',
      '#message' => $message,
    ];
    $message['body'] = $this->renderer->renderRoot($render);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    return parent::mail($message);
  }

}
