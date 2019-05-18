<?php

namespace Drupal\commerce_salesforce_connector\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "syncUsersController",
 *   label = @Translation("Users sync controller"),
 *   uri_paths = {
 *     "canonical" = "/commerce_sf/usersf",
 *     "https://www.drupal.org/link-relations/create" = "/commerce_sf/usersf"
 *   }
 * )
 */
class syncUsersController extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new syncUsersController object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('commerce_salesforce_connector'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
 public function post(array $data) {
  
    foreach ($data as $user ) {

        $getUserSF = $this->getUserSF($user['uid']);
        if(empty($getUserSF))
          $this->insertSFInDrupal($user);
    }

  return new ModifiedResourceResponse($data, 200);
  }

  private function getUserSF($userId) {

     $connection = \Drupal::database();

     $result = $connection->query("SELECT field_salesforce_id_value from user__field_salesforce_id where entity_id = :uid", [':uid'=>$userId])->fetchAssoc();
     
     return count($result)? $result['field_salesforce_id_value']:'';
   }


  private function insertSFInDrupal($userData) {


   $connection = \Drupal::database();
   $connection->insert("user__field_salesforce_id")
                  ->fields([
                    'bundle'=>'user',
                    'entity_id' =>$userData['uid'],
                    'revision_id' => '1',
                    'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
                    'delta' => '0',
                    'field_salesforce_id_value' => $userData['sfid']
                    ])->execute(); 
   }




}
