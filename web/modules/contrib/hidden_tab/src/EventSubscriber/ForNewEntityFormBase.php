<?php

namespace Drupal\hidden_tab\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hidden_tab\Event\HiddenTabPageFormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * To help list all entities of a page, on it's edit form.
 */
abstract class ForNewEntityFormBase implements EventSubscriberInterface {

  /**
   * See RefrencerInterface for target entity.
   *
   * @var string
   */
  protected $currentlyTargetEntity = 'node';

  /**
   * Form element prefix.
   *
   * @var string
   */
  protected $prefix;

  /**
   * The entity type being created.
   *
   * @var string
   */
  protected $e_type;

  /**
   * Label of entity type being created.
   *
   * TODO translate properly.
   *
   * @var string
   */
  protected $label;

  /**
   * To translate.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $t;

  /**
   * Obvious.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * To create the new entity on form submit.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public static final function getSubscribedEvents() {
    return [
      HiddenTabPageFormEvent::EVENT_NAME => 'onEvent',
    ];
  }


  /**
   * ForNewEntityFormBase constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $t
   *   See $this->t.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   See $this->messenger.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   See $this->entityStorage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(TranslationInterface $t,
                              MessengerInterface $messenger,
                              EntityTypeManagerInterface $entity_type_manager) {
    $this->t = $t;
    $this->messenger = $messenger;
    $this->entityStorage = $entity_type_manager->getStorage($this->e_type);
  }

  /**
   * Handles the received event and delegates properly by form phase.
   *
   * @param \Drupal\hidden_tab\Event\HiddenTabPageFormEvent $event
   *   The received event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public final function onEvent(HiddenTabPageFormEvent $event) {
    switch ($event->phase) {
      case HiddenTabPageFormEvent::PHASE_FORM:
        $this->onForm($event);
        break;

      case HiddenTabPageFormEvent::PHASE_VALIDATE:
        $this->onValidate($event);
        break;

      case HiddenTabPageFormEvent::PHASE_SUBMIT:
        $this->onSave($event);
        break;
    }
  }

  /**
   * Handles when form is being created.
   *
   * @param \Drupal\hidden_tab\Event\HiddenTabPageFormEvent $event
   *   The received event.
   */
  private final function onForm(HiddenTabPageFormEvent $event) {
    $form[$this->prefix . 'create'] = [
      '#type' => 'checkbox',
      '#title' => t('Create a @type for the page', [
        '@type' => $this->label,
      ]),
      '#default_value' => !$event->isEdit(),
    ];
    $form += $this->addForm($event);
    $event->form[$this->prefix] = [
      '#type' => 'details',
      '#title' => $this->label,
      '#open' => FALSE,
    ];
    $event->form[$this->prefix][$this->prefix . 'form'] = $form;
  }

  /**
   * Handles when form is being validated.
   *
   * @param \Drupal\hidden_tab\Event\HiddenTabPageFormEvent $event
   *   The received event.
   */
  private function onValidate(HiddenTabPageFormEvent $event) {
    if (!$event->get($this->prefix, 'create')) {
      return;
    }
    $event->set($this->prefix, 'target_hidden_tab_page', $event->page->id());
    $this->onValidate0($event);
  }

  /**
   * Handles when form is being saved.
   *
   * @param \Drupal\hidden_tab\Event\HiddenTabPageFormEvent $event
   *   The received event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private final function onSave(HiddenTabPageFormEvent $event) {
    if (!$event->get($this->prefix, 'create')) {
      return;
    }

    $v = [
        'target_hidden_tab_page' => $event->page->id(),
      ] + $this->onSave0($event);
    $e = $this->entityStorage->create($v);
    $e->save();
    $this->messenger->addStatus($this->t->translate('New @label created', [
      '@label' => $this->label,
    ]));
  }

  /**
   * Create the form.
   *
   * @param \Drupal\hidden_tab\Event\HiddenTabPageFormEvent $event
   *   The event happening.
   *
   * @return array
   *   The form
   */
  protected abstract function addForm(HiddenTabPageFormEvent $event): array;

  /**
   * Handles when form is being validated.
   *
   * @param \Drupal\hidden_tab\Event\HiddenTabPageFormEvent $event
   *   The received event.
   */
  protected abstract function onValidate0(HiddenTabPageFormEvent $event);

  /**
   * Create entity values array to create the entity.
   *
   * @param \Drupal\hidden_tab\Event\HiddenTabPageFormEvent $event
   *   The received event.
   *
   * @return array
   *   Entity values array to save.
   */
  protected abstract function onSave0(HiddenTabPageFormEvent $event): array;

}
