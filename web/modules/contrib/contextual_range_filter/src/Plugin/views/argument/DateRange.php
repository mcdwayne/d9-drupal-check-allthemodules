<?php

namespace Drupal\contextual_range_filter\Plugin\views\argument;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\argument\Date;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\contextual_range_filter\ContextualRangeFilter;

/**
 * Argument handler to accept a date range.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("date_range")
 */
class DateRange extends Date {

  /**
   * Overrides Drupal\views\Plugin\views\argument\Formula::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $plugin_id = $options['plugin_id'] ?: 'date_fulldate';

    switch ($plugin_id) {

      case 'date_year':
        $this->format = 'Y';
        $this->argFormat = 'Y';
        break;

      case 'date_year_month':
        $this->format = 'F Y';
        $this->argFormat = 'Ym';
        break;

      case 'date_month':
        $this->format = 'F';
        $this->argFormat = 'm';
        break;

      case 'date_week':
        $this->format = 'w';
        $this->argFormat = 'W';
        break;

      case 'date_day':
        $this->format = 'j';
        $this->argFormat = 'd';
        break;

      case 'date_fulldate':
      default:
        $this->format = 'F j, Y';
        $this->argFormat = 'Ymd';
        break;
    }
  }

  /**
   * Define our options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['break_phrase'] = array('default' => FALSE);
    $options['not'] = array('default' => FALSE);
    return $options;
  }

  /**
   * Build the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['description']['#markup'] = t('Contextual date range filter values are taken from the URL.');

    $form['more']['#collapsed'] = FALSE;

    // Allow passing multiple values.
    $form['break_phrase'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow multiple date ranges'),
      '#description' => t('If selected, multiple date ranges may be specified by stringing them together with plus signs.<br/>Example: <strong>19990101--20051231+20130701--20140630</strong>'),
      '#default_value' => $this->options['break_phrase'],
      '#fieldset' => 'more',
    );

    $form['not'] = array(
      '#type' => 'checkbox',
      '#title' => t('Exclude'),
      '#description' => t('Negate the range. If selected, output matching the specified date range(s) will be excluded, rather than included.'),
      '#default_value' => !empty($this->options['not']),
      '#fieldset' => 'more',
    );
  }

  /**
   * Title override.
   *
   * Required because of range version of views_break_phrase() in this function.
   */
  public function title() {
    if (!$this->argument) {
      return $this->definition['empty field name'] ?: t('Uncategorized');
    }
    if (!empty($this->options['break_phrase'])) {
      $this->breakPhraseRange($this->argument);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'or';
    }
    if ($this->value === FALSE) {
      return $this->definition['invalid input'] ?: t('Invalid input');
    }
    if (empty($this->value)) {
      return $this->definition['empty field name'] ?: t('Uncategorized');
    }

    return implode($this->operator == 'or' ? ' + ' : ', ', $this->value);
  }

  /**
   * Prepare the range query where clause.
   *
   * @param bool $group_by
   *   Whether to apply grouping.
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    if (!empty($this->options['break_phrase'])) {
      // From "Allow multple ranges" checkbox.
      $this->breakPhraseRange($this->argument);
    }
    else {
      $this->value = array($this->argument);
    }
    ContextualRangeFilter::buildRangeQuery($this, $this->getFormula());
  }

  /**
   * Break xfrom--xto+yfrom--yto+zfrom--zto into an array or ranges.
   *
   * @param string $str
   *   The string to parse.
   */
  protected function breakPhraseRange($str) {
    if (empty($str)) {
      return;
    }
    $this->value = preg_split('/[+ ]/', $str);
    $this->operator = 'or';
    // Keep an 'error' value if invalid ranges were given.
    // A single non-empty value is ok, but a plus sign without values is not.
    if (count($this->value) > 1 && (empty($this->value[0]) || empty($this->value[1]))) {
      $this->value = FALSE;
    }
  }

}
