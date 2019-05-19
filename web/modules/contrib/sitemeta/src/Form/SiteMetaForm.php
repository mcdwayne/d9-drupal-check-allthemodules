<?php

namespace Drupal\sitemeta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\sitemeta\SitemetaGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form controller for Site meta edit forms.
 *
 * @ingroup sitemeta
 */
class SiteMetaForm extends ContentEntityForm {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The sitemeta service.
   *
   * @var \Drupal\sitemeta\SitemetaGenerator
   */
  protected $sitemetaGenerator;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new SiteMetaForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\sitemeta\SitemetaGenerator $sitemetaGenerator
   *   The sitemeta service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, PathValidatorInterface $path_validator, AliasManagerInterface $alias_manager, MessengerInterface $messenger, SitemetaGenerator $sitemetaGenerator, RequestStack $request_stack) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->pathValidator = $path_validator;
    $this->aliasManager = $alias_manager;
    $this->messenger = $messenger;
    $this->sitemetaGenerator = $sitemetaGenerator;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('path.validator'),
      $container->get('path.alias_manager'),
      $container->get('messenger'),
      $container->get('sitemeta.sitemeta.generator'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\sitemeta\Entity\SiteMeta */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Existing system path'),
      '#default_value' => $entity->getPath(),
      '#maxlength' => 255,
      '#size' => 45,
      '#description' => $this->t('Specify the existing path you wish to add a sitemeta. For example: /node/28, /forum/1, /taxonomy/term/1, /taxonomy/term/%.'),
      '#field_prefix' => $this->requestStack->getCurrentRequest()->getHost(),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->getDescription(),
      '#description' => $this->t('Specify the description.'),
      '#maxlength' => 255,
    ];

    $form['keywords'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keywords'),
      '#default_value' => $entity->getKeywords(),
      '#description' => $this->t('Specify the keywords separated by comma.'),
      '#maxlength' => 255,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $path = $form_state->getValue('path');
    $path = $this->aliasManager->getPathByAlias($path);

    // Make sure that we save the real path and not an alias.
    $form_state->setValue('path', $path);

    if ($path[0] !== '/') {
      $form_state->setErrorByName('source', 'The path has to start with a slash.');
    }

    if (!$this->pathValidator->isValid(trim($path, '/')) && strpos($path, '%') == FALSE) {
      $form_state->setErrorByName('source', $this->t("Either the path '@link_path' is invalid or you do not have access to it.", ['@link_path' => $path]));
    }

    if ($this->sitemetaGenerator->getSiteMeta($path, $this->entity->langcode->value) && $form['#form_id'] != "sitemeta_edit_form") {
      $form_state->setErrorByName('source', 'Meta for this page and language already exists.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label Site meta.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Site meta.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.sitemeta.collection');
  }

}
