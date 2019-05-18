<?php

namespace Drupal\dblog_persistent\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\dblog_persistent\DbLogPersistentStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DBLogPersistentTypeForm.
 */
class ChannelForm extends EntityForm {

  /**
   * @var \Drupal\dblog_persistent\DbLogPersistentStorageInterface
   */
  protected $storage;

  /**
   * Message types.
   *
   * @var string[]
   */
  private $types;

  /**
   * DBLogPersistentTypeForm constructor.
   *
   * @param \Drupal\dblog_persistent\DbLogPersistentStorageInterface $storage
   */
  public function __construct(DbLogPersistentStorageInterface $storage) {
    $this->storage = $storage;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('dblog_persistent.storage'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\dblog_persistent\Entity\ChannelInterface $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['info']['#markup'] = $this->t('Configure the filters that are applied to this log channel.');

    $form['types'] = [
      '#type' => 'select',
      '#title' => $this->t('Types'),
      '#multiple' => TRUE,
      '#size' => 6,
      '#options' => $this->getTypes() + array_combine($entity->getTypes(), $entity->getTypes()),
      '#default_value' => $entity->getTypes(),
      '#description' => $this->t('Capture events of specific types.'),
    ];

    $form['_types_extra'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional types'),
      '#description' => $this->t('The above field only shows types currently stored in the log, or previously selected. Use this comma-separated field to add more types.'),
    ];

    $form['levels'] = [
      '#type' => 'select',
      '#title' => $this->t('Severity'),
      '#multiple' => TRUE,
      '#size' => 8,
      '#options' => RfcLogLevel::getLevels(),
      '#default_value' => $entity->getLevels(),
      '#description' => $this->t('Capture events with specific severities.'),
    ];

    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#default_value' => $entity->getMessage(),
      '#description' => $this->t('Capture events whose message contains a substring. This is applied to the untranslated message.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity,
                                            array $form,
                                            FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    /** @var \Drupal\dblog_persistent\Entity\ChannelInterface $entity */
    // Remove non-entity fields.
    $entity->set('_types_extra', NULL);

    // If additional types were entered, add them now.
    if ($extra = trim($form_state->getValue('_types_extra'))) {
      $types = array_map('trim', preg_split('/,\s*/', $extra));
      $entity->set('types', $entity->getTypes() + array_combine($types, $types));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The persistent log channel %label was created.',
        [
          '%label' => $this->entity->label(),
        ]));
    }
    else {
      $this->messenger()->addStatus($this->t('The persistent log channel %label was updated.',
        [
          '%label' => $this->entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * Check if a given ID exists.
   *
   * @param string $id
   *
   * @return bool
   */
  public function exists(string $id): bool {
    try {
      return $this->entityTypeManager
               ->getStorage('dblog_persistent_channel')
               ->load($id) !== NULL;
    }
    catch (InvalidPluginDefinitionException $exception) {}
    catch (PluginNotFoundException $exception) {}
    return FALSE;
  }

  /**
   * List all available event types.
   *
   * @return string[]
   */
  protected function getTypes(): array {
    if ($this->types === NULL) {
      // Add all types in the core dblog.
      $types = _dblog_get_message_types();
      $this->types = array_combine($types, $types);
      // Add all types in the persistent dblog.
      $types = $this->storage->getTypes();
      $this->types += array_combine($types, $types);
      ksort($this->types);
    }

    return $this->types;
  }

}
