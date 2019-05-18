<?php

namespace Drupal\entity_gallery\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains a form for switching the view mode of an entity gallery during
 * preview.
 */
class EntityGalleryPreviewForm extends FormBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'), $container->get('config.factory'));
  }

  /**
   * Constructs a new EntityGalleryPreviewForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_gallery_preview_form_select';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\EntityInterface $entity_gallery
   *   The entity gallery being previews
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity_gallery = NULL) {
    $view_mode = $entity_gallery->preview_view_mode;

    $query_options = array('query' => array('uuid' => $entity_gallery->uuid()));
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $query_options['query']['destination'] = $query->get('destination');
    }
    $form['backlink'] = array(
      '#type' => 'link',
      '#title' => $this->t('Back to content editing'),
      '#url' => $entity_gallery->isNew() ? Url::fromRoute('entity_gallery.add', ['entity_gallery_type' => $entity_gallery->bundle()]) : $entity_gallery->urlInfo('edit-form'),
      '#options' => array('attributes' => array('class' => array('entity-gallery-preview-backlink'))) + $query_options,
    );

    // Always show full as an option, even if the display is not enabled.

    $view_mode_options = ['full' => $this->t('Full')] + $this->entityManager->getViewModeOptionsByBundle('entity_gallery', $entity_gallery->bundle());

    // Unset view modes that are not used in the front end.
    unset($view_mode_options['default']);
    unset($view_mode_options['search_index']);

    $form['uuid'] = array(
      '#type' => 'value',
      '#value' => $entity_gallery->uuid(),
    );

    $form['view_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $view_mode_options,
      '#default_value' => $view_mode,
      '#attributes' => array(
        'data-drupal-autosubmit' => TRUE,
      )
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Switch'),
      '#attributes' => array(
        'class' => array('js-hide'),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_parameters = [
      'entity_gallery_preview' => $form_state->getValue('uuid'),
      'view_mode_id' => $form_state->getValue('view_mode'),
    ];

    $options = [];
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $options['query']['destination'] = $query->get('destination');
      $query->remove('destination');
    }
    $form_state->setRedirect('entity.entity_gallery.preview', $route_parameters, $options);
  }

}
