<?php

/**
 * @file
 * Contains \Drupal\custom_text\Form\CustomTextAdminForm.
 */

namespace Drupal\custom_text\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to configure identfication settings for this site.
 */
class CustomTextAdminForm extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ConfigCustomTextForm.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler) {
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'), $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_text_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();
    // Get the texts info.
    $texts_group_info = $this->moduleHandler->invokeAll('custom_text_group_info');
    $texts_info = $this->moduleHandler->invokeAll('custom_text_info');
    // If there are no valid identifiers, quit.
    if (!$texts_info || !$texts_group_info) {
      $form['error'] = array(
        '#title' => t('Error'),
        '#markup' => t('There are no texts defined by any of the modules used by this site, or you do not have permission to edit any of the texts. See <code>custom_text.api.php</code>.'),
      );
      return $form;
    }
    $languages = $this->languageManager->getLanguages();
    $texts = _custom_text_get_all();
    $form['texts'] = array(
      '#type' => 'vertical_tabs',
      '#title' => t('Custom Texts'),
    );

    foreach ($texts_group_info as $text_group_identifier => $text_group_element) {
      $form[$text_group_identifier] = array(
        '#type' => 'details',
        '#group' => 'texts',
      ) + $text_group_element;

      foreach ($languages as $langcode => $language) {
        $form[$text_group_identifier][$langcode] = array(
          '#type' => 'details',
          '#title' => $language->getName(),
          '#group' => 'passthru',
        );

        foreach ($texts_info[$text_group_identifier] as $text_identifier => $text_element) {
          if (preg_match('/^[\w_-]{1,64}$/', $text_identifier) && !preg_match('/--/', $text_identifier)) {
            $form[$text_group_identifier][$langcode]['text--' . $text_identifier . '--' . $langcode] = array(
              '#default_value' => isset($texts[$text_identifier][$langcode]) ? $texts[$text_identifier][$langcode]['value'] : NULL,
              '#format' => isset($texts[$text_identifier][$langcode]) ? $texts[$text_identifier][$langcode]['format'] : NULL,
            ) + $text_element;
          }
        }
      }
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save changes'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getUserInput() as $key => $value) {
      $matches = array();
      if (preg_match('/^text--([\w_-]+)--(\w+)$/', $key, $matches)) {
        list(, $identifier, $langcode) = $matches;
        if (is_array($value)) {
          $text = $value['value'];
          $text_format = $value['format'];
        }
        else {
          $text = $value;
          $text_format = NULL;
        }
        custom_text_set($identifier, $text, $text_format, $langcode);
      }
    }
    drupal_set_message(t('Your changes were saved.'));
  }

}
