<?php

namespace Drupal\drd\Entity\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\drd\Crypt\Base as CryptBase;
use Drupal\drd\Entity\Domain as DomainEntity;
use Drupal\drd\Plugin\Auth\Manager as AuthManager;

/**
 * Form controller for Core edit forms.
 *
 * @ingroup drd
 */
class Core extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    /* @var \Drupal\drd\Entity\CoreInterface $core */
    $core = $this->entity;

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $core->getLangCode(),
      '#languages' => Language::STATE_ALL,
    ];

    if ($core->isNew()) {
      $form['host']['widget']['#default_value'] = [1];

      $referer = \Drupal::request()->headers->get('referer');
      if (!empty($referer)) {
        $base_url = Url::fromUserInput('/', ['absolute' => TRUE])->toString();
        if (strpos($referer, $base_url) === 0) {
          $path = substr($referer, strlen($base_url) - 1);
          $url = Url::fromUserInput($path);
          if ($url->getRouteName() == 'entity.drd_host.canonical') {
            $form['host']['widget']['#default_value'] = [(int) $url->getRouteParameters()['drd_host']];
          }
        }
      }

      // Adding a new core means we need the URL to initially contact that site
      // to grab all the details about that Drupal installation.
      $form['drd-new-core-wrapper'] = [
        '#type' => 'container',
        '#weight' => -99,
      ];
      $form['drd-new-core-wrapper']['url'] = [
        '#title' => $this->t('URL'),
        '#type' => 'url',
        '#default_value' => '',
        '#description' => $this->t('Provide the URL including scheme (e.g. https://www.example.com) and then press the TAB key (or leave the field otherwise) so that DRD will validate the URL and provide you with more setting fields.'),
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'validateUrlAjax'],
          'event' => 'change',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Verifying url...'),
          ],
        ],
      ];
      $form['drd-new-core-wrapper']['url-message'] = [
        '#type' => 'container',
      ];

      // Container for domain specific settings.
      $form['drd-new-core-wrapper']['drd'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'hidden',
        ],
      ];
      $form['drd-new-core-wrapper']['drd'] += AuthManager::authForm($form, $form_state);
      $form['drd-new-core-wrapper']['drd'] += CryptBase::cryptForm($form, $form_state);

      // Hide the actions until the domain specific settings will be displayed.
      $form['actions']['#attributes']['class'][] = 'hidden';
    }
    else {
      $form['host']['#disabled'] = TRUE;
    }

    /** @var \Drupal\drd\Update\ManagerStorageInterface $updateManager */
    $updateManager = \Drupal::service('plugin.manager.drd_update.storage');
    $updateManager->buildGlobalForm($form, $form_state, $core->getUpdateSettings());

    return $form;
  }

  /**
   * Validates that the url field points to a drd_agent enabled domain.
   *
   * @param array $form
   *   Form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return bool|string|array
   *   If the URI is not provided yet, return FALSE. If we can't receive a list
   *   of crypt methods from remote, return a message as a string explaining the
   *   reason. If all goes allright, we return an array with the list of
   *   available crypt methods.
   */
  private function validateUrl(array &$form, FormStateInterface $form_state) {
    $uri = trim($form_state->getValue('url'), ' /');
    if (empty($uri)) {
      return FALSE;
    }

    /* @var \Drupal\drd\Entity\CoreInterface $core */
    $core = $this->entity;
    $values = AuthManager::authFormValues($form_state) + CryptBase::cryptFormValues($form_state);
    $domain = DomainEntity::instanceFromUrl($core, $uri, $values);

    if (!$domain->isNew()) {
      return $this->t('This domain is already known to the dashboard.')->render();
    }

    $crypt_methods = $domain->getSupportedCryptMethods();
    if ($crypt_methods === FALSE) {
      return $this->t('Can not connect to this domain.')->render();
    }
    if (empty($crypt_methods)) {
      return $this->t('There is no DRD Agent available at this domain.')->render();
    }
    elseif (CryptBase::countAvailableMethods($crypt_methods) == 0) {
      return $this->t('The remote site has DRD Agent installed but does not support any encryption methods matching those of the dashboard.')->render();
    }

    $form_state->setTemporaryValue('drd_domain', $domain);
    return $crypt_methods;
  }

  /**
   * Ajax callback for checking remote domain.
   *
   * Ajax callback to lookup a remote domain and receive their supported crypt
   * methods which will be integrated into the settings form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response object with instructions on how to adjust the form.
   */
  public function validateUrlAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $crypt_methods = $this->validateUrl($form, $form_state);
    if (is_array($crypt_methods)) {
      $command = 'removeClass';
      $message = '';
      foreach (CryptBase::getMethods() as $key => $value) {
        $response->addCommand(new InvokeCommand('#edit-drd-crypt-type option[value="' . $key . '"]', 'prop', ['disabled', !isset($crypt_methods[$key])]));
      }
    }
    else {
      $command = 'addClass';
      $message = $crypt_methods;
    }
    $response->addCommand(new InvokeCommand('#edit-drd', $command, ['hidden']));
    $response->addCommand(new InvokeCommand('#edit-actions', $command, ['hidden']));
    $response->addCommand(new HtmlCommand('#edit-url-message', $message));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\drd\Entity\CoreInterface $core */
    $core = parent::validateForm($form, $form_state);
    if (!$form_state->hasAnyErrors() && $core->isNew()) {
      $is_ajax = \Drupal::request()->get('ajax_form');
      if (empty($is_ajax)) {
        $error = $this->validateUrl($form, $form_state);
        if (!empty($error) && is_string($error)) {
          $form_state->setErrorByName('url', $error);
        }
      }
    }
    /** @var \Drupal\drd\Update\ManagerStorageInterface $updateManager */
    $updateManager = \Drupal::service('plugin.manager.drd_update.storage');
    $updateManager->validateGlobalForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\drd\Entity\CoreInterface $core */
    $core = $this->entity;

    /** @var \Drupal\drd\Update\ManagerStorageInterface $updateManager */
    $updateManager = \Drupal::service('plugin.manager.drd_update.storage');
    $core->set('updsettings', $updateManager->globalFormValues($form, $form_state));

    $status = $core->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label core.', [
          '%label' => $core->label(),
        ]));

        /** @var \Drupal\drd\Entity\DomainInterface $core */
        $domain = $form_state->getTemporaryValue('drd_domain');
        $domain->setCore($core);
        $domain->save();
        drupal_set_message($this->t('Now you should %configure your remote domain. Make sure you are logged in to the remote site before you click the link!', [
          '%configure' => $domain->getRemoteSetupLink($this->t('configure'), TRUE),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Core.', [
          '%label' => $core->label(),
        ]));
    }
    $form_state->setRedirect('entity.drd_core.canonical', ['drd_core' => $core->id()]);
  }

}
