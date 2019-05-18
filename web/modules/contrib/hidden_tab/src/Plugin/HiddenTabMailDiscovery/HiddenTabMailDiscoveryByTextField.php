<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabMailDiscovery;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hidden_tab\Entity\HiddenTabMailerInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabMailDiscoveryAnon;
use Drupal\hidden_tab\Plugable\MailDiscovery\HiddenTabMailDiscoveryInterface;
use Drupal\hidden_tab\Plugable\MailDiscovery\HiddenTabMailDiscoveryPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Finds email address by value of a preconfigured name of simple text field.
 *
 * @HiddenTabMailDiscoveryAnon(
 *   id = "hidden_tab_by_text_field"
 * )
 */
class HiddenTabMailDiscoveryByTextField extends HiddenTabMailDiscoveryPluginBase {

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::id()
   */
  protected $PID = 'hidden_tab_by_text_field';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::label()
   */
  protected $HTPLabel = 'By Text Field';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::description()
   */
  protected $HTPDescription = 'TODO';

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::weight()
   */
  protected $HTPWeight = 0;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = [];

  /**
   * To load list of fields.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              string $plugin_id,
                              $plugin_definition,
                              EntityStorageInterface $field_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldStorage = $field_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
        ->getStorage('field_storage_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function findMail(HiddenTabMailerInterface $config,
                           HiddenTabPageInterface $page,
                           EntityInterface $entity): array {
    if (!($entity instanceof FieldableEntityInterface)) {
      return [];
    }

    $field_name = $config->pluginConfiguration(HiddenTabMailDiscoveryInterface::PID, $this->id());
    // TODO FIX ME!!
    if (strpos($field_name, 'node.') === 0) {
      $field_name = substr($field_name, strlen('node.'));
    }
    if (!$entity->hasField($field_name)) {
      return [];
    }

    $field = $entity->get($field_name);
    if (!$field) {
      return [];
    }

    $value = $field->value;
    if (gettype($value) === 'string') {
      return [$value];
    }
    elseif (is_array($value) && isset($value[0])) {
      return $value;
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleConfigForm(array &$form, ?FormStateInterface $form_state, string $fs, $config) {
    $options = ['' => t('None')];
    foreach ($this->fieldStorage->loadByProperties(['type' => 'string',]) as $id => $info) {
      $options[$id] = $info->label();
    }
    $form[$fs][$this->formElementBase()] = [
      '#type' => 'select',
      '#title' => t('By Email Field'),
      '#description' => t('Email the link to the email stored in the selected text field.'),
      '#default_value' => $config,
      '#options' => $options,
    ];
  }

}
