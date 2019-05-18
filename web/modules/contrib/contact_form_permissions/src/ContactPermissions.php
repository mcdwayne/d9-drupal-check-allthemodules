<?php

namespace Drupal\contact_form_permissions;

use Drupal\contact\ContactFormInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for contact form.
 *
 * @package Drupal\contact_form_permissions
 */
class ContactPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The Contact Form storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $contactFormStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ContactPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, ModuleHandlerInterface $module_handler) {
    $this->entityManager = $entity_manager;
    $this->contactFormStorage = $this->entityManager->getStorage('contact_form');
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * Returns an array of contact form permissions.
   *
   * @return array
   *   The contact form permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function permissions() {
    $perms = [];

    if ($this->moduleHandler->moduleExists('contact_storage')) {
      $perms['manage settings contact storage form'] = [
        'title' => $this->t('Manage contact storage settings'),
      ];
    }

    $perms['add contact form'] = [
      'title' => $this->t('Add contact form'),
    ];

    // Generate permissions for all contact forms.
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    foreach ($this->contactFormStorage->loadMultiple() as $contact_form) {
      // Let Drupal core manage the personal contact form.
      if ($contact_form->id() === 'personal') {
        continue;
      }

      $perms += $this->buildPermissions($contact_form);
    }

    return $perms;
  }

  /**
   * Get the permission key for a given contact form.
   *
   * @param string $operation
   *   The operation.
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *   The contact form.
   *
   * @return string
   *   The permission key for a contact form.
   */
  public function getPermissionKey($operation, ContactFormInterface $contact_form) {
    return $operation . ' ' . $contact_form->id() . ' contact form';
  }

  /**
   * Returns a list of permissions for a given contact form.
   *
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *   The contact form.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(ContactFormInterface $contact_form) {
    $contact_form_params = ['%type_name' => $contact_form->label()];

    // @TODO: Add view permission.
    $permissions = [
      $this->getPermissionKey('edit', $contact_form) => [
        'title' => $this->t('%type_name: Edit contact form', $contact_form_params),
      ],
      $this->getPermissionKey('delete', $contact_form) => [
        'title' => $this->t('%type_name: Delete contact form', $contact_form_params),
      ],
    ];

    if ($this->moduleHandler->moduleExists('contact_storage')) {
      $permissions[$this->getPermissionKey('activation', $contact_form)] = [
        'title' => $this->t('%type_name: Enable/Disable contact form', $contact_form_params),
      ];
    }

    return $permissions;
  }

}
