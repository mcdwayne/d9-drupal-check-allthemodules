<?php

/**
 * @file
 * Pathalias_force settings form used to recreate forced aliases.
 */

namespace Drupal\pathalias_force\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\language\Entity\ConfigurableLanguage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PathAliasForceSettings extends FormBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  protected $languageManager;


  /**
   * AxisPathAliasForceSettings constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   */
  protected function __construct(Connection $connection, LanguageManager $languageManager) {
    $this->connection = $connection;
    $this->languageManager = $languageManager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\pathalias_force\Form\PathAliasForceSettings|\Drupal\Core\Form\FormBase
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('language_manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return('pathalias_force_recreate');
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['details'] = [
      '#markup' => $this->t('<h4>This will recreate all the forced aliases.</h4>'),
    ];

    $form['recreate'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Recreate'),
    )
    ;
    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $this->connection->select('url_alias', 'ul');
    $query->fields('ul', ['pid', 'source', 'langcode']);
    $query->condition('ul.forced', 0);
    $query->orderBy('ul.pid');
    $result = $query->execute();
    $sources = $result->fetchAll();

    $languages = $this->languageManager->getLanguages();
    $fallback_languages = [];
    /** @var \Drupal\Core\Language\Language $language */
    foreach ($languages as $language) {
      $conf_language = ConfigurableLanguage::load($language->getId());
      if ($conf_language->getThirdPartySetting('language_hierarchy', 'fallback_langcode', '')) {
        $fallback_languages[] = $language->getId();
      }
    }

    $operations = [];
    foreach ($sources as $source) {
      if (!in_array($source->langcode, $fallback_languages)) {
        $operations[] = [
          '\Drupal\pathalias_force\PathAliasForceBatch::recreate',
          [$source->source, $source->langcode],
        ];
      }
    }

    $batch = [
      'title' => $this->t('Recreating forced aliases'),
      'operations' => $operations,
      'finished'=> '\Drupal\pathalias_force\PathAliasForceBatch::finished',
    ];

    batch_set($batch);
  }

}
