<?php

namespace Drupal\webform_cart\Plugin\WebformElement;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformLibrariesManagerInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform_cart\WebformCartSessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'webform_example_composite' element.
 *
 * @WebformElement(
 *   id = "webform_cart",
 *   label = @Translation("Webform Cart Data"),
 *   description = @Translation("Adds content from the webform cart data fields."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 */
class WebformCart extends WebformCompositeBase {

  /**
   * Drupal\webform_cart\WebformCartSessionInterface definition.
   *
   * @var \Drupal\webform_cart\WebformCartSessionInterface
   */
  protected $webformCartSession;

  /**
   * @inheritDoc
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              LoggerInterface $logger,
                              ConfigFactoryInterface $config_factory,
                              AccountInterface $current_user,
                              EntityTypeManagerInterface $entity_type_manager,
                              ElementInfoManagerInterface $element_info,
                              WebformElementManagerInterface $element_manager,
                              WebformTokenManagerInterface $token_manager,
                              WebformLibrariesManagerInterface $libraries_manager,
                              WebformCartSessionInterface $webform_cart_session) {
    parent::__construct($configuration,
      $plugin_id,
      $plugin_definition,
      $logger,
      $config_factory,
      $current_user,
      $entity_type_manager,
      $element_info,
      $element_manager,
      $token_manager,
      $libraries_manager);
      $this->webformCartSession = $webform_cart_session;
  }


  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   *
   * @return \Drupal\webform\Plugin\WebformElement\WebformCompositeBase|\Drupal\webform_cart\Plugin\WebformElement\WebformCart
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webform'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.token_manager'),
      $container->get('webform.libraries_manager'),
      $container->get('webform_cart.session')
    );
  }

  /**
   * @return array
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
        'data_type' => '',
      ];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return parent::buildConfigurationForm($form, $form_state);

  }

  /**
   * @param array $element
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission) {
    if ($element['#type'] == 'webform_cart') {
      $cartId = $this->webformCartSession->getCartIds();
      if ($cartId) {
        $orderEntity = $this->entityTypeManager->getStorage('webform_cart_order')->load($cartId[0]);

        $orderLineItems = $orderEntity->get('field_order_item')->getValue();
        foreach ($orderLineItems as $key => $value) {
          $orderLineIds[$key] = $value['target_id'];
        }
        $submission_data = $webform_submission->getData();
        $new_submission_data = $submission_data;
        $orderItemEntity = $this->entityTypeManager->getStorage('webform_cart_item')->loadMultiple($orderLineIds);
        $serializer = \Drupal::service('serializer');
        $data = $serializer->serialize($orderItemEntity, 'json', ['plugin_id' => 'entity']);
        $new_submission_data['webform_cart_data']['data1'] = "some test data";
        $new_submission_data['webform_cart_data']['data2'] = $data;
        $webform_submission->setData($new_submission_data);
      }
    }

    parent::preSave($element, $webform_submission);
  }


}
