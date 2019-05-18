<?php

namespace Drupal\commerce_quick_edit\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ProductQuickEditController class.
 */
class ProductQuickEditController extends ControllerBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ProductQuickEditController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   Entity form builder.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('config.factory')
    );
  }

  /**
   * Open product edit form in a modal.
   *
   * @param int $id
   *   Product ID.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   JSON response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function openModalForm($id) {
    $product_entity = $this->entityTypeManager
      ->getStorage('commerce_product')
      ->load($id);

    $config = $this->configFactory
      ->get('commerce_quick_edit.settings');

    $quick_note_form = $this->entityFormBuilder->getForm($product_entity);
    $quick_note_form['#attributes']['class'][] = 'commerce-quick-edit';
    $response = new AjaxResponse();

    return $response->addCommand(
      new OpenModalDialogCommand(
        'Product edit',
        $quick_note_form,
        ['width' => $config->get('modal_width')]
      )
    );
  }

}
