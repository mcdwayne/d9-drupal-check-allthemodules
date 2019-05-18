<?php

namespace Drupal\message_thread\Plugin\Field\FieldFormatter;

use Drupal\views\Views;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field formatter for Message Thread Messages Field.
 *
 * @FieldFormatter(
 *   id = "message_thread_messages",
 *   label = @Translation("Messages View"),
 *   field_types = {"message_thread_messages"}
 * )
 */
class MessageThreadMessagesFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['view_id'] = 'message_threads';
    $options['display_id'] = 'default';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $allowed = [];
    foreach ($settings['plugin_types'] as $type) {
      if ($type) {
        $allowed[] = $type;
      }
    }
    $summary[] = t('Allowed plugins: @view', ['@view' => implode(', ', $allowed)]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $view_name = $item->getValue()['target_id'];
      $display_id = $item->getValue()['display_id'];
      $argument = $item->getValue()['argument'];
      $title = $item->getValue()['title'];
      $view = Views::getView($view_name);
      // Someone may have deleted the View.
      if (!is_object($view)) {
        continue;
      }
      $arguments = [$argument];
      if (preg_match('/\//', $argument)) {
        $arguments = explode('/', $argument);
      }

      $node = \Drupal::routeMatch()->getParameter('node');
      $token_service = \Drupal::token();
      if (is_array($arguments)) {
        foreach ($arguments as $index => $argument) {
          if (!empty($token_service->scan($argument))) {
            $arguments[$index] = $token_service->replace($argument, ['node' => $node]);
          }
        }
      }

      $view->setDisplay($display_id);
      $view->setArguments($arguments);
      $view->build($display_id);
      $view->preExecute();
      $view->execute($display_id);

      if (!empty($view->result) || !empty($view->empty)) {
        if ($title) {
          $title = $view->getTitle();
          $title_render_array = [
            '#theme' => 'viewsreference__view_title',
            '#title' => $title,
          ];
        }

        if ($this->getSetting('plugin_types')) {
          if ($title) {
            $elements[$delta]['title'] = $title_render_array;
          }
        }

        $elements[$delta]['contents'] = $view->buildRenderable($display_id);
      }
    }

    return $elements;
  }

}
