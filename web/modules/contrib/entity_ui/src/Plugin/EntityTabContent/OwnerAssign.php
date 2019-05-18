<?php

namespace Drupal\entity_ui\Plugin\EntityTabContent;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\entity_ui\Plugin\EntityTabContentFormBase;
use Drupal\entity_ui\Plugin\EntityTabContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EntityTabContent(
 *   id = "owner_assign",
 *   label = @Translation("Assign entity owner"),
 *   description = @Translation("Provides a form to set the entity's owner."),
 * )
 */
class OwnerAssign extends EntityTabContentFormBase implements EntityTabContentInterface, ContainerFactoryPluginInterface, FormInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new OwnerAssign plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $bundle_info_service,
    FormBuilderInterface $form_builder,
    Connection $connection
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $bundle_info_service, $form_builder);

    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('form_builder'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function appliesToEntityType(EntityTypeInterface $entity_type, $definition) {
    return $entity_type->entityClassImplements(EntityOwnerInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $target_entity = NULL) {
    $form['owner_uid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('Username'),
      '#target_type' => 'user',
      '#selection_setttings' => [
        'include_anonymous' => FALSE,
      ],
      // Validation is done in static::validateConfigurationForm().
      '#validate_reference' => FALSE,
      '#size' => '20',
      '#maxlength' => '60',
      '#description' => t('The username of the user to which you would like to assign ownership.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Change owner'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $target_entity = $this->getTargetEntity($form_state);

    if ($target_entity->getOwnerId() == $form_state->getValue('owner_uid')) {
      $form_state->setErrorByName('owner_uid', t("The user '@username' is already the owner of this entity.", [
        '@username' => $target_entity->getOwner()->getAccountName(),
      ]));
      return;
    }

    $exists = (bool) $this->connection->queryRange('SELECT 1 FROM {users_field_data} WHERE uid = :uid AND default_langcode = 1', 0, 1, [
      ':uid' => $form_state->getValue('owner_uid')
    ])->fetchField();
    if (!$exists) {
      $form_state->setErrorByName('owner_uid', t('Enter a valid username.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $target_entity = $this->getTargetEntity($form_state);
    // Validation has already ensured this is a value user ID.
    $target_entity->setOwnerId($form_state->getValue('owner_uid'));
    $target_entity->save();

    \Drupal::messenger()->addMessage("The owner of the entity has been changed.");
  }

}
