<?php

namespace Drupal\alexa_flash_briefing\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Plugin\views\field\Path;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Default style plugin to render an Alexa Flash Briefing feed as JSON.
 *
 * @ViewsStyle(
 *   id = "alexa_flash_briefing_text_json",
 *   title = @Translation("Text Feed (JSON)"),
 *   help = @Translation("Generates an Alexa Flash Briefing text feed using JSON."),
 *   display_types = {"alexa_flash_briefing"}
 * )
 */
class FlashBriefingJson extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['uid_field'] = ['default' => ''];
    $options['date_field'] = ['default' => ''];
    $options['title_field'] = ['default' => ''];
    $options['description_field'] = ['default' => ''];
    $options['link_field'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $view_fields_labels = $this->displayHandler->getFieldLabels();

    $form['uid_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Identifier field'),
      '#description' => $this->t('Unique identifier for each feed item (UUID format preferred, but a nid is okay too)'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['uid_field'],
      '#required' => TRUE,
    ];
    $form['date_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Date field'),
      '#description' => $this->t('Used to order items to be read from newest to oldest. Will be formatted automatically.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['date_field'],
      '#required' => TRUE,
    ];
    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#description' => $this->t('The title of the feed item to display in the Alexa app (no HTML allowed)'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['title_field'],
      '#required' => TRUE,
    ];
    $form['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Text content field'),
      '#description' => $this->t('The text that is read to the user (4500 characters max; no HTML allowed)'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['description_field'],
      '#required' => TRUE,
    ];
    $form['link_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Display URL field'),
      '#description' => $this->t('Provides the URL target for the Read More link in the Alexa app (absolute URL to path alias; no HTML allowed)'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['link_field'],
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();

    $required_options = [
      'uid_field',
      'date_field',
      'title_field',
      'description_field',
      'link_field',
    ];
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Row style plugin requires specifying which views fields to use for Alexa Flash Briefing items.');
        break;
      }
    }

    // Check pagination limit.
    if ($this->displayHandler->isPagerEnabled()) {
      $per_page = $this->displayHandler->getPlugin('pager')->getItemsPerPage();
      if ($per_page == 0 || $per_page > 5) {
        $errors[] = $this->t('"Items to display" is too high; Alexa only supports up to 5 items.');
      }
    }

    // Check URL field.
    $link_handler = $this->displayHandler->getHandler('field', $this->options['link_field']);
    if ($link_handler !== NULL && $link_handler instanceof Path) {
      if (!$link_handler->options['absolute']) {
        $errors[] = $this->t('"%group: %title" must be an absolute URL', ['%group' => $link_handler->definition['group'], '%title' => $link_handler->definition['title']]);
      }
    }

    return $errors;
  }

  /**
   * Render the display in this style.
   *
   * @return array
   *   The render array containing pre-rendered JSON.
   */
  public function render() {
    $date = new \DateTime('now', new \DateTimeZone('UTC'));

    $rows = [];
    foreach ($this->view->result as $row_index => $row) {
      $d = $this->getFieldValue($row_index, $this->options['date_field']);
      $d = $date->setTimestamp($d)->format('Y-m-d\TH:i:s.0\Z');

      $link_field_value = $this->getField($row_index, $this->options['link_field']);
      if (substr($link_field_value, 0, 1) !== '/') {
        $link_field_value = '/' . $link_field_value;
      }

      $item = new \stdClass();
      $item->uid = (string) $this->getField($row_index, $this->options['uid_field']);
      $item->updateDate = $d;
      $item->titleText = (string) $this->getField($row_index, $this->options['title_field']);
      $item->mainText = (string) $this->getField($row_index, $this->options['description_field']);
      $item->redirectionUrl = Url::fromUserInput($link_field_value)->setAbsolute()->toString();

      $rows[] = $item;
    }

    if (count($rows) === 1) {
      $markup = json_encode($rows[0], JSON_PRETTY_PRINT);
    }
    else {
      $markup = json_encode($rows, JSON_PRETTY_PRINT);
    }

    return [
      '#children' => $markup,
      '#view' => $this->view,
      '#options' => $this->options,
    ];
  }

}
