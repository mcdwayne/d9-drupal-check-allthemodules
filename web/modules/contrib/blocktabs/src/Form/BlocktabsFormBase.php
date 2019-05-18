<?php

namespace Drupal\blocktabs\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for blocktabs add and edit forms.
 */
abstract class BlocktabsFormBase extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\blocktabs\BlocktabsInterface
   */
  protected $entity;

  /**
   * The block tabs entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Constructs a base class for blocktabs add and edit forms.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The block tabs entity storage.
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('blocktabs')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Blocktabs name'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this->entityStorage, 'load'],
      ],
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
    ];

    $form['#tree'] = FALSE;
    $form['settings'] = [
      '#type' => 'details',
      '#title' => t('Tabs settings'),
    ];

    $default_event = $this->entity->getEvent();
    if (empty($default_event)) {
      $default_event = 'mouseover';
    }
    $form['settings']['event'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select an event'),
      '#default_value' => $default_event,
      '#options' => [
        'mouseover' => $this->t('Mouseover'),
        'click' => $this->t('Click'),
      ],
    ];

    $default_style = $this->entity->getStyle();
    if (empty($default_style)) {
      $default_style = 'default';
    }
    $form['settings']['style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Style'),
      '#default_value' => $default_style,
      '#options' => [
        'default' => $this->t('Default tabs'),
        'vertical' => $this->t('Vertical tabs'),
        'accordion' => $this->t('Accordion'),
      ],
    ];
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->urlInfo('edit-form'));
  }

}
