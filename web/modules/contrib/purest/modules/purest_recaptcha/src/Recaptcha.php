<?php

namespace Drupal\purest_recaptcha;

use Drupal\Core\State\StateInterface;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use GuzzleHttp\Client;

/**
 * Class Recaptcha.
 */
class Recaptcha implements RecaptchaInterface {

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * ReCAPTCHA Secret key.
   *
   * @var string
   */
  protected $secretKey;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Guzzle HTTP Client class.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * ReCAPTCHA API endpoint.
   */
  protected const RECAPTCHA_ENDPOINT = "https://www.google.com/recaptcha/api/siteverify";

  /**
   * Constructs a new Recaptcha object.
   */
  public function __construct(StateInterface $state, RequestStack $request, Client $http_client) {
    $this->state = $state;
    $this->secretKey = $this->state->get('purest_recaptcha.secret_key');
    $this->currentRequest = $request->getCurrentRequest();
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(string $recaptcha_response) {
    $query = self::RECAPTCHA_ENDPOINT . '?';
    $params = [
      'secret' => $this->secretKey,
      'response' => $recaptcha_response,
    ];
    $query .= UrlHelper::buildQuery($params);

    $result = $this->httpClient->get($query, ['Accept' => 'application/json']);
    $output = $result->getBody()->getContents();
    $response = json_decode($output, TRUE);

    if (!$response['success']) {
      return FALSE;
    }

    return TRUE;
  }

}
