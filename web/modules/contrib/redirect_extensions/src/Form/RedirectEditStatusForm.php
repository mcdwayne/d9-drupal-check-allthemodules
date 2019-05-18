<?php

namespace Drupal\redirect_extensions\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides a form to select a status code.
 */
class RedirectEditStatusForm extends FormBase {

  /**
   * The array of redirects to delete.
   *
   * @var string[][]
   */
  protected $redirects = [];

  /**
   * The private tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The redirect storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $redirectStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configFactory;

  /**
   * Constructs a RedirectEditStatus form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The String translation.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config manager for retrieving dependent config.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, TranslationInterface $string_translation, ConfigFactory $configFactory) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->redirectStorage = $entity_type_manager->getStorage('redirect');
    $this->currentUser = $account;
    $this->setStringTranslation($string_translation);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('string_translation'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_edit_status_code';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('redirect.list');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('redirect.settings');
    $this->redirects = $this->privateTempStoreFactory->get('redirect_edit_status_code')->get($this->currentUser->id());
    if (empty($this->redirects)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $default_code = $config->get('default_status_code');

    $form['status_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Redirect status'),
      '#description' => $this->t('You can find more information about HTTP redirect status codes at <a href="@status-codes">@status-codes</a>.', ['@status-codes' => 'http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection']),
      '#default_value' => $default_code,
      '#options' => [
        300 => $this->t('300 Multiple Choices'),
        301 => $this->t('301 Moved Permanently'),
        302 => $this->t('302 Found'),
        303 => $this->t('303 See Other'),
        304 => $this->t('304 Not Modified'),
        305 => $this->t('305 Use Proxy'),
        307 => $this->t('307 Temporary Redirect'),
      ],
    ];

    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    $form['cancle_link'] = [
      '#title' => $this->t('Cancel'),
      '#type' => 'link',
      '#url' => Url::fromRoute('redirect.list'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('status_code') && !empty($this->redirects)) {
      // Save the status code in each of the selected redirects.
      $status_code = $form_state->getValue('status_code');
      foreach ($this->redirects as $redirect) {
        $redirect->setStatusCode($status_code);
        $redirect->save();
      }
      $count = count($this->redirects);
      $this->logger('redirect')->notice('Updated @count redirects.', ['@count' => $count]);
      drupal_set_message($this->stringTranslation->formatPlural($count, 'Updated 1 redirect.', 'Updated @count redirects.'));
    }
    $form_state->setRedirect('redirect.list');
  }

}
