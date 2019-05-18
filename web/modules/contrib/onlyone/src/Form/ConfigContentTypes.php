<?php

namespace Drupal\onlyone\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\onlyone\OnlyOneEvents;
use Drupal\onlyone\OnlyOneInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ConfigContentTypes.
 */
class ConfigContentTypes extends ConfigFormBase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispacher;

  /**
   * The onlyone service.
   *
   * @var \Drupal\onlyone\OnlyOneInterface
   */
  protected $onlyone;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispacher
   *   The event dispacher.
   * @param \Drupal\onlyone\OnlyOneInterface $onlyone
   *   The onlyone service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EventDispatcherInterface $event_dispacher, OnlyOneInterface $onlyone) {
    parent::__construct($config_factory);

    $this->eventDispacher = $event_dispacher;
    $this->onlyone = $onlyone;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'), $container->get('event_dispatcher'), $container->get('onlyone')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onlyone_config_content_types';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['onlyone.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Getting the available content types.
    $available_content_types = $this->onlyone->getAvailableContentTypesForPrint();
    // Getting the number of content types.
    $cant_available_content_types = count($available_content_types);
    if ($cant_available_content_types) {
      // The details form element with the available content types.
      $form['available_content_type'] = [
        '#type' => 'details',
        '#title' => $this->t("Content types available to have Only One content"),
        '#open' => TRUE,
      ];
      // All the available content types.
      $form['available_content_type']['onlyone_node_types'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Configure these content types to have Only One content per language:'),
        '#options' => $available_content_types,
        '#default_value' => $this->config('onlyone.settings')->get('onlyone_node_types'),
        '#description' => $this->t('The selected content types will allow Only One content per language.'),
      ];
    }
    // Getting the non-available content types.
    $not_available_content_types = $this->onlyone->getNotAvailableContentTypesForPrint();
    // Getting the number of not availables content types.
    $cant_not_available_content_types = count($not_available_content_types);
    // If all the content types are available we don't need to show the element.
    if ($cant_not_available_content_types) {
      $collapsed = $cant_available_content_types ? FALSE : TRUE;
      // The details form element with the unavailable content types.
      $form['not_available_content_type'] = [
        '#type' => 'details',
        '#title' => $this->t('Content types not available to have Only One content per language'),
        '#description' => $this->t('Content types which have more than one content in at least one language:'),
        '#open' => $collapsed,
        '#attributes' => [
          'class' => [
            'details-description--not-available-content-types',
          ],
        ],
      ];
      // Showing all the not availables content types.
      foreach ($not_available_content_types as $key => $value) {
        $form['not_available_content_type'][$key] = [
          '#type' => 'item',
          '#markup' => $value,
        ];
      }
      // Attaching the css file.
      $form['#attached']['library'] = [
        'onlyone/admin_settings',
      ];
    }

    if (!$cant_available_content_types && !$cant_not_available_content_types) {
      $form['not_available_content_type'] = [
        '#markup' => $this->t('There are not content types on this site, go to the <a href=":add-content-type">Add content type</a> page to create one.', [':add-content-type' => Url::fromRoute('node.type_add')->toString()]),
      ];
    }

    // Show the submit button if there is availables content types.
    if ($cant_available_content_types) {
      return parent::buildForm($form, $form_state);
    }
    else {
      $form = parent::buildForm($form, $form_state);
      unset($form['actions']);
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Cleaning the not checked content types from the selected checkboxes.
    $content_types_checked = array_values(array_diff($form_state->getValue('onlyone_node_types'), ['0']));
    // Getting the configured content types.
    $onlyone_content_types = $this->config('onlyone.settings')->get('onlyone_node_types');

    // Checking if we have any change in the configured content types.
    if ($content_types_checked == array_values($onlyone_content_types)) {
      $this->messenger()->addWarning($this->t("You don't have changed the configured content types."));
    }
    else {
      // Saving the configuration.
      $this->config('onlyone.settings')
        ->set('onlyone_node_types', $content_types_checked)
        ->save();
      // Calling parent method.
      parent::submitForm($form, $form_state);

      // Dispatching the event related to a change in the configured content
      // types.
      $this->eventDispacher->dispatch(OnlyOneEvents::CONTENT_TYPES_UPDATED);
    }
  }

}
