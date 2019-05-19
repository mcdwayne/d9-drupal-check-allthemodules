<?php

namespace Drupal\redirect_extensions\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides a form to enter a new redirect destination.
 */
class RedirectEditDestForm extends FormBase {

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
   * Constructs a RedirectDeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The String translation.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory,
                              EntityTypeManagerInterface $entity_type_manager,
                              AccountInterface $account,
                              TranslationInterface $string_translation) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->redirectStorage = $entity_type_manager->getStorage('redirect');
    $this->currentUser = $account;
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_edit_dest';
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
    $this->redirects = $this->privateTempStoreFactory->get('redirect_edit_dest')->get($this->currentUser->id());
    if (empty($this->redirects)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $form['dest'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#maxlength' => 560,
      '#required' => TRUE,
      '#description' => $this->t('Enter an internal Drupal path, path alias,  or complete external URL (like http://example.com/) to redirect to. Use %front to redirect to the front page.',
        ['%front' => '<front>']),
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Make sure none of the redirects has a source path equal to the new
    // destination path.
    $destination = $form_state->getValue('dest');
    foreach ($this->redirects as $redirect) {
      $source = $redirect->getSource();
      try {
        $source_url = Url::fromUri('internal:/' . $source['path']);
        $redirect_url = Url::fromUri('internal:/' . $destination);

        // It is relevant to do this comparison only in case the source path has
        // a valid route. Otherwise the validation will fail on the redirect
        // path being an invalid route.
        if ($source_url->toString() == $redirect_url->toString()) {
          $form_state->setErrorByName('redirect_redirect',
            $this->t('You are attempting to redirect the page to itself. This will result in an infinite loop.'));
        }
      }
      catch (\InvalidArgumentException $e) {
        // Do nothing, we want to only compare the resulting URLs.
      }

    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('dest') && !empty($this->redirects)) {
      // Save the destination in each of the selected redirects.
      $destination = $form_state->getValue('dest');
      foreach ($this->redirects as $redirect) {

        // Work around for bug. See: https://www.drupal.org/node/2845884
        if (UrlHelper::isExternal($destination)) {
          $redirect->redirect_redirect->set(0, $destination, []);

        }
        else {
          $redirect->setRedirect($destination);
        }

        $redirect->save();
      }
      $count = count($this->redirects);
      $this->logger('redirect')->notice('Updated @count redirects.', ['@count' => $count]);
      drupal_set_message($this->stringTranslation->formatPlural($count, 'Updated 1 redirect.', 'Updated @count redirects.'));
    }
    $form_state->setRedirect('redirect.list');
  }

}
