<?php

namespace Drupal\term_split\EventSubscriber;

use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\hook_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the form alter event for the term overview form.
 */
class OverviewTerms implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherEvents::FORM_ALTER => 'alterForm',
    ];
  }

  /**
   * Alter form.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Form\FormAlterEvent $event
   *   The event.
   *
   * @SuppressWarnings(static)
   */
  public function alterForm(FormAlterEvent $event) {
    if ($event->getFormId() !== 'taxonomy_overview_terms') {
      return;
    }

    $form = &$event->getForm();

    $children = Element::getVisibleChildren($form['terms']);

    foreach ($children as $child) {
      /** @var \Drupal\taxonomy\Entity\Term $currentTerm */
      $currentTerm = $form['terms'][$child]['#term'];
      $routeName = 'entity.taxonomy_term.split_form';
      $routeParameters['taxonomy_term'] = $currentTerm->id();
      /** @var \Drupal\Core\Url $editUrl */
      $editUrl = $form['terms'][$child]['operations']['#links']['edit']['url'];
      $options = $editUrl->getOptions();
      $splitFormUrl = new Url($routeName, $routeParameters, $options);

      $form['terms'][$child]['operations']['#links']['split'] = [
        'title' => $this->t("Split"),
        'query' => [
          'destination' => '/admin/structure/taxonomy/manage/topics/overview',
        ],
        'url' => $splitFormUrl,
      ];
    }
  }

}
