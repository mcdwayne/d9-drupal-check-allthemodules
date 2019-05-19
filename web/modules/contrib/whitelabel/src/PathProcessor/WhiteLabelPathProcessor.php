<?php

namespace Drupal\whitelabel\PathProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\whitelabel\WhiteLabelProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class WhiteLabelPathProcessor.
 *
 * @package Drupal\whitelabel\PathProcessor
 */
class WhiteLabelPathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  use StringTranslationTrait;

  /**
   * White label negotiation: use the domain as whitelabel indicator.
   */
  const CONFIG_QUERY_PARAMETER = 'query_parameter';

  /**
   * White label negotiation: use the path prefix as whitelabel indicator.
   */
  const CONFIG_PATH_PREFIX = 'path_prefix';

  /**
   * White label negotiation: use the domain as whitelabel indicator.
   */
  const CONFIG_DOMAIN = 'domain';

  /**
   * Holds the white label provider.
   *
   * @var \Drupal\whitelabel\WhiteLabelProviderInterface
   */
  protected $whiteLabelProvider;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Helper function for fetching all white label modes.
   *
   * @return array
   *   An array of descriptions, keyed by the mode system name.
   */
  public static function getModes() {
    return [
      self::CONFIG_QUERY_PARAMETER => t('Query parameter (<em>example.com/somepage?wl=<strong>whitelabel_token</strong></em>)'),
      self::CONFIG_PATH_PREFIX => t('Path prefix (<em>example.com/<strong>whitelabel_token</strong>/somepage</em>)'),
      self::CONFIG_DOMAIN => t('Domain (<em><strong>whitelabel_token</strong>.example.com/somepage</em>)'),
    ];
  }

  /**
   * WhiteLabelPathProcessor constructor.
   *
   * @param \Drupal\whitelabel\WhiteLabelProviderInterface $white_label_provider
   *   The white label provider.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(WhiteLabelProviderInterface $white_label_provider, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account) {
    $this->whiteLabelProvider = $white_label_provider;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $config = $this->configFactory->get('whitelabel.settings');
    $whitelabel_mode = $config->get('mode');

    $token = NULL;
    $whitelabel = NULL;
    $invalid_whitelabel = FALSE;

    switch ($whitelabel_mode) {
      case WhiteLabelPathProcessor::CONFIG_QUERY_PARAMETER:
        $query_parameter = !empty($request->query->get('whitelabel')) ? $request->query->get('whitelabel') : NULL;

        // Try to load the white label from the query parameter.
        $token = $query_parameter;
        $whitelabel = $this->loadWhiteLabelFromToken($token);
        break;

      case WhiteLabelPathProcessor::CONFIG_PATH_PREFIX:
        // Strip the token from the path.
        $parts = explode('/', trim($path, '/'));
        $token = array_shift($parts);

        // Rebuild a path with the remaining parts.
        $temp_path = implode('/', $parts);

        if ($whitelabel = $this->loadWhiteLabelFromToken($token)) {
          $path = $temp_path;
        }
        else {
          $invalid_whitelabel = TRUE;
          $whitelabel = NULL;
        }
        break;

      case WhiteLabelPathProcessor::CONFIG_DOMAIN:
        // Get only the host, not the port.
        $http_host = $request->getHost();
        $host_parts = explode('.', $http_host);

        // Make sure that the host was the token plus the base domain.
        if ($http_host == $host_parts[0] . '.' . $config->get('domain')) {
          $token = $host_parts[0];
          $whitelabel = $this->loadWhiteLabelFromToken($token);
        }
        else {
          $invalid_whitelabel = TRUE;
        }
        break;
    }

    // Store the white label in the session if there is one.
    if (!empty($whitelabel)) {
      $this->whiteLabelProvider->setWhiteLabel($whitelabel);
    }
    // If the white label is invalid or permissions are wrong, show 404.
    elseif ($invalid_whitelabel && ($whitelabel_mode == WhiteLabelPathProcessor::CONFIG_PATH_PREFIX || $whitelabel_mode == WhiteLabelPathProcessor::CONFIG_DOMAIN)) {
      throw new NotFoundHttpException('The path ' . $path . ' and the white label ' . $token . ' did not result in an existing page.');
    }

    return $path;
  }

  /**
   * Attempts to load a white label object from a provided token.
   *
   * @param string $token
   *   The token to load the white label for.
   *
   * @return \Drupal\whitelabel\WhiteLabelInterface|null
   *   The loaded white label object, or NULL if no match was found.
   */
  private function loadWhiteLabelFromToken($token) {
    if (empty($token)) {
      return NULL;
    }

    if ($whitelabels = $this->entityTypeManager->getStorage('whitelabel')
      ->loadByProperties(['token' => $token])
    ) {

      /** @var \Drupal\whitelabel\WhiteLabelInterface $whitelabel */
      $whitelabel = reset($whitelabels);
      return $whitelabel;
    }

    // Return NULL if no white label was found.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $whitelabel = $this->whiteLabelProvider->getWhiteLabel();

    // No white label or no permission to serve it, so leave.
    // TODO: Check if we can append a white label toggle to the url's $options
    // array.
    if (empty($whitelabel)) {
      return $path;
    }

    $whitelabel_token = $whitelabel->getToken();

    // Apply white label in the right place.
    $whitelabel_mode = $this->configFactory->get('whitelabel.settings')->get('mode');
    switch ($whitelabel_mode) {

      case WhiteLabelPathProcessor::CONFIG_QUERY_PARAMETER:
        // Append the white label query parameter.
        $options['query']['whitelabel'] = $whitelabel_token;
        break;

      case WhiteLabelPathProcessor::CONFIG_PATH_PREFIX:
        // Append the white label token as a prefix, preserve existing prefixes.
        // The weight of the inbound path processors defines the inbound order.
        // (So make sure they match.)
        $options['prefix'] = $whitelabel_token . '/' . $options['prefix'];
        break;

      case WhiteLabelPathProcessor::CONFIG_DOMAIN:
        // Append the white label token as a domain prefix.
        $url_scheme = 'http';
        $port = 80;
        if ($request) {
          $url_scheme = $request->getScheme();
          $port = $request->getPort();
        }

        global $base_url;
        $options['base_url'] = isset($options['base_url']) ? $options['base_url'] : $base_url;

        // Save the original base URL. If it contains a port, we need to
        // retain it below.
        if (!empty($options['base_url'])) {
          // The colon in the URL scheme messes up the port checking below.
          $normalized_base_url = str_replace([
            'https://',
            'http://',
          ], '', $options['base_url']);
        }

        // Ask for an absolute URL with our modified base URL.
        $options['absolute'] = TRUE;

        $options['base_url'] = $url_scheme . '://' . $whitelabel_token . '.' . $normalized_base_url;

        // In case either the original base URL or the HTTP host contains a
        // port, retain it.
        if (isset($normalized_base_url) && strpos($normalized_base_url, ':') !== FALSE) {
          list(, $port) = explode(':', $normalized_base_url);
          $options['base_url'] .= ':' . $port;
        }
        elseif (($url_scheme == 'http' && $port != 80) || ($url_scheme == 'https' && $port != 443)) {
          $options['base_url'] .= ':' . $port;
        }

        if (isset($options['https'])) {
          if ($options['https'] === TRUE) {
            $options['base_url'] = str_replace('http://', 'https://', $options['base_url']);
          }
          elseif ($options['https'] === FALSE) {
            $options['base_url'] = str_replace('https://', 'http://', $options['base_url']);
          }
        }

        // Add Drupal's subfolder from the base_path if there is one.
        $options['base_url'] .= rtrim(base_path(), '/');
        break;
    }

    if ($bubbleable_metadata) {
      $bubbleable_metadata
        ->addCacheableDependency($whitelabel);
    }

    return $path;
  }

}
