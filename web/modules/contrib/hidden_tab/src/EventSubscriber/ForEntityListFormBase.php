<?php

namespace Drupal\hidden_tab\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hidden_tab\Event\HiddenTabPageFormEvent;
use Drupal\hidden_tab\FUtility;
use Drupal\hidden_tab\Utility;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class ForEntityListFormBase implements EventSubscriberInterface {

  /**
   * Form element prefixes.
   *
   * @var string
   */
  protected $prefix;

  /**
   * Entity type of entity being listed.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Label of entity being listed.
   *
   * TODO translate properly.
   *
   * @var string
   */
  protected $label;

  /**
   * Translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $t;

  /**
   * To load the list.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Current Url by user.
   *
   * @var string
   */
  protected $currentUrl;

  /**
   * {@inheritdoc}
   */
  public static final function getSubscribedEvents() {
    return [
      HiddenTabPageFormEvent::EVENT_NAME => 'onEvent',
    ];
  }

  /**
   * ForEntityListFormBase constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $t
   *   See $this->t.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $em
   *   See $this->entityStorage.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   See $this->currentUrl.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(TranslationInterface $t,
                              EntityTypeManagerInterface $em,
                              RequestStack $request_stack) {
    $this->t = $t;
    $this->entityStorage = $em->getStorage($this->entityType);
    $this->currentUrl = Utility::currentUrl($request_stack);
  }

  /**
   * Build the custom header items.
   *
   * @return array
   *   Custom header options.
   */
  protected abstract function header(): array;

  /**
   * Build the custom elements of row .
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to build row for.
   *
   * @return array
   *   Custom elements of row .
   */
  protected abstract function row(EntityInterface $entity): array;

  /**
   * Handles the received event by hidden tab page form.
   *
   * @param \Drupal\hidden_tab\Event\HiddenTabPageFormEvent $event
   *   The received event.
   * \Exception\PluginNotFoundException
   */
  public final function onEvent(HiddenTabPageFormEvent $event) {
    if (!$event->isEdit() || $event->phase !== HiddenTabPageFormEvent::PHASE_FORM) {
      return;
    }

    $ret = [
      '#type' => 'table',
      '#header' => $this->header() + [
          'operations' => $this->t->translate('Operations'),
        ],
      '#empty' => $this->t->translate('There are no items yet.'),
    ];

    $ids = [];
    try {
      $entities = $this->entityStorage
        ->loadByProperties([
          'target_hidden_tab_page' => $event->page->id(),
        ]);
      foreach ($entities as $id => $entity) {
        $ret[$id] = FUtility::renderHelper($this->row($entity))
          + FUtility::operationsHelper($entity, $this->currentUrl);
      }
    }
    catch (\Throwable $error) {
      Utility::renderLog($error, $this->entityType, 'hidden_tab_page_id=' . $event->page->id());
      $ret['#empty'] = [
        '#type' => 'table',
        '#empty' => $this->t->translate('@type: Error loading list of entities.', [
          '@type' => $this->entityType,
        ]),
      ];
    }

    $ret += $ids;
    $event->form[$this->prefix . 'entity_fieldset'] = [
      '#type' => 'details',
      '#open' => TRUE,
      // TODO translate properly.
      '#title' => $this->t->translate($this->label),
    ];
    $event->form[$this->prefix . 'entity_fieldset'][$this->prefix . 'entities'] = $ret;
  }

}
