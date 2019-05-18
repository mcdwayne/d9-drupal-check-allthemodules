<?php
/**
 * Created by PhpStorm.
 * User: will
 * Date: 5/23/18
 * Time: 8:35 AM
 */

namespace Drupal\byu_calendar\Plugin\Block;

use DateTime;
use DateTimeZone;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UpcomingEvents
 * @Block (
 *  id = "byu_calendar_block",
 *  admin_label = @Translation("BYU Calendar")
 * )
 */


class UpcomingEvents extends BlockBase {

  // This is where the form for the block is built.

  public function blockForm($form, FormStateInterface $formState) {
    $config = $this->getConfiguration();

    $form['byu_calendar_categories'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Categories')
    ];

    $form['byu_calendar_categories']['byu_calendar_main_categories'] = [
      '#type' => 'checkboxes',
      '#default_value' => isset($config['byu_calendar_main_categories']) ? $config['byu_calendar_main_categories'] : '',
      '#title' => $this->t('Main Categories'),
      '#options' => [
        90 => $this->t('Events featured on the BYU Homepage (<strong>90</strong>)'),
        395 => $this->t('All Academic Calendar Events, Deadlines and Holidays (<strong>395</strong>)'),
        9 => $this->t('Arts and Entertainment (<strong>9</strong>)'),
        10 => $this->t('Athletics (<strong>10</strong>)'),
        6 => $this->t('Conferences (<strong>6</strong>)'),
        7 => $this->t('Devotionals & Forums (<strong>7</strong>)'),
        4 => $this->t('Education (<strong>4</strong>)'),
        47 => $this->t('Health & Wellness (<strong>47</strong>)'),
        49 => $this->t('Student Life (<strong>49</strong>)'),
        52 => $this->t('Other (<strong>52</strong>)')
      ],
      '#description' => $this->t('Select the desired Categories.')
    ];

    $form['byu_calendar_categories']['byu_calendar_additional_categories'] = [
      '#type' => 'textfield',
      '#default_value' => isset($config['byu_calendar_additional_categories']) ? $this->t($config['byu_calendar_additional_categories']) : '',
      '#title' => $this->t('Additional Categories'),
      '#description' => $this->t('Any other additional categories. Enter the category using the category ID. Example: "574, 863, 921"')
    ];

    $form['byu_calendar_style'] = [
      '#type' => 'select',
      '#default_value' => isset($config['byu_calendar_style']) ? $this->t($config['byu_calendar_style']) : '',
      '#options' => [
        'classic_list' => $this->t('Classic List'),
        'vertical_tile' => $this->t('Vertical Tile'),
        'horizontal_tile' => $this->t('Horizontal Tile'),
        'fullpage_rows' => $this->t('Full-Page Rows'),
        'fullpage_image_rows' => $this->t('Full-Page Rows with Images'),
        'featured' => $this->t('Featured'),
        'minimal_tile' => $this->t('Minimal Tiles'),
      ],
      '#title' => $this->t('Calendar Display'),
      '#description' => $this->t('Selected the desired display.')
    ];

    $form['byu_calendar_days_forward'] = [
      '#type' => 'number',
      '#default_value' => isset($config['byu_calendar_days_forward']) ? $this->t($config['byu_calendar_days_forward']) : 30,
      '#title' => $this->t('Days to Look Forward'),
      '#description' => $this->t('Set how many days in advance the calendar should look for events.')
    ];

    $form['byu_calendar_event_limit'] = [
      '#type' => 'number',
      '#default_value' => isset($config['byu_calendar_event_limit']) ? $this->t($config['byu_calendar_event_limit']) : '',
      '#title' => $this->t('Limit of Events'),
      '#description' => $this->t('The number of events to be displayed.')
    ];

    $form['byu_calendar_price'] = [
      '#type' => 'number',
      '#default_value' => isset($config['byu_calendar_price']) ? $this->t($config['byu_calendar_price']) : 0,
      '#title' => $this->t('Price Filter'),
      '#field_prefix' => $this->t('$'),
      '#description' => $this->t('Show events under a certain price.')
    ];

    return $form;
  }

  // This passes the values entered in the block form to the block's configuration.

  public function blockSubmit($form, FormStateInterface $formState) {
    parent::blockSubmit($form, $formState);
    $values = $formState->getValues();
    $this->configuration['byu_calendar_main_categories'] = $values['byu_calendar_categories']['byu_calendar_main_categories'];
    $this->configuration['byu_calendar_additional_categories'] = $values['byu_calendar_categories']['byu_calendar_additional_categories'];
    $this->configuration['byu_calendar_style'] = $values['byu_calendar_style'];
    $this->configuration['byu_calendar_event_limit'] = $values['byu_calendar_event_limit'];
    $this->configuration['byu_calendar_days_forward'] = $values['byu_calendar_days_forward'];
    $this->configuration['byu_calendar_price'] = $values ['byu_calendar_price'];
  }

  // This will build the block based on the block's configuration.

  public function build() {
    $config = $this->getConfiguration();
    $mainCategories = $config['byu_calendar_main_categories'];
    $additionalCategories = $config['byu_calendar_additional_categories'];
    $style = $config['byu_calendar_style'];
    $limit = $config['byu_calendar_event_limit'];
    $days = $config['byu_calendar_days_forward'];
    $price = $config['byu_calendar_price'];

    // Get the category IDs in the right format.
    $additionalCategories = str_replace(' ', '', $additionalCategories);

    // Check for duplicate IDs in additional categories.
    foreach($mainCategories as $mainCategory) {
      if ($mainCategory != 0) {
        $additionalCategories = str_replace($mainCategory . ',', '', $additionalCategories);
        $additionalCategories = str_replace($mainCategory, '', $additionalCategories);
      }
    }
    $additionalCategories = str_replace(',', '+', $additionalCategories);
    $mainCategories = implode('+', $mainCategories);
    $mainCategories = str_replace('+0', '', $mainCategories); //Remove zeroes from empty check boxes.
    $categories = $mainCategories . '+' . $additionalCategories;

    // Remove trailing + signs if any are present
    if (substr($categories, -1) == '+') {
      $categories = substr($categories, 0, -1);
    }

    $html = byu_calendar_build_display($categories, $style, $limit, $days, $price);
    $tz = new DateTimeZone('America/Denver');
    $tomorrow = new DateTime("tomorrow", $tz);
    $now = new DateTime("now", $tz);

    $library = 'byu_calendar/byu-calendar';

    return [
      '#type' => 'inline_template',
      '#template' => '{{ content | raw }}',
      '#context' => [
        'content' => $html,
      ],
      '#cache' => [
        'max-age' => ($tomorrow->getTimestamp() - $now->getTimestamp()) //i.e. expire cache at midnight tonight
      ],
      '#attached' => [
        'library' => [
          $library,
        ],
      ],
    ];

  }
}
