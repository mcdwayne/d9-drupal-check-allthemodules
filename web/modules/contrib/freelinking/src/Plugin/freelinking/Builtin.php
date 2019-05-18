<?php

namespace Drupal\freelinking\Plugin\freelinking;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\freelinking\Plugin\FreelinkingPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Freelinking builtin plugin.
 *
 * @Freelinking(
 *   id = "builtin",
 *   title = @Translation("Built-in"),
 *   weight = -1,
 *   hidden = true,
 *   settings = { }
 * )
 */
class Builtin extends FreelinkingPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getTip() {
    return $this->t('Redact, show text only, or display the indicator');
  }

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    return '/^(showtext|nowiki|redact)$/';
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    $text = '';

    if ('showtext' === $target['indicator']) {
      $text = $target['text'] ? $target['text'] : $target['dest'];
    }
    elseif ('nowiki' === $target['indicator']) {
      $text .= '[[';
      $text .= $target['text'] ? $target['text'] : $target['dest'];
      $text .= ']]';
    }
    elseif ('redact' === $target['indicator']) {
      if ($this->account->isAuthenticated()) {
        $text = $target['dest'];
      }
      else {
        $text = $target['text'] ? $target['text'] : '******';
      }
    }

    return [
      '#markup' => $text,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

}
