<?php

namespace Drupal\views_dynamic_entity_row\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Plugin\views\row\EntityRow;

/**
 * Dynamic entity row plugin.
 *
 * @ViewsRow(
 *   id = "dynamic_entity",
 *   deriver = "Drupal\views_dynamic_entity_row\Plugin\Derivative\ViewsDynamicEntityRow"
 * )
 */
class DynamicEntityRow extends EntityRow {

  /**
   * Returns the current renderer.
   *
   * @return \Drupal\views\Entity\Render\EntityTranslationRendererBase
   *   The configured renderer.
   */
  protected function getEntityTranslationRenderer() {
    if (!isset($this->entityTranslationRenderer)) {
      $view = $this->getView();
      $rendering_language = $view->display_handler->getOption('rendering_language');
      $langcode = NULL;
      $dynamic_renderers = array(
        '***LANGUAGE_entity_translation***' => 'DynamicViewModeTranslationLanguageRenderer',
        '***LANGUAGE_entity_default***' => 'DefaultLanguageRenderer',
      );
      if (isset($dynamic_renderers[$rendering_language])) {
        // Dynamic language set based on result rows or instance defaults.
        $renderer = $dynamic_renderers[$rendering_language];
      }
      else {
        if (strpos($rendering_language, '***LANGUAGE_') !== FALSE) {
          $langcode = PluginBase::queryLanguageSubstitutions()[$rendering_language];
        }
        else {
          // Specific langcode set.
          $langcode = $rendering_language;
        }
        $renderer = 'ConfigurableLanguageRenderer';
      }

      if ($renderer == 'DynamicViewModeTranslationLanguageRenderer') {
        $class = '\Drupal\views_dynamic_entity_row\Entity\Render\\' . $renderer;
      }
      else {
        $class = '\Drupal\views\Entity\Render\\' . $renderer;
      }
      $entity_type = $this->getEntityManager()->getDefinition($this->getEntityTypeId());
      $this->entityTranslationRenderer = new $class($view, $this->getLanguageManager(), $entity_type, $langcode);
    }

    return $this->entityTranslationRenderer;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['view_mode']['#title'] = $this->t('Default view mode');
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    $options = $this->entityManager->getViewModeOptions($this->entityTypeId);

    if (isset($options[$this->options['view_mode']])) {
      return $this->t('Autodetect (default: @default)', [
        '@default' => $options[$this->options['view_mode']],
      ]);
    }
    else {
      return $this->t('Autodetect (default not set)');
    }
  }

}
