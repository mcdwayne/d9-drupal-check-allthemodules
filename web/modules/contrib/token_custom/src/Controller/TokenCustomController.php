<?php

namespace Drupal\token_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\token_custom\TokenCustomTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for token_custom entity.
 */
class TokenCustomController extends ControllerBase {

  /**
   * The custom token storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $tokenCustomStorage;

  /**
   * The custom token type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $tokenCustomTypeStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('token_custom'),
      $entity_manager->getStorage('token_custom_type')
    );
  }

  /**
   * Constructs a TokenCustom object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $token_custom_storage
   *   The custom token storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $token_custom_type_storage
   *   The custom token type storage.
   */
  public function __construct(EntityStorageInterface $token_custom_storage, EntityStorageInterface $token_custom_type_storage) {

    $this->tokenCustomStorage = $token_custom_storage;
    $this->tokenCustomTypeStorage = $token_custom_type_storage;
  }

  /**
   * Displays add custom token links for available types.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for a list of the custom token types that can be added or
   *   if there is only one custom token type defined for the site, the function
   *   returns the custom token add page for that custom token type.
   */
  public function add(Request $request) {

    $types = $this->tokenCustomTypeStorage->loadMultiple();
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type, $request);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any token types yet. Go to the <a href=":url">token type creation page</a> to add a new token type.', [
          ':url' => Url::fromRoute('token_custom.type_add')->toString(),
        ]),
      ];
    }

    return ['#theme' => 'token_custom_add_list', '#content' => $types];
  }

  /**
   * Presents the custom token creation form.
   *
   * @param \Drupal\token_custom\TokenCustomTypeInterface $token_custom_type
   *   The custom token type to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(TokenCustomTypeInterface $token_custom_type, Request $request) {

    $token = $this->tokenCustomStorage->create([
      'type' => $token_custom_type->id(),
    ]);
    return $this->entityFormBuilder()->getForm($token);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\token_custom\TokenCustomTypeInterface $token_custom_type
   *   The custom token type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(TokenCustomTypeInterface $token_custom_type) {
    return $this->t('Add %type custom token', [
      '%type' => $token_custom_type->label(),
    ]);
  }

}
