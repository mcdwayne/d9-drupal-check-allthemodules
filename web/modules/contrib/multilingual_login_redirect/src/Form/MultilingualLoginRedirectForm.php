<?php

namespace Drupal\multilingual_login_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\multilingual_login_redirect\Entity\MultilingualRedirect;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * Defines MultilingualLoginRedirectForm form class.
 */
class MultilingualLoginRedirectForm extends ConfigFormBase {

  protected $DrupalLanguageManager;
  protected $MultilingualRedirect;

  /**
   * Constructor for class MultilingualLoginRedirectForm.
   *
   * @param Drupal\Core\Language\LanguageManager $languageManager
   *   Instance of LanguageManager class.
   */
  public function __construct(LanguageManager $languageManager) {
    $this->DrupalLanguageManager = $languageManager;
    $this->MultilingualRedirect = new MultilingualRedirect();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multilingualLoginRedirectForm';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'multilingual_login_redirect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form['#attached']['library'][] = 'multilingual_login_redirect/mlr_library';

    // Get roles and pass to javascript.
    $drupal_roles = $this->MultilingualRedirect->getDrupalRoles();
    $roles = $this->MultilingualRedirect->sanitizeRolesArray($drupal_roles);
    $form['#attached']['drupalSettings']['multilingual_login_redirect']['js']['roles'] = json_encode(array_keys($roles));

    $languages = $this->DrupalLanguageManager->getLanguages();

    foreach ($languages as $lang => $lang_values) {
      $form['mlr_description'] = [
        '#type' => 'item',
        '#title' => 'Usage:',
        '#markup' => 'You can put a redirect role for each language and add exceptions for each role.<br />
                      Allowed roles are relative paths (/it/blog), absolute paths(http://mysite.com/it/blog)
                      or node number following this format: node:[node_id](node:20).<br />
                      <b>If a user has multiple roles the module will apply the rule using the role with the highest weight</b>',
        '#prefix' => '<div class="mlr-description">',
        '#suffix' => '</div>',
      ];

      $form['mlr_destination__' . $lang] = [
        '#type' => 'textfield',
        '#title' => $this->t('General destination page @lang', ['@lang' => $lang]),
        '#default_value' => $this->MultilingualRedirect->getRedirect('mlr_destination__' . $lang),
        '#prefix' => '<div class="mlr-exception-row-pre">',
        '#suffix' => '</div>',
      ];

      foreach ($roles as $role => $role_info) {
        $redirect_value = $this->MultilingualRedirect->getRedirect('mlr_destination__' . $lang . '__' . $role);
        if ($this->MultilingualRedirect->redirectIsRegistered($redirect_value)) {
          $form['mlr_destination__' . $lang . '__' . $role] = [
            '#type' => 'textfield',
            '#title' => $this->t('Destination page @lang and @role', ['@lang' => $lang, '@role' => $role]),
            '#default_value' => $redirect_value,
            '#prefix' => '<div class="mlr-exception-row-pre">',
            '#suffix' => '<a class="mlr-delete-row" href="#">[ Delete ]</a></div>',
          ];
        }
      }

      $form['mlr_exception_button__' . $lang] = [
        '#type' => 'button',
        '#value' => 'Add exception for ' . $lang . ' language',
        '#id' => 'add_exc_' . $lang,
        '#attributes' => [
          'class' => [
            'new_exc_button',
          ],
          'language' => $lang,
        ],
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state = $this->extendMlrForm($form_state);
    $values = $form_state->getValues();

    foreach ($values as $field => $url) {
      if ($this->MultilingualRedirect->submissionIsMlr($field) && !$this->MultilingualRedirect->isValidUrl($url)) {
        $form_state->setErrorByName($field, $this->t('Url provided is not valid'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    foreach ($values as $field => $url) {
      if ($this->MultilingualRedirect->submissionIsMlr($field)) {
        $this->MultilingualRedirect->setRedirect($field, $url);
      }
    }
    drupal_set_message($this->t('URL saved'));
  }

  /**
   * Adding dinamically created form items into the form state.
   *
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The Form state.
   *
   * @return Drupal\Core\Form\FormStateInterface
   *   The refactored Form state.
   */
  private function extendMlrForm(FormStateInterface $form_state) {
    $extended_form_value = $_POST;
    foreach ($extended_form_value as $name => $value) {
      if (!isset($form_state->complete_form[$name]) && $this->MultilingualRedirect->submissionIsMlr($name)) {
        $form_state->setValue($name, $value);
      }
    }
    return $form_state;
  }

}
