<?php

namespace Drupal\open_connect\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;

/**
 * Annotation for open connect providers.
 *
 * @Annotation
 */
class OpenConnectProvider extends Plugin {

  /**
   * The unique identifier of the identity provider.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the identity provider.
   *
   * @var string
   */
  protected $label = '';

  /**
   * The URL of the homepage of the identity provider.
   *
   * @var string
   */
  protected $homepage = '';

  /**
   * API urls such as authorization, access_token, openid, user_info.
   *
   * @var array
   */
  protected $urls;

  /**
   * An array of keys.
   *
   * @var array
   */
  protected $keys;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values) {
    if (empty($values['id']) || empty($values['label'])) {
      throw new InvalidPluginDefinitionException('Plugin id and label cannot be empty.');
    }
    if (!isset($values['description'])) {
      $values['description'] = $values['label'];
    }
    // Ensure url defaults.
    if (!isset($values['urls'])) {
      $values['urls'] = [];
    }
    $values['urls'] += [
      'authorization' => '',
      'access_token' => '',
      'openid' => '',
      'user_info' => '',
    ];
    // Ensure keys defaults.
    if (!isset($values['keys'])) {
      $values['keys'] = [];
    }
    $values['keys'] += [
      'client_id' => 'client_id',
      'client_secret' => 'client_secret',
      'openid' => 'openid',
    ];
    parent::__construct($values);
  }

}
