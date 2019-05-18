<?php

namespace Drupal\abstractpermissions\Form;

use Drupal\abstractpermissions\AbstractPermissionsServiceInterface;
use Drupal\abstractpermissions\FormAlter\PermissionsFormMarkGoverned;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PermissionAbstractionForm extends EntityForm {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The permissions form.
   *
   * @var \Drupal\abstractpermissions\Form\PermissionsFormInterface
   */
  protected $permissionsForm;

  /**
   * @inheritDoc
   */
  public function __construct(PermissionHandlerInterface $permissionHandler, PermissionsFormInterface $permissionsForm) {
    $this->permissionHandler = $permissionHandler;
    $this->permissionsForm = $permissionsForm;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions'),
      $container->get('abstractpermissions.permissions_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;

    /** @var \Drupal\abstractpermissions\Entity\PermissionAbstractionInterface $entity */
    $entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Label for the @type.', [
        '@type' => $entity->getEntityType()->getLabel(),
      ]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'source' => ['label'],
        'exists' => '\\' . $entity->getEntityType()->getClass() . '::load',
        'replace_pattern' => '[^a-z0-9_]+',
        'replace' => '_',
      ],
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#rows' => 5,
      '#default_value' => $entity->getDescription(),
      '#description' => $this->t('Description for the @type.', [
        '@type' => $entity->getEntityType()->getLabel(),
      ]),
    ];

    // Derived permisions.
    $abstractedPermissions = $entity->getAbstractedPermissions();
    $abstractedPermissions += ['' => ''];
    $form['abstracted_permissions']['#type'] = 'details';
    $form['abstracted_permissions']['#title'] = $this->t('Abstracted permissions');
    $form['abstracted_permissions']['#description'] = $this->t('Define the newly created permissions.');
    $key = 0;
    foreach ($abstractedPermissions as $id => $label) {
      $form['abstracted_permissions'][$key]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#maxlength' => 255,
        '#default_value' => $label,
        '#description' => $this->t('Label for the @type.', [
          '@type' => $entity->getEntityType()->getLabel(),
        ]),
      ];

      $form['abstracted_permissions'][$key]['id'] = [
        '#type' => 'machine_name',
        '#default_value' => $id,
        '#machine_name' => [
          'source' => ['abstracted_permissions', $key, 'label'],
          'exists' => static::class . '::returnFalse',
          'replace_pattern' => '[^a-z0-9_]+',
          'replace' => '_',
        ],
        '#required' => FALSE,
      ];
      $key += 1;
    }

    // Permissions.
    $form['governed_permissions']['#type'] = 'details';
    $form['governed_permissions']['#title'] = $this->t('Governed permissions');
    $form['governed_permissions']['#description'] = $this->t('Define the permissions to be governed by abstracted permissions.');

    $form['governed_permissions']['table'] = $this->permissionsForm->form(
      ['select' => $this->t('Select')],
      $this->permissionHandler->getPermissions(),
      ['select' => $entity->getGovernedPermissions()]
    );
    PermissionsFormMarkGoverned::alterForm($form['governed_permissions']['table']);


    // Permission mapping.
    $form['permission_mapping']['#type'] = 'details';
    $form['permission_mapping']['#title'] = $this->t('Permission mapping');
    $form['permission_mapping']['#description'] = $this->t('Map permissions to abstract permissions.');

    $form['permission_mapping']['table'] = $this->permissionsForm->form(
      $entity->getAbstractedPermissions(),
      array_intersect_key($this->permissionHandler->getPermissions(), array_fill_keys($entity->getGovernedPermissions(), TRUE)),
      $entity->getPermissionMapping()
    );

    return $form;
  }

  public static function returnFalse() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $derivedPermissions = [];
    $abstractedPermissionValues = (array)$form_state->getValue(['abstracted_permissions']);
    foreach ($abstractedPermissionValues as $key => $abstractedPermissionValue) {
      $id = $abstractedPermissionValue['id'];
      $label = $abstractedPermissionValue['label'];
      if (isset($derivedPermissions[$id])) {
        $form_state->setError($form['abstracted_permissions'][$key], $this->t('Abstracted permission ID must be unique.'));
      }
      elseif ($id && $label) {
        $derivedPermissions[$id] = $label;
      }
      elseif (!$id && $label) {
        $form_state->setError($form['abstracted_permissions'][$key], $this->t('Abstracted permission ID is missing.'));
      }
      elseif ($id && !$label) {
        $form_state->setError($form['abstracted_permissions'][$key], $this->t('Abstracted permission label is missing.'));
      }
      elseif (!$id && !$label) {
        unset($abstractedPermissionValues[$key]);
      }
    }
    $form_state->setValue(['abstracted_permissions'], array_values($abstractedPermissionValues));
  }

  /**
   * @inheritDoc
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\abstractpermissions\Entity\PermissionAbstractionInterface $entity */
    $entity = clone $this->entity;
    $entity->setId($form_state->getValue('id'));
    $entity->setLabel($form_state->getValue('label'));
    $entity->setDescription($form_state->getValue('description'));

    $abstractedPermissions = [];
    $abstractedPermissionValues = (array)$form_state->getValue(['abstracted_permissions']);
    foreach ($abstractedPermissionValues as $abstractedPermissionValue) {
      $abstractedPermissions[$abstractedPermissionValue['id']] = $abstractedPermissionValue['label'];
    }
    $entity->setAbstractedPermissions($abstractedPermissions);

    $permissionsByRole = $this->permissionsForm->extractPermissionsByRole($form_state->getValue(['governed_permissions', 'table']));
    $entity->setGovernedPermissions(reset($permissionsByRole));

    $entity->setPermissionMapping($this->permissionsForm->extractPermissionsByRole($form_state->getValue(['permission_mapping', 'table'])));
    return $entity;
  }


  /**
   * {@inheritdoc}
   */
  public function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);
    $element['saveContinue'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and Continue Editing'),
      '#name' => 'save_continue',
      '#submit' => ['::submitForm', '::save'],
      '#weight' => 7,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    $entity = $this->entity;

    switch ($status) {
      case SAVED_NEW:
        $message = $this->t('Created the %label permission abstraction.', [
          '%label' => $entity->label(),
        ]);
        $log = '%type permission abstraction %id created';
        break;

      default:
        $message = $this->t('Saved the %label permission abstraction.', [
          '%label' => $entity->label(),
        ]);
        $log = '%type permission abstraction %id saved';
    }
    drupal_set_message($message);
    \Drupal::logger('abstractpermissions')->notice($log, [
      '%type' => $entity->getEntityTypeId(),
      '%id' => $entity->id(),
    ]);

    $trigger = $form_state->getTriggeringElement();
    if (isset($trigger['#name']) && $trigger['#name'] != 'save_continue') {
      $form_state->setRedirectUrl($entity->toUrl('collection'));
    }
    else {
      $form_state->setRedirectUrl($entity->toUrl());
    }
    return $status;
  }

}
