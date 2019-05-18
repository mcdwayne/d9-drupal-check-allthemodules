<?php

namespace Drupal\rules_api_post\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides "Rules API Post" rules action.
 *
 * @RulesAction(
 *   id = "RulesAPI_POST",
 *   label = @Translation("API POST"),
 *   category = @Translation("Data"),
 *   context = {
 *     "url" = @ContextDefinition("string",
 *       label = @Translation("URL"),
 *       description = @Translation("The Url address where to post, get and delete request send. <br><b>Example:</b> https://example.com/node?_format=hal_json "),
 *       multiple = TRUE,
 *       required = TRUE,
 *     ),
 *     "linkurl" = @ContextDefinition("string",
 *       label = @Translation("Link URL"),
 *       description = @Translation("The service URL.<br> <b>Example:</b> https://example.com/rest/type/node/article "),
 *       multiple = TRUE,
 *       required = TRUE,
 *     ),
 *     "nodetype" = @ContextDefinition("string",
 *       label = @Translation("Node Type"),
 *       description = @Translation("This holds a value for the content type the API is expecting."),
 *       required = FALSE,
 *      ),
 *     "apiuser" = @ContextDefinition("string",
 *       label = @Translation("API User Name"),
 *       description = @Translation("Username for API Access"),
 *       required = FALSE,
 *      ),
 *     "apipass" = @ContextDefinition("string",
 *       label = @Translation("API User Password"),
 *       description = @Translation("Password for API Access"),
 *       required = FALSE,
 *      ),
 *     "apitoken" = @ContextDefinition("string",
 *       label = @Translation("API Session Token"),
 *       description = @Translation("Session Token for API Access"),
 *       required = FALSE,
 *      ),
 *     "content_author" = @ContextDefinition("string",
 *       label = @Translation("Content Author"),
 *       description = @Translation("This custom field field_content_author Content Author"),
 *       required = FALSE,
 *      ),
 *     "post_title" = @ContextDefinition("string",
 *       label = @Translation("Post Title"),
 *       description = @Translation("A pass through for our content titles."),
 *       required = FALSE,
 *      ),
 *     "post_body" = @ContextDefinition("string",
 *       label = @Translation("Post Body"),
 *       description = @Translation("A pass through for our content body."),
 *       required = FALSE,
 *      ),
 *   },
 *   provides = {
 *     "http_response" = @ContextDefinition("string",
 *       label = @Translation("HTTP data")
 *     )
 *   }
 * )
 *
 */
class RulesAPI_POST extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The logger for the rules channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a httpClient object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param GuzzleHttp\ClientInterface $http_client
   *   The guzzle http client instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger_factory->get('rules_api_post');
    $this->http_client = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('http_client')
    );
  }

  /**
   * Set up form variables
   *
   * @param string[] $url
   *   Url addresses HTTP request.
   * @param string[] $linkurl
   *   Link Url addresse for service
   * @param string[] $nodetype
   *   (optional) The Node Type for API call
   * @param string[] $apiuser
   *   (optional) The User Name for API call
   * @param string[] $apipass
   *   (optional) The User Passord for API call
   * @param string[] $apitoken
   *   (optional) The Session Token for API call
   * @param string[] $content_author
   *   (optional) A custom field, Content Author
   * @param string[] $post_title
   *   (optional) A passthrough for content titles.
   * @param string[] $post_body
   *   (optional) A passthrough for content titles.
   */

//protected function doExecute () {
protected function doExecute(array $url, $linkurl, $nodetype, $apiuser, $apipass, $apitoken, $content_author, $post_title, $post_body ) {
// Debug message
drupal_set_message(t("Activating Rules API POST ..."), 'status');

$serialized_entity = json_encode([
  'title' => [['value' => $post_title]],
  'type' => [['target_id' => $nodetype ]],
  'body' => [['value' => $post_body, 'format' => 'full_html']],
   // Set the value of a custom field
  'field_content_author' => [['value' => $content_author ]],
  '_links' => ['type' => [
        'href' => $linkurl[0]
  ]],
]) ;

$client = \Drupal::httpClient();
$url =$url[0];
$method = 'POST';
$options = [
  'auth' => [
    $apiuser,
    $apipass
  ],
'timeout' => '2',
'body' => $serialized_entity,
'headers' => [
'Content-Type' => 'application/hal+json',
'Accept' => 'application/hal+json',
'X-CSRF-Token' => $apitoken
    ],
];
try {
  $response = $client->request($method, $url, $options);
  $code = $response->getStatusCode();
  if ($code == 200) {
    $body = $response->getBody()->getContents();
    return $body;
  }
}
catch (RequestException $e) {
  watchdog_exception('rules_api_post', $e);
  }
 }
}
