<?php

namespace Drupal\imager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the GeolocationGoogleMapAPIkey form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ImagerSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * ImagerSettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity Manager.
   */
  public function __construct(EntityTypeManagerInterface $entityManager) {
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'imager_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'imager.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('imager.settings');
    $view_modes = $this->entityManager->getViewModes('media');

    $options = [];
    foreach ($view_modes as $mode) {
      $options[$mode['id']] = $mode['label'];
    }

    $form['help'] = [
      '#type' => 'link',
      '#title' => $this->t('Click here for help'),
      '#url' => Url::fromUri('internal:/admin/help/imager'),
    ];

    $form['view_modes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('View Modes'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      'view_mode_info' => [
        '#type' => 'select',
        '#title' => $this->t('Information popup'),
        '#default_value' => $config->get('view_mode_info'),
        '#options' => $options,
        '#description' => $this->t('View mode for displaying media entity in information popup.'),
      ],
//    'view_mode_map' => [
//      '#type' => 'select',
//      '#title' => $this->t('Map marker popup'),
//      '#default_value' => $config->get('view_mode_map'),
//      '#options' => $options,
//      '#description' => $this->t('View mode when user hovers over a map marker'),
//    ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('imager.settings');

    $config->set('view_mode_info', $form_state->getValue('view_mode_info'));
//  $config->set('view_mode_map', $form_state->getValue('view_mode_map'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
