<?php

namespace Drupal\death_link\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class DeathLinkConfigForm.
 *
 * @package Drupal\death_link
 */
class DeathLinkConfigForm extends ConfigFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityManager) {
    parent::__construct($configFactory);
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'death_link_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $formState) {
    // Get the config.
    $config = $this->config('death_link.settings');

    // Provide a default empty option list.
    $options = [];

    // Build list with all entities.
    $linkProfiles = $this->entityManager->getStorage('linkit_profile')->loadMultiple();
    if (!empty($linkProfiles)) {
      foreach ($linkProfiles as $linkProfile) {
        $options[$linkProfile->id()] = $linkProfile->label();
      }
    }

    // Build the parent form.
    if (!empty($options)) {
      $form = parent::buildForm($form, $formState);
    }

    if (empty($options)) {

      // Provide the label field.
      $form['no_linkit'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('No linkit profiles available'),
      ];

      // Provide the label field.
      $form['no_linkit']['description'] = [
        '#type' => 'item',
        '#markup' => $this->t('Please @linkit for creating Death Links.', [
          '@linkit' => Link::fromTextAndUrl($this->t('create a linkit profile'), Url::fromRoute('entity.linkit_profile.collection'))->toString(),
        ]),
      ];

      return $form;
    }

    // Provide checkboxes to manage which entities can be use for death links.
    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    // Provide checkboxes to manage which entities can be use for death links.
    $form['settings']['linkit_profile'] = [
      '#title' => $this->t('Linkit profile'),
      '#description' => $this->t('The linkit profile to use for creating Death Links'),
      '#type' => 'select',
      '#multiple' => FALSE,
      '#options' => $options,
      '#default_value' => $config->get('linkit_profile'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $config = $this->config('death_link.settings');
    $config->set('linkit_profile', $formState->getValue('linkit_profile'))->save();
    parent::submitForm($form, $formState);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'death_link.settings',
    ];
  }

}
