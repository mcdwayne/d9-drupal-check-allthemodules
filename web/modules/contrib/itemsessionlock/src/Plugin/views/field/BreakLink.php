<?php

namespace Drupal\itemsessionlock\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to present a link to break a lock.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("itemsessionlock_break_link")
 */
class BreakLink extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->additional_fields['type'] = 'type';
    $this->additional_fields['iid'] = 'iid';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $actual = array(
      'iid' => $this->getValue($values, 'iid'),
      'type' => $this->getValue($values, 'type'),
    );

    return $this->renderLink($actual, $values);
  }

  /**
   * Alters the field to render a link.
   *
   * @param array $actual
   * @param \Drupal\views\ResultRow $values
   *   The current row of the views result.
   *
   * @return string
   *   The acutal rendered text (without the link) of this field.
   */
  protected function renderLink($data, ResultRow $values) {
    $manager = \Drupal::service('plugin.manager.itemsessionlock');
    $lock = $manager->createInstance($data['type'], array('iid' => $data['iid']));

    $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('Break');
    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['url'] = Url::fromUri('internal:' . $lock->getBreakRoute($lock->getProvider(), $data['type'], $data['iid'], 'any'), [ 'query' => ['destination' => \Drupal::destination()->get()]]);

    return $text;
  }

}
