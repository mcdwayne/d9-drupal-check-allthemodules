<?php

namespace Drupal\micro_site\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\micro_site\SiteValidatorInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Form controller for Site edit forms.
 *
 * @ingroup micro_site
 */
class SiteForm extends ContentEntityForm {

  /**
   * The domain entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;


  /**
   * Constructs a DomainForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, EntityStorageInterface $storage, RendererInterface $renderer, AccountInterface $current_user) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->storage = $storage;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_type.manager')->getStorage('site'),
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\micro_site\Entity\Site */
    $form = parent::buildForm($form, $form_state);

    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $this->entity->getChangedTime(),
    ];

    // Created date.
    $form['created'] = [
      '#type' => 'hidden',
      '#default_value' => $this->entity->isNew() ? $this->time->getRequestTime() : $this->entity->getCreatedTime(),
    ];

    $form['#theme'] = ['site_edit_form'];

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }

    $form['type_url']['#disabled'] = !$this->entity->isNew() && !$this->currentUser->hasPermission('define site with full domain url');
    $form['site_url']['#disabled'] = !$this->entity->isNew() && !$this->currentUser->hasPermission('update site url');
    $form['user_id']['#disabled'] = !$this->entity->isNew() && !$this->currentUser->hasPermission('administer site entities');
    $form['site_scheme']['#disabled'] = !$this->entity->isNew() && !$this->currentUser->hasPermission('update site url');
    if (isset($form['site_shield']) && isset($form['shield_user'])) {
      $form['shield_user']['#states'] = [
        'visible' => [
          ':input[name="site_shield[value]"]' => ['checked' => TRUE],
        ],
      ];
    }
    if (isset($form['site_shield']) && isset($form['shield_password'])) {
      $form['shield_password']['#states'] = [
        'visible' => [
          ':input[name="site_shield[value]"]' => ['checked' => TRUE],
        ],
      ];
    }

    $form['status']['#disabled'] = $this->entity->isNew();
    $form['actions']['#weight'] = 200;
    $form['#attached']['library'][] = 'micro_site/site_form';


    // Build the advanced container
    $form['advanced']['#type'] = 'container';
    $form['advanced']['#attributes']['class'][] = 'entity-meta';
    if ($this->entity->isNew()) {
      $form['advanced']['#attributes']['class'][] = 'is-new';
    }

    $is_new = !$this->entity->isNew() ? format_date($this->entity->getChangedTime(), 'short') : t('Not saved yet');
    $created = !$this->entity->isNew() ? format_date($this->entity->getCreatedTime(), 'short') : '';
    $form['meta'] = [
      '#attributes' => ['class' => ['entity-meta__header']],
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -100,
      'published' => [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' => $this->entity->isPublished() ? t('Published') : t('Not published'),
        '#access' => !$this->entity->isNew(),
        '#attributes' => [
          'class' => ['entity-meta__title'],
        ],
      ],
      'changed' => [
        '#type' => 'item',
        '#wrapper_attributes' => ['class' => ['entity-meta__last-saved', 'container-inline']],
        '#markup' => '<h4 class="label inline">' . t('Last saved') . '</h4> ' . $is_new,
      ],
      'created' => [
        '#type' => 'item',
        '#wrapper_attributes' => ['class' => ['entity-meta__created', 'container-inline']],
        '#markup' => '<h4 class="label inline">' . t('Created on') . '</h4> ' . $created,
        '#access' => !$this->entity->isNew(),
      ],
      'author' => [
        '#type' => 'item',
        '#wrapper_attributes' => ['class' => ['author', 'container-inline']],
        '#markup' => '<h4 class="label inline">' . t('Owner') . '</h4> ' . $this->entity->getOwner()->getUsername(),
      ],
    ];

    $form['new_revision']['#group'] = 'meta';
    $form['revision_log_message']['#type'] = 'container';
    $form['revision_log_message']['#group'] = 'meta';


    // Site URL information for administrators.
    $form['url_info'] = [
      '#type' => 'details',
      '#title' => t('URL and scheme'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['site-form-url-info'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];

    if (isset($form['type_url'])) {
      $form['type_url']['#group'] = 'url_info';
    }

    if (isset($form['site_scheme'])) {
      $form['site_scheme']['#group'] = 'url_info';
    }

    if (isset($form['site_url'])) {
      $form['site_url']['#group'] = 'url_info';
    }

    // Site owner information for administrators.
    $form['owner'] = [
      '#type' => 'details',
      '#title' => t('Owner'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['site-form-owner'],
      ],
      '#weight' => 95,
      '#optional' => TRUE,
    ];

    if (isset($form['user_id'])) {
      $form['user_id']['#group'] = 'owner';
    }

    // Site logo and slogan information for administrators.
    $form['logo_slogan'] = [
      '#type' => 'details',
      '#title' => t('Logo and slogan'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['site-form-logo-slogan'],
      ],
      '#weight' => 100,
      '#optional' => TRUE,
    ];

    if (isset($form['logo'])) {
      $form['logo']['#group'] = 'logo_slogan';
    }

    if (isset($form['favicon'])) {
      $form['favicon']['#group'] = 'logo_slogan';
    }

    if (isset($form['slogan'])) {
      $form['slogan']['#group'] = 'logo_slogan';
    }

    // Piwik configuration.
    $form['piwik_info'] = [
      '#type' => 'details',
      '#title' => t('Piwik'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['site-form-piwik'],
      ],
      '#weight' => 105,
      '#optional' => TRUE,
    ];

    if (isset($form['piwik_id'])) {
      $form['piwik_id']['#group'] = 'piwik_info';
    }

    if (isset($form['piwik_url'])) {
      $form['piwik_url']['#group'] = 'piwik_info';
    }

    if (!(\Drupal::moduleHandler()->moduleExists('piwik') || \Drupal::moduleHandler()->moduleExists('matomo'))) {
      $form['piwik_info']['#access'] = FALSE;
    }

    // Site shield information for administrators.
    $form['shield_info'] = [
      '#type' => 'details',
      '#title' => t('Shield'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['site-form-shield-info'],
      ],
      '#weight' => 110,
      '#optional' => TRUE,
    ];

    if (isset($form['site_shield'])) {
      $form['site_shield']['#group'] = 'shield_info';
    }

    if (isset($form['shield_user'])) {
      $form['shield_user']['#group'] = 'shield_info';
    }

    if (isset($form['shield_password'])) {
      $form['shield_password']['#group'] = 'shield_info';
    }

    // Site shield information for administrators.
    $form['assets'] = [
      '#type' => 'details',
      '#title' => t('Assets'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['site-form-assets-info'],
      ],
      '#weight' => 120,
      '#optional' => TRUE,
    ];

    if (isset($form['css'])) {
      $form['css']['#group'] = 'assets';
      $form['css']['widget'][0]['value']['#attributes']['data-ace-mode'] = 'css';
      $form['css']['widget'][0]['value']['#prefix'] = '<div>';
      $form['css']['widget'][0]['value']['#suffix'] = '<div class="resizable"><div class="ace-editor"></div></div></div>';
      $form['#attached']['library'][] = 'micro_site/ace_editor';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->currentUser->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Site.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Site.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.site.canonical', ['site' => $entity->id()]);
  }

}
