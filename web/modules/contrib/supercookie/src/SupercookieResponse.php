<?php
namespace Drupal\supercookie;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Path\AliasManager;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Path\PathValidator;
use Drupal\node\Entity\Node;

/**
 * Handles HTTP responses for Supercookie requests.
 */
class SupercookieResponse {

  protected $supercookieManager;
  protected $supercookiePath;
  protected $config;
  protected $request;
  protected $session;
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(SupercookieManager $supercookie_manager, RequestStack $request_stack, Session $session, AliasManager $alias_manager, PathValidator $path_validator) {
    $this->config = $supercookie_manager->config;
    $this->supercookieManager = $supercookie_manager;
    $this->supercookiePath = $alias_manager->getAliasByPath('/supercookie');
    $this->request = $request_stack->getCurrentRequest();
    $this->session = $session;
    $this->pathValidator = $path_validator;
  }

  /**
   * TODO.
   */
  private function getInstance() {

    $supercookie = NULL;
    $args = $this->request->query->all();
    $ip_addr = $this->request->getClientIp();

    if ($this->request->getPathInfo() == $this->supercookiePath) {
      if (!empty($args['client']) && !empty($args['date'])) {

        // Set client + server data.
        $data = array(
          'server' => array(
            'REMOTE_ADDR' => $ip_addr,
            'REMOTE_HOST' => gethostbyaddr($ip_addr),
            'HTTP_USER_AGENT' => $this->request->server->get('HTTP_USER_AGENT'),
            'HTTP_ACCEPT' => $this->request->server->get('HTTP_ACCEPT'),
          ),
          'client' => $args['client'],
        );

        $hash = md5(serialize($data));

        $geolocation = array();
        if (!empty($args['geo'])) {
          $geolocation = explode(',', $args['geo'], 2);
          $geolocation = array(
            'geolocation' => array(
              'latitude' => $geolocation[0],
              'longitude' => $geolocation[1],
            ),
          );
        }

        // Upsert supercookie instance.
        $supercookie = $this->supercookieManager
          ->match($hash)
          ->save($args['date'])
          ->mergeCustom($geolocation);

        $this->session->set($this->config['supercookie_name_server'], $hash);

        // Track entity views; also note the "ref" arg's controlling JS var.
        // @see Drupal.behaviors.supercookie.entitiesTracked
        if (!empty($args['ref'])) {
          $ref_url = $this->pathValidator->getUrlIfValid($args['ref']);
          if ($ref_url) {
            $ref_args = $ref_url->getRouteParameters();
            if (!empty($ref_args['node'])) {
              $ref_node = Node::load($ref_args['node']);
              $ref_terms = [];

              $field_defs = $ref_node->getFieldDefinitions();
              foreach ($field_defs as $field_config) {
                if ($field_config->getType() == 'entity_reference') {
                  $field_settings = $field_config
                    ->getItemDefinition()
                    ->getSettings();

                  if ($field_settings['target_type'] == 'taxonomy_term') {
                    $field_values = $ref_node
                      ->get($field_config->getName())
                      ->getString();

                    $field_values = explode(',', str_replace(' ', '', $field_values));
                    $ref_terms = array_merge($ref_terms, $field_values);
                  }
                }
              }
              $ref_terms = array_unique($ref_terms);

              // Increment entity view counts.
              $supercookie = $this->supercookieManager
                ->trackNodes(array($ref_node->id()))
                ->trackTerms($ref_terms);
            }
          };
        }

      }
    }

    if (empty($supercookie)) {
      $hash = $this->session->get($this->config['supercookie_name_server']);
      $supercookie = $this->supercookieManager
        ->match($hash);
    }

    return $supercookie;
  }

  /**
   * Dumps a JSON blob of current user's supercookie data.
   */
  public function getResponse() {

    $supercookie = $this->getInstance();

    // Now set header, cookie and JSON for client. Note explicit header() here
    // vs. attached to JsonResponse below...we want to apply this header to
    // all responses, not just the /supercookie response.
    $once = &drupal_static(get_class($this) . Unicode::ucwords(__FUNCTION__), FALSE);
    if (empty($once)) {
      if (empty($supercookie->dnt)) {
        $this->request->server->set('HTTP_' . str_replace('-', '_', Unicode::strtoupper($this->config['supercookie_name_header'])), $supercookie->data);
        header($supercookie->config['supercookie_name_header'] . ': ' . $supercookie->data, TRUE);
        setcookie($supercookie->config['supercookie_name_server'], $supercookie->data, $supercookie->expires, '/');
      }
      else {
        header($supercookie->config['supercookie_name_header'] . ': ' . '', TRUE);
        setcookie($supercookie->config['supercookie_name_server'], '', 0);
      }

      $once = TRUE;
    }

    $data = array(
      'scid' => $supercookie->scid,
      'hash' => $supercookie->data,
      'expires' => $supercookie->expires,
      'dnt' => !empty($supercookie->dnt),
      'geolocation' => !empty($supercookie->config['supercookie_geolocation']),
      'json' => $this->supercookiePath,
      'name_server' => $supercookie->config['supercookie_name_server'],
      'name_header' => $supercookie->config['supercookie_name_header'],
    );

    $headers = [];
    if ($this->request->getPathInfo() == $this->supercookiePath) {
      $headers = array(
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0',
      );
    }

    return new JsonResponse($data, 200, $headers);
  }

  /**
   * Dumps a JSON blob of all transformed supercookie data.
   */
  public function getReport() {
    $data = $this->supercookieManager->report();

    return new JsonResponse($data, 200, array(
      'Cache-Control' => 'no-cache, no-store, must-revalidate',
      'Pragma' => 'no-cache',
      'Expires' => '0',
    ));
  }

}
