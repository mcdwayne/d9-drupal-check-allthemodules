<?php

namespace Drupal\third_party_services\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Html;
use Drupal\third_party_services\MediatorInterface;
use Drupal\third_party_services\Ajax\LocalStorageCommand;
use Drupal\third_party_services\Ajax\LocationReloadCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Main configuration form for allowing/denying sort of services.
 */
class ConfigurationForm extends FormBase {

  /**
   * Instance of the "MODULE.mediator" service.
   *
   * @var \Drupal\third_party_services\MediatorInterface
   */
  protected $mediator;
  /**
   * Instance of the "cache_tags.invalidator" service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * ConfigurationForm constructor.
   *
   * @param \Drupal\third_party_services\MediatorInterface $mediator
   *   Instance of the "MODULE.mediator" service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   Instance of the "cache_tags.invalidator" service.
   */
  public function __construct(MediatorInterface $mediator, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->mediator = $mediator;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('third_party_services.mediator'), $container->get('cache_tags.invalidator'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'third_party_services__configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL): array {
    $allowed = [
      '#type' => 'checkboxes',
      '#options' => [],
      '#default_value' => [],
      // Beautifier by "webform" module.
      // @see webform_preprocess_checkboxes()
      '#options_display' => 'two_columns',
    ];

    foreach ($this->mediator->loadTerms() as $term) {
      $uuid = $term->uuid();
      $label = $term->label();

      $allowed['#options'][$uuid] = $label;

      // @see template_preprocess_form_element()
      $allowed[$uuid]['#wrapper_attributes'] = [
        'class' => ['service-' . Html::getClass($label)],
      ];

      if ($this->mediator->isServiceAllowed($term, $user)) {
        $allowed['#default_value'][$uuid] = $uuid;
      }
    }

    $form['allowed'] = $allowed;

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => ['use-ajax'],
      ],
      '#ajax' => [
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'third_party_services/configuration_form';
    $form['#attributes']['class'][] = $this->getFormId();

    $form_state->setTemporaryValue('account', $user);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term_uuids = array_keys(array_filter($form_state->getValue('allowed')));

    $this->mediator->setAllowedServices($term_uuids, $form_state->getTemporaryValue('account'));
    $this->cacheTagsInvalidator->invalidateTags(array_keys($form['allowed']['#options']));

    $response = new AjaxResponse();
    $response->addCommand(new LocalStorageCommand('setItem', 'third_party_services_allowed', implode(',', $term_uuids)));
    // Page must be refreshed to fully remove all scripts injected.
    $response->addCommand(new LocationReloadCommand());
    $response->addCommand(new CloseModalDialogCommand());

    $form_state->setResponse($response);
  }

}
