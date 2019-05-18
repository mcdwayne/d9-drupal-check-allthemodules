<?php

namespace Drupal\flush_single_image\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\flush_single_image\FlushSingleImageInterface;

/**
 * Class FlushSingleImageForm.
 */
class FlushSingleImageForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The filesystem helper.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The drupal messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The single image flusher service.
   *
   * @var \Drupal\flush_single_image\FlushSingleImage
   */
  protected $flushSingleImage;

  /**
   * Constructs a new FlushSingleImageForm object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, MessengerInterface $messenger, FlushSingleImageInterface $flush_single_image) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->flushSingleImage = $flush_single_image;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('messenger'),
      $container->get('flush_single_image')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flush_single_image_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File URI'),
      '#description' => $this->t('The image URI to flush image styles for. This can also be a relative path in which case the ' . file_default_scheme() . ':// scheme will be used.'),
    ];

    $form['check'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Check Styles'),
    ];

    $form['check']['description'] = [
      '#markup' => '<p class="description">Click "Check Styles" to check which styles have images cached for the provided image path.</p>',
      '#prefix' => '<div id="flush-single-image-description">',
      '#suffix' => '</div>',
      '#title' => $this->t('Check Styles'),
    ];

    $form['check']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Check Styles'),
      '#ajax' => [
        'callback' => '::checkStyles',
        'wrapper' => 'flush-single-image-description',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Checking styles...'),
        ],
      ],
      '#attributes' => ['class' => ['form-item']],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Flush'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (!$form_state->getValue('path')) {
      $form_state->setError($form['path'], $this->t('@name field is required.', ['@name' => $form['path']['#title']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $paths = $this->flushSingleImage->flush($form_state->getValue('path'));

    foreach ($paths as $path) {
      $this->messenger->addMessage(t('Flushed @path', ['@path' => $path]));
    }

    $this->messenger->addMessage(t('Flushed all images for @path', ['@path' => $form_state->getValue('path')]));

    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback to check which styles an image has cached.
   */
  public static function checkStyles(array &$form, FormStateInterface $form_state) {

    $paths = \Drupal::service('flush_single_image')->flush($form_state->getValue('path'));

    if ($paths) {
      $element = [
        '#theme' => 'item_list',
        '#title' => t('Styled Images for @path', ['@path' => $form_state->getValue('path')]),
        '#prefix' => '<div id="flush-single-image-description">',
        '#suffix' => '</div>',
        '#items' => [],
      ];
      foreach ($paths as $path) {
        $element['#items'][] = ['#markup' => $path];
      }
    }
    else {
      $element = [
        '#markup' => '<p class="description">There are no image styles cached for this image.</p>',
        '#prefix' => '<div id="flush-single-image-description">',
        '#suffix' => '</div>',
        '#title' => t('Check Styles'),
      ];
    }

    return $element;
  }

}
