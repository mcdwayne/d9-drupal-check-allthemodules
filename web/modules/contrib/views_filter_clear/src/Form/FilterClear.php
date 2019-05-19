<?php

namespace Drupal\views_filter_clear\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Attaches 'clear' links to configured exposed filters.
 */
class FilterClear implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('request_stack'));
  }

  /**
   * Constructs the filter clear object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * Adds 'clear' links to views exposed forms.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addClearLinks(array &$form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    // @See \Drupal\views\Form\ViewsExposedForm::buildForm
    foreach ($view->display_handler->handlers as $type => $value) {
      /** @var \Drupal\views\Plugin\views\HandlerBase $handler */
      foreach ($view->$type as $id => $handler) {
        if ($handler->canExpose() && $handler->isExposed() && !empty($handler->options['expose']['add_clear_link'])) {
          // Pass the raw url object in so themes can place this elsewhere.
          $url = $this->getClearLinkUrl($id);
          $link = '<span class="views-filter-clear">' . Link::fromTextAndUrl($this->t('Clear'), $url)->toString() . '</span>';
          $form['#info'][$type . '-' . $id]['views_filter_clear'] = $url;

          // Attach link before the element.
          // @todo Figure out a better way than just a string as the prefix.
          if (isset($form[$id . '_wrapper'])) {
            // Account for wrapper divs on date range elements.
            $form[$id . '_wrapper']['#prefix'] = $link;
            $form[$id . '_wrapper']['#views_filter_clear'] = $url;
          }
          else {
            $form[$id]['#prefix'] = $link;
            $form[$id]['#views_filter_clear'] = $url;
          }
        }
      }
    }
  }

  /**
   * Creates the clear link for a given filter ID.
   */
  protected function getClearLinkUrl($id) {
    $url = Url::createFromRequest($this->requestStack->getCurrentRequest());

    // @todo this logic can be removed once the core issue is resolved.
    // @see https://www.drupal.org/node/2985400
    $url->setOption('query', $this->requestStack->getCurrentRequest()->query->all());

    $query = $url->getOption('query');

    // Unset the current ID to generate a link that will clear the value.
    unset($query[$id]);
    $url->setOption('query', $query);

    return $url;
  }

}
