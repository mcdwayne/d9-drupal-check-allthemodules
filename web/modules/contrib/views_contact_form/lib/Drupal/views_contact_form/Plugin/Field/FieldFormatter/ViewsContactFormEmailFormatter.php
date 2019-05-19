<?php

/**
 * @file
 * Definition of Drupal\views_contact_form\Plugin\field\formatter\ViewsContactFormEmailFormatter.
 */

namespace Drupal\views_contact_form\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\String;

/**
 * Plugin implementation of the 'ViewsContactFormEmailFormatter' formatter
 *
 * @FieldFormatter(
 *   id = "views_contact_form_email_formatter",
 *   label = @Translation("Views Contact Form"),
 *   field_types = {
 *     "email"
 *   },
 *   settings = {
 *     "category" = "feedback",
 *     "category_recipients_include" = TRUE
 *   }
 * )
 */
class ViewsContactFormEmailFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {

    module_load_include('pages.inc', 'contact');

    // Get the category entity from the configuration.
    $category = clone entity_load('contact_category', $this->getSetting('category'));

    // Get value from the items and store it.
    $recipients = array();
    foreach ($items as $delta => $item) {
      $recipients[] = $item->value;
    }

    // If we want to send email also to the recipients from the category,
    // merge recipients from items and category.
    if ($this->getSetting('category_recipients_include') == TRUE) {
      $recipients = array_merge($recipients, $category->recipients);
    }

    // Remove the doubles to avoid double mail.
    $recipients = array_unique($recipients);
    // Finally override the recipients on the category entity.
    $category->set('recipients', $recipients);

    // Create the Message entity from the category.
    $message = entity_create('contact_message', array(
      'category' => $category->id(),
    ));
    // Override the entity category in the Message.
    // So the recipients are also set.
    $message->category->entity = $category;

    // Get the form
    $form = \Drupal::entityManager()->getForm($message);
    // Override the title
    $form['#title'] = String::checkPlain($category->label());

    // Render the form and return the element.
    return array(0 => array(
      '#markup' => drupal_render($form)
      )
    );

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $entity = entity_load('contact_category', $this->getSetting('category'));
    $category_recipients_include = $this->getSetting('category_recipients_include') == TRUE ? 'Yes' : 'No';

    $summary[] = t('Category: <a href="@url">@category</a>', array('@url' => '/admin/structure/contact/manage/' . $entity->id, '@category' => $entity->label));
    $summary[] = t('Include category recipient(s): @category_recipients_include', array('@category_recipients_include' => $category_recipients_include));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {

    $categories = entity_load_multiple('contact_category');

    foreach($categories as $id => $category) {
      $options[$id] = $category->label;
    }

    $form['category'] = array(
      '#title' => 'Choose the category',
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSetting('category')
    );

    $form['category_recipients_include'] = array(
      '#title' => 'Category recipient(s)',
      '#description' => 'Should we also send the mail to the default category recipient(s) ?',
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('category_recipients_include')
    );

    $form['form_display'] = array(
      '#title' => 'Form display',
      '#markup' => 'You can customize the display of the form by editing the ' .
        'category form display. Click on the corresponding link: ' .
        '<em>Manage form display</em> on ' .
        l('this page', 'admin/structure/contact', array('attributes' => array('target' => '_blank'))),
      '#type' => 'item'
    );

    return $form;
  }


}
