<?php

namespace Drupal\webform_composite\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Composite add and edit forms.
 */
class WebformCompositeForm extends EntityForm {

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs an WebformCompositeForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(QueryFactory $entity_query, AccountInterface $current_user, WebformElementManagerInterface $element_manager) {
    $this->entityQuery = $entity_query;
    $this->currentUser = $current_user;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('current_user'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $composite = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $composite->label(),
      '#description' => $this->t('Name for the Composite.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $composite->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$composite->isNew(),
    ];
    $form['description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Administrative description'),
      '#default_value' => $composite->getDescription(),
    ];
    $form['elements'] = [
      '#type' => 'webform_element_composite',
      '#title' => $this->t('Elements'),
      '#title_display' => $this->t('Invisible'),
      '#label' => $this->t('element'),
      '#labels' => $this->t('elements'),
      '#empty_items' => 0,
      '#header' => TRUE,
    ];

    // Load existing elements.
    $default_value = [];
    $elements = $composite->getElementsDecoded();
    foreach ($elements as $key => $properties) {
      $composite_element = WebformArrayHelper::removePrefix($properties);
      $default_value[$key] = $composite_element;
    }
    $form['elements']["#default_value"] = $default_value;

    $form['#attached']['library'][] = 'webform/webform.element.composite.admin';

    // You will need additional form elements for your custom properties.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for duplicate keys.
    $keys = [];
    $elements = $form_state->getValue('elements');
    foreach ($elements as $delta => $value) {
      $key = $value['key'];
      if (isset($keys[$key])) {
        $selector = 'elements][items][' . $delta . '][key';
        $message = $this->t('Duplicate key found. The %key key must only be assigned on one element.', ['%key' => $key]);
        $form_state->setErrorByName($selector, $message);
      }
      $keys[$key] = $key;

      $element = WebformArrayHelper::addPrefix($value);

      /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
      $element_plugin = $this->elementManager->getElementInstance($element);
      if ($element_plugin->hasProperty('options') && empty($element['#options'])) {
        $t_args = ['%title' => $element['#title']];
        $form_state->setErrorByName('elements][items][' . $delta . '][options', $this->t('Options for %title is required.', $t_args));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $composite = $this->entity;

    // Rekey and prefix elements into an render array.
    $elements = [];
    foreach ($form_state->getValue('elements') as $key => $element) {
      $element = array_filter($element);
      $elements[$key] = WebformArrayHelper::addPrefix($element);
    }

    // Update the values stored on the composite.
    $composite->set('elements', Yaml::encode($elements));
    $status = $composite->save();

    if ($status) {
      drupal_set_message($this->t('Composite %label has been saved.', [
        '%label' => $composite->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('Composite %label was not saved.', [
        '%label' => $composite->label(),
      ]), 'error');
    }

    $form_state->setRedirect('entity.webform_composite.list');
  }

  /**
   * Helper function to check whether an composite id is already in use.
   *
   * @return bool
   *   True if a composite exists with the given id. FALSE otherwise.
   */
  public function exists($id) {
    $entity = $this->entityQuery->get('webform_composite')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
