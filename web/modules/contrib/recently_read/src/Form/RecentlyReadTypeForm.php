<?php

namespace Drupal\recently_read\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class RecentlyReadTypeForm.
 */
class RecentlyReadTypeForm extends EntityForm {

  /**
   * The routeMatch service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs RecentlyReadTypeForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   The routeMatch.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(CurrentRouteMatch $routeMatch, EntityTypeManager $entityTypeManager, MessengerInterface $messenger) {
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
  }

  /**
   * RecentlyReadTypeForm create function.
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $types = [];

    $entity = $this->entity;
    $route_parameters = $this->routeMatch->getParameters();
    $route_name = $route_parameters->get('recently_read_type')->id();
    if ($route_name == "node") {
      $types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    }
    if ($route_name == "taxonomy_term") {
      $types = Vocabulary::loadMultiple();
    }
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Config name'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
      '#disabled' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\recently_read\Entity\RecentlyReadType::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#default_value' => $entity->get('enabled'),
      '#title' => $this->t("Enabled"),
      '#ajax' => [
        'callback' => '::enabledCallback',
        'wrapper' => 'types',
      ],
    ];

    $options = [];
    foreach ($types as $typeId => $type) {
      $options[$typeId] = $type->label();
    }
    $form['types'] = [];
    if (count($options) > 0) {
      $form['types'] = [
        '#type' => 'checkboxes',
        '#options' => $options,
        '#default_value' => $entity->get('types'),
        '#title' => $this->t('Track'),
        '#required' => $form_state->getValue('enabled') !== NULL && $form_state->getValue('enabled') ? 1 : 0,
        '#prefix' => '<div id="types">',
        '#suffix' => '</div>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $recently_read_type = $this->entity;
    $status = $recently_read_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label recently read config.', [
          '%label' => $recently_read_type->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the recently read %label config.', [
          '%label' => $recently_read_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($recently_read_type->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function enabledCallback(array &$form, FormStateInterface $form_state) {
    return $form['types'];
  }

}
