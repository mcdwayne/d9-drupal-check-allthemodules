<?php
namespace Drupal\textrazor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Configure example settings for this site.
 */
class textrazorSettingsForm extends ConfigFormBase {

  private $entityManager;
  private $entityFiledManager;

  public function __construct( EntityManagerInterface $entityManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityManager = $entityManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textrazor_admin_settings';
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'textrazor.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('textrazor.settings');

    $form['textrazor_apikey'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('TextRazor API key'),
      '#default_value' => $config->get('textrazor_apikey'),
    );

    $bundles = $this->entityManager->getBundleInfo('node');
    $options = [];
    foreach ($bundles as $bundle_id => $bundle) {
      $options[$bundle_id] = $bundle['label'];
    }
    $form['active_bundles'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Content types'),
        '#options' => $options,
        '#default_value' => $config->get('active_bundles'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = $this->entityManager->getBundleInfo('node');
    $active_bundles = $form_state->getValue('active_bundles');
    $textrazorManager = \Drupal::service('textrazormanager');
    foreach ($bundles as $bundle_id => $bundle) {
      if ($active_bundles[$bundle_id] === 0) {
        $textrazorManager->removeTextrazorFields($bundle_id);
      }
      else {
        $textrazorManager->appendTextrazorFields($bundle_id);
      }
    }
    $this->config('textrazor.settings')
      ->set('textrazor_apikey', $form_state->getValue('textrazor_apikey'))
      ->set('active_bundles', $form_state->getValue('active_bundles'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
