<?php

namespace Drupal\node_read_time\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines a form for enabling reading time field for specific content types.
 */
class ReadingTimeConfigForm extends ConfigFormBase {

  protected $entityManager;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManager $entityManager) {
    parent::__construct($config_factory);
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'reading_time';

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('node_read_time.settings');
    $contentTypes = $this->entityManager->getStorage('node_type')->loadMultiple();
    $words_per_minute = !empty($config->get('reading_time')['words_per_minute']) ?
      $config->get('reading_time')['words_per_minute'] : NULL;

    $form['container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content types to apply reading time field to.'),
      '#tree' => TRUE,
    ];
    $form['words_per_minute'] = [
      '#type' => 'number',
      '#title' => $this->t('Words per minute.'),
      '#default_value' => $words_per_minute,
    ];

    foreach ($contentTypes as $type => $obj) {
      $name = $obj->get('name');

      $is_activated = !empty($config->get('reading_time')['container'][$type]['is_activated']) ?
        $config->get('reading_time')['container'][$type]['is_activated'] : NULL;

      $form['container'][$type]['is_activated'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Activate for @name', ['@name' => $name]),
        '#default_value' => $is_activated,
        '#tree' => TRUE,
      ];

    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('node_read_time.settings');
    $config->set('reading_time', $form_state->getValues());

    $config->save();

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return [
      'node_read_time.settings',
    ];

  }

}
