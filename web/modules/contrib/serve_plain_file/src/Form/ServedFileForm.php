<?php

namespace Drupal\serve_plain_file\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Url;
use Drupal\serve_plain_file\Entity\ServedFile;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Served File add and edit forms.
 *
 * @property \Drupal\serve_plain_file\Entity\ServedFileInterface $entity
 */
class ServedFileForm extends EntityForm {

  /**
   * Route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Drupal root.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * Constructs an Served File object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routeBuilder
   *   The route builder.
   * @param string $appRoot
   *   Drupal root.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RouteBuilderInterface $routeBuilder, $appRoot) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeBuilder = $routeBuilder;
    $this->appRoot = $appRoot;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('router.builder'),
      $container->get('app.root')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $served_file = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $served_file->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $served_file->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$served_file->isNew(),
    ];

    $form['max_age'] = [
      '#type' => 'number',
      '#title' => $this->t('Max age'),
      '#description' => $this->t('The cache max age for the file.'),
      '#default_value' => $served_file->getFileMaxAge(),
      '#min' => 0,
      '#step' => 1,
    ];

    $allowed_mime_types = (array) $this->config('serve_plain_file.settings')->get('allowed_mime_types');
    $allowed_mime_types = array_merge($allowed_mime_types, [ServedFile::DEFAULT_MIME_TYPE => ServedFile::DEFAULT_MIME_TYPE]);
    $allowed_mime_types = array_unique($allowed_mime_types);

    $form['mime_type'] = [
      '#type' => 'select',
      '#title' => $this->t('MIME-Type'),
      '#description' => $this->t('The MIME-Type will be sent in the Content-Type header.'),
      '#default_value' => !empty($served_file->getMimeType()) ? $served_file->getMimeType() : ServedFile::DEFAULT_MIME_TYPE,
      '#options' => $allowed_mime_types,
    ];

    $path = 'ads.txt';
    $url = Url::fromUri('base:' . $path);
    $url->setAbsolute();
    $link = $url->toString();

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#maxlength' => 255,
      '#default_value' => $served_file->getPath(),
      '#description' => $this->t("Path to the file (e.g: %path will appear on %link).", ['%path' => 'ads.txt', '%link' => $link]),
      '#required' => TRUE,
    ];

    $form['content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Content'),
      '#default_value' => $served_file->getContent(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $served_file = $this->entity;

    $path = $form_state->getValue('path');
    $served_file->set('path', ltrim($path, '/'));

    try {
      $served_file->save();

      drupal_set_message($this->t('Saved the %label file.', [
        '%label' => $served_file->label(),
      ]));

      $form_state->setRedirect('entity.served_file.collection');

      // Rebuild dynamic routes so that the path is available.
      $this->routeBuilder->rebuild();
    }
    catch (EntityStorageException $e) {
      drupal_set_message($this->t('The %label file was not saved.', [
        '%label' => $served_file->label(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $path = $form_state->getValue('path');
    $path = ltrim($path, '/');
    $file = $this->appRoot . '/' . $path;

    if (file_exists($file)) {
      $form_state->setError($form['path'], $this->t('Invalid path, there is a real file at that location.'));
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = $this->entityTypeManager->getStorage('served_file');
    /** @var \Drupal\serve_plain_file\Entity\ServedFileInterface[] $served_files */
    $served_files = $storage->loadByProperties(['path' => $path]);

    if (!empty($this->entity->id())) {
      unset($served_files[$this->entity->id()]);
    }

    if (!empty($served_files)) {
      $form_state->setError($form['path'], $this->t('A served file with that path already exists.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Checks whether the entity exists.
   *
   * @param int $id
   *   Entity id.
   *
   * @return bool
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('served_file')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
