<?php

namespace Drupal\private_page\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\PathElement;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\PermissionHandlerInterface;

/**
 * Form controller for the private_page entity edit forms.
 *
 * @ingroup private_page
 */
class PrivatePageForm extends ContentEntityForm {

  /**
   * The user permissions service.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $userPermissions;

  /**
   * Constructs a PrivatePageForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\user\PermissionHandlerInterface $user_permissions
   *   The user permissions service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, PermissionHandlerInterface $user_permissions) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->userPermissions = $user_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('user.permissions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /* @var \Drupal\private_page\Entity\PrivatePage $entity */
    $entity = $this->entity;

    $form['private_path'] = array(
      '#title' => $this->t('Path'),
      '#type' => 'path',
      '#default_value' => $entity->getPrivatePagePath(),
      '#description' => $this->t('Specify the path for example: "/node/1"'),
      '#convert_path' => PathElement::CONVERT_NONE,
      '#element_validate' => [
        [$this, 'validatePath'],
      ],
      '#required' => TRUE,
    );

    $form['permissions'] = [
      '#type' => 'select',
      '#title' => $this->t('Permissions'),
      '#description' => $this->t('Select the permissions.'),
      '#default_value' => $entity->getPermissions(),
      '#multiple' => TRUE,
      '#options' => $this->getPermissionOptions(),
      '#size' => 50,
      '#required' => TRUE,
    ];

    $form['#attached']['library'][] = 'private_page/chosen.core';
    $form['#attached']['library'][] = 'private_page/chosen';

    return $form;
  }

  /**
   * Validates path.
   * 
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $element
   *   Form state object.
   * 
   */
  public function validatePath(array &$element, FormStateInterface $form_state) {
    if ($element['#value'] && $element['#value'][0] !== '/') {
      $form_state->setError($element, t('The path needs to start with a slash.'));
    }
  }

  /**
   * Get user permissions.
   * 
   * @return array
   *   Array of user permissions.
   * 
   */
  protected function getPermissionOptions() {
    $permissions = [];

    foreach ($this->userPermissions->getPermissions() as $key => $permission) {
      $permissions[$key] = $permission['title'];
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    /* @var \Drupal\private_page\Entity\PrivatePage $entity */
    $entity = $this->entity;

    $entity->set('private_path', $form_state->getValue('private_path'));
    $entity->set('permissions', $form_state->getValue('permissions'));
    $entity->save();

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $status;
  }
}
