<?php

namespace Drupal\media_entity_icon\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\media_entity\MediaInterface;
use Drupal\media_entity_icon\SvgManagerInterface;
use Drupal\media_entity_icon\SvgTypeManagerInterface;

/**
 * Related icons form.
 *
 * @package Drupal\media_entity_icon\Form
 */
class RelatedIconsForm extends FormBase {

  /**
   * Current user.
   *
   * @var \Drupal\media_entity_icon\SvgManagerInterface
   */
  protected $currentUser;

  /**
   * Current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SVG manager service.
   *
   * @var \Drupal\media_entity_icon\SvgManagerInterface
   */
  protected $svgManager;

  /**
   * SVG type manager service.
   *
   * @var \Drupal\media_entity_icon\SvgTypeManagerInterface
   */
  protected $svgTypeManager;

  /**
   * RelatedIconsForm constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   Current path.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\media_entity_icon\SvgManagerInterface $svg_manager
   *   SVG manager service.
   * @param \Drupal\media_entity_icon\SvgTypeManagerInterface $svg_type_manager
   *   SVG type manager service.
   */
  public function __construct(
    AccountInterface $current_user,
    CurrentPathStack $current_path,
    EntityTypeManagerInterface $entity_type_manager,
    SvgManagerInterface $svg_manager,
    SvgTypeManagerInterface $svg_type_manager) {
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
    $this->entityTypeManager = $entity_type_manager;
    $this->svgManager = $svg_manager;
    $this->svgTypeManager = $svg_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('path.current'),
      $container->get('entity_type.manager'),
      $container->get('media_entity_icon.manager.svg'),
      $container->get('media_entity_icon.manager.svg.type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'related_icons';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, MediaInterface $media = NULL) {
    $form['#attached']['library'][] = 'media_entity_icon/related_icons';

    // Prepare the data.
    /** @var \Drupal\media_entity\MediaTypeInterface $media_type */
    $media_type = $media->getType();
    $media_path = $media_type->getField($media, 'path');
    $media_realpath = $media_type->getField($media, 'realpath');
    $media_config = $media_type->getConfiguration();
    $existing_icons = $media_type->getExistingIcons($media);
    $source_icons = $this->svgManager->extractIconIds($media_realpath);
    $current_path = $this->currentPath->getPath();

    // Gather the icons.
    $obsolete_icons = !empty($existing_icons) ? array_diff_key($existing_icons, $source_icons) : [];
    $missing_icons = !empty($existing_icons) ? array_diff_key($source_icons, $existing_icons) : $source_icons;
    $existing_icons = !empty($obsolete_icons) ? array_diff_key($existing_icons, $obsolete_icons) : $existing_icons;
    $count = [
      'missing' => count($missing_icons),
      'obsolete' => count($obsolete_icons),
      'existing' => count($existing_icons),
    ];

    // Prepare layout.
    $statuses = [
      'existing' => 'update_thumbnail',
      'missing' => 'create_icon',
      'obsolete' => 'migrate_icon',
    ];
    foreach ($statuses as $status => $action) {
      $form[$status . '_icons'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['panel'],
        ],
        '#prefix' => '<div class="layout-column layout-column--half">',
        '#suffix' => '</div>',
      ];

      $form[$status . '_icons']['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => ucfirst($status) . ' icons (' . $count[$status] . ')',
        '#attributes' => [
          'class' => ['panel__title', 'icon-status', 'icon-status__' . $status],
        ],
      ];

      $form[$status . '_icons'][$action] = [
        '#type' => 'tableselect',
        '#header' => [],
        '#empty' => $this->t('No icon belong to this status.'),
        '#options' => [],
        '#attributes' => [
          'class' => ['panel__content'],
        ],
      ];
    }

    // Missing icons.
    $form['missing_icons'][$statuses['missing']]['#header'] = [
      'preview' => $this->t('Preview'),
      'name' => $this->t('Name'),
      'actions' => $this->t('Actions'),
    ];

    foreach ($missing_icons as $icon_id) {
      $icon_size = $this->svgManager->getIconSize($media_realpath, $icon_id);
      $preview = [
        '#theme' => 'media_icon_svg_formatter',
        '#icons_path' => $media_path,
        '#icon_class' => $icon_id,
        '#attributes' => $icon_size,
      ];

      $actions = ['#type' => 'actions'];
      if (!empty($media_config['target_bundle'])) {
        $actions['create'] = [
          '#type' => 'link',
          '#title' => $this->t('Create manually'),
          '#url' => Url::fromRoute(
            'entity.media.add_form',
            ['media_bundle' => $media_config['target_bundle']],
            [
              'query' => [
                'source_id' => $media->id(),
                'icon_id' => $icon_id,
                'destination' => $current_path,
              ],
            ]
          ),
          '#attributes' => [
            'class' => ['button'],
          ],
        ];
      }

      $row = [
        'preview' => render($preview),
        'name' => $icon_id,
        'actions' => render($actions),
      ];

      $form['missing_icons'][$statuses['missing']]['#options'][$icon_id] = $row;
    }

    if (!empty($missing_icons)) {
      $form['missing_icons']['actions']['#type'] = 'actions';
      $form['missing_icons']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Create icon(s)'),
        '#submit' => ['::createIcon'],
      ];
    }

    // Existing icons.
    $form['existing_icons'][$statuses['existing']]['#header'] = [
      'preview' => $this->t('Preview'),
      'thumbnail' => $this->t('Thumbnail'),
      'name' => $this->t('Name'),
      'actions' => $this->t('Actions'),
    ];

    foreach ($existing_icons as $icon_id => $icon_medias) {
      $icon_size = $this->svgManager->getIconSize($media_realpath, $icon_id);

      /** @var \Drupal\media_entity\MediaInterface $icon_media */
      foreach ($icon_medias as $icon_media) {
        $row = [
          'preview' => [
            'data' => [
              '#theme' => 'media_icon_svg_formatter',
              '#icons_path' => $media_path,
              '#icon_class' => $icon_id,
              '#attributes' => $icon_size,
            ],
          ],
          'thumbnail' => [
            'data' => [
              '#theme' => 'image_style',
              '#style_name' => 'thumbnail',
              '#uri' => $icon_media->get('thumbnail')->entity->get('uri')->value,
            ],
          ],
          'name' => [
            'data' => [
              '#type' => 'link',
              '#title' => $icon_media->label(),
              '#url' => Url::fromRoute(
                'entity.media.canonical',
                ['media' => $icon_media->id()]
              ),
            ],
          ],
          'actions' => [
            'data' => [
              '#type' => 'actions',
              'extra' => [
                '#type' => 'dropbutton',
                '#links' => [],
              ],
            ],
          ],
        ];

        if ($this->currentUser->hasPermission('edit media')) {
          $row['actions']['data']['extra']['#links']['edit'] = [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute(
              'entity.media.edit_form',
              ['media' => $icon_media->id()],
              ['query' => ['destination' => $current_path]]
            ),
          ];
        }
        if ($this->currentUser->hasPermission('delete media')) {
          $row['actions']['data']['extra']['#links']['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute(
              'entity.media.delete_form',
              ['media' => $icon_media->id()],
              ['query' => ['destination' => $current_path]]
            ),
          ];
        }

        $form['existing_icons'][$statuses['existing']]['#options'][$icon_media->id()] = $row;
      }
    }

    if (!empty($existing_icons)) {
      $form['existing_icons']['actions']['#type'] = 'actions';
      $form['existing_icons']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update thumbnail(s)'),
        '#submit' => ['::updateThumbnail'],
      ];
    }

    // Obsolete icons.
    $form['obsolete_icons'][$statuses['obsolete']]['#header'] = [
      'thumbnail' => $this->t('Thumbnail'),
      'name' => $this->t('Name'),
      'actions' => $this->t('Actions'),
    ];

    foreach ($obsolete_icons as $icon_id => $icon_medias) {
      foreach ($icon_medias as $icon_media) {
        $row = [
          'thumbnail' => [
            'data' => [
              '#theme' => 'image_style',
              '#style_name' => 'thumbnail',
              '#uri' => $icon_media->get('thumbnail')->entity->get('uri')->value,
            ],
          ],
          'name' => [
            'data' => [
              '#type' => 'link',
              '#title' => $icon_media->label(),
              '#url' => Url::fromRoute(
                'entity.media.canonical',
                ['media' => $icon_media->id()]
              ),
            ],
          ],
          'actions' => [
            'data' => [
              '#type' => 'actions',
              'extra' => [
                '#type' => 'dropbutton',
                '#links' => [],
              ],
            ],
          ],
        ];

        if ($this->currentUser->hasPermission('edit media')) {
          $row['actions']['data']['extra']['#links']['edit'] = [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute(
              'entity.media.edit_form',
              ['media' => $icon_media->id()],
              ['query' => ['destination' => $current_path]]
            ),
          ];
        }
        if ($this->currentUser->hasPermission('delete media')) {
          $row['actions']['data']['extra']['#links']['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute(
              'entity.media.delete_form',
              ['media' => $icon_media->id()],
              ['query' => ['destination' => $current_path]]
            ),
          ];
        }

        $form['obsolete_icons'][$statuses['obsolete']]['#options'][$icon_media->id()] = $row;
      }
    }

    if (!empty($obsolete_icons)) {
      $form['obsolete_icons']['migrate_target_icon_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Target icon id'),
        '#options' => $source_icons,
      ];

      $form['obsolete_icons']['actions']['#type'] = 'actions';
      $form['obsolete_icons']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Migrate icon(s)'),
        '#submit' => ['::migrateIcon'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getSubmitHandlers() as $submit_handler) {
      switch ($submit_handler) {
        case '::createIcon':
          if (!$form_state->getValue('create_icon') || empty(array_filter($form_state->getValue('create_icon')))) {
            $form_state->setErrorByName('create_icon', $this->t('You need to select at least one icon to create.'));
          }
          break;

        case '::migrateIcon':
          if (!$form_state->getValue('migrate_icon') || empty(array_filter($form_state->getValue('migrate_icon')))) {
            $form_state->setErrorByName('migrate_icon', $this->t('You need to select at least one icon to migrate.'));
          }
          if (!$form_state->getValue('migrate_target_icon_id') || empty($form_state->getValue('migrate_target_icon_id'))) {
            $form_state->setErrorByName('migrate_icon', $this->t('You need to select a target icon id.'));
          }
          break;

        case '::updateThumbnail':
          if (!$form_state->getValue('update_thumbnail') || empty(array_filter($form_state->getValue('update_thumbnail')))) {
            $form_state->setErrorByName('update_thumbnail', $this->t('You need to select at least one icon to update.'));
          }
          break;

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No primary button is available.
  }

  /**
   * Create selected missing icons.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state instance.
   */
  public function createIcon(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();

    /** @var \Drupal\media_entity\MediaInterface $media */
    $media = $build_info['args'][0];
    /** @var \Drupal\media_entity\MediaTypeInterface $media_type */
    $media_type = $media->getType();

    $icon_ids = array_filter($form_state->getValue('create_icon'));
    $icon_ids = array_combine($icon_ids, $icon_ids);

    $media_type->updateIcons($media, $icon_ids);
  }

  /**
   * Migrate selected obsolete icons.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state instance.
   */
  public function migrateIcon(array &$form, FormStateInterface $form_state) {
    $target_icon_id = $form_state->getValue('migrate_target_icon_id');

    $media_ids = array_filter($form_state->getValue('migrate_icon'));
    $media_ids = array_combine($media_ids, $media_ids);
    $medias = $this->entityTypeManager
      ->getStorage('media')
      ->loadMultiple($media_ids);

    $output = [];
    /** @var \Drupal\media_entity\MediaInterface $media */
    foreach ($medias as $media) {
      /** @var \Drupal\media_entity\MediaTypeInterface $media_type */
      $media_type = $media->getType();
      $thumbnail_uri = $media_type->createThumbnail($media, TRUE);

      $id_field = $this->svgTypeManager
        ->getIconBundleIdField($media->bundle());

      $media->set($id_field, $target_icon_id);
      if (!empty($thumbnail_uri)) {
        $media->set('thumbnail', $thumbnail_uri);
      }
      $success = $media->save();

      $output[$success ? 'success' : 'error'][$media->id()] = $media->label();
    }

    if (!empty($output['success'])) {
      drupal_set_message($this->t('Icons successfully migrated: @icon_ids.', ['@icon_ids' => implode(', ', $output['success'])]));
    }
    if (!empty($output['error'])) {
      drupal_set_message($this->t('Icons failed migration: @icon_ids.', ['@icon_ids' => implode(', ', $output['error'])]));
    }
  }

  /**
   * Update thumbnails for selected icons.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state instance.
   */
  public function updateThumbnail(array &$form, FormStateInterface $form_state) {
    $media_ids = array_filter($form_state->getValue('update_thumbnail'));
    $media_ids = array_combine($media_ids, $media_ids);
    $medias = $this->entityTypeManager
      ->getStorage('media')
      ->loadMultiple($media_ids);

    $output = [];
    /** @var \Drupal\media_entity\MediaInterface $media */
    foreach ($medias as $media) {
      /** @var \Drupal\media_entity\MediaTypeInterface $media_type */
      $media_type = $media->getType();
      $thumbnail_uri = $media_type->createThumbnail($media, TRUE);
      if (!empty($media->get('thumbnail')) && $media->get('thumbnail')->entity->getFileUri() !== $thumbnail_uri) {
        $media->set('thumbnail', $thumbnail_uri);
        $media->save();
      }

      $output[!empty($thumbnail_uri) ? 'success' : 'error'][$media->id()] = $media->label();
    }

    if (!empty($output['success'])) {
      drupal_set_message($this->t('SvgIcon thumbnails successfully updated: @icon_ids.', ['@icon_ids' => implode(', ', $output['success'])]));
    }
    if (!empty($output['error'])) {
      drupal_set_message($this->t('SvgIcon thumbnails failed update: @icon_ids.', ['@icon_ids' => implode(', ', $output['error'])]));
    }
  }

  /**
   * Validate access to the form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   * @param \Drupal\media_entity\MediaInterface $media
   *   Current media entity.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Whether the user can access or not to the form.
   */
  public function relatedIconsAccess(AccountInterface $account, MediaInterface $media) {
    return AccessResult::allowedIf(
      $account->hasPermission('access related icons')
      && 'svg_sprite' === $media->getType()->getPluginId()
    );
  }

}
