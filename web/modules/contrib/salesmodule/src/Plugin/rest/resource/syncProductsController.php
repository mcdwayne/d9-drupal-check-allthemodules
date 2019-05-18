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
 *   id = "syncProductsController",
 *   label = @Translation("Products sync controller"),
 *   uri_paths = {
 *     "canonical" = "/commerce_sf/productsf",
 *     "https://www.drupal.org/link-relations/create" = "/commerce_sf/productsf"
 *   }
 * )
 */
class syncProductsController extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new syncProductsController object.
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
  public function post(array $key) {

         foreach ($key as $variation ) {

         $getProductSF = $this->getProductSF($variation['variation_id']);

         if(empty($getProductSF))  {
         $getProductData = $this->getBundleRevisionVariation($variation['variation_id']);
          $this->insertSFValueInProduct($getProductData,$variation['sfid']);      
  }

}
    return new ModifiedResourceResponse($key, 200);
  
}



   private function getBundleRevisionVariation($variationId) {

     $connection = \Drupal::database();
     $result = $connection->query("SELECT bundle, revision_id, variations_target_id from commerce_product__variations where variations_target_id = :entityId", [":entityId"=>$variationId])->fetchAssoc();
     return $result;
   }

 
   private function getProductSF($variationId) {

     $connection = \Drupal::database();

     $result = $connection->query("SELECT field_salesforce_id_value from commerce_product_variation__field_salesforce_id where entity_id = :variationId", [':variationId'=>$variationId])->fetchAssoc();


     return count($result)? $result['field_salesforce_id_value']:'';
   }


   private function insertSFValueInProduct($dataProduct, $sfId) {
      $connection = \Drupal::database();

      $connection->insert("commerce_product_variation__field_salesforce_id")
                  ->fields([
                    'bundle'=>$dataProduct['bundle'],
                    'entity_id' =>$dataProduct['variations_target_id'],
                    'revision_id' => $dataProduct['revision_id'],
                    'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
                    'delta' => '0',
                    'field_salesforce_id_value' => $sfId])->execute();
 
   }


}
