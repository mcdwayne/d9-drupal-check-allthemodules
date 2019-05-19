<?php

namespace Drupal\wizenoze\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\wizenoze\Entity\Wizenoze;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Provides a 'Wizenoze page form' block.
 *
 * @Block(
 *   id = "wizenoze_page_form_block",
 *   admin_label = @Translation("Wizenoze search block form"),
 *   category = @Translation("Forms")
 * )
 */
class WizenozeSearchBlock extends BlockBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The form bulder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $form;

  /**
   * Constructs a new WizenozeSearchBlock object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param Drupal\Core\Form\FormBuilderInterface $form
   *   The form builder service.
   */
  public function __construct(EntityManagerInterface $entity_manager, FormBuilderInterface $form) {
    $this->entityManager = $entity_manager;
    $this->form = $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity.manager'), $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $options = [];

    $wizenoze_pages = $this->entityManager->getStorage('wizenoze')->loadMultiple();
    foreach ($wizenoze_pages as $wizenoze_page) {
      $options[$wizenoze_page->id()] = $wizenoze_page->label();
    }

    $form['wizenoze_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Search page'),
      '#default_value' => !empty($this->configuration['wizenoze_page']) ? $this->configuration['wizenoze_page'] : '',
      '#description' => $this->t('Select to which search page a submission of this form will redirect to'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['wizenoze_page'] = $form_state->getValue('wizenoze_page');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    /* @var $wizenoze_page \Drupal\wizenoze\WizenozePageInterface */
    $wizenoze_page = Wizenoze::load($this->configuration['wizenoze_page']);
    $config_name = $wizenoze_page->getConfigDependencyName();
    return ['config' => [$config_name]];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $args = [
      'wizenoze_page' => $this->configuration['wizenoze_page'],
    ];
    return $this->form->getForm('Drupal\wizenoze\Form\WizenozePageBlockForm', $args);
  }

}
