<?php

namespace Drupal\social_contact_info\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Egulias\EmailValidator\EmailValidator;

/**
 * Provides a 'Social Contact Info' Block.
 *
 * @Block(
 *   id = "social_contact_info",
 *   admin_label = @Translation("Social Contact Info"),
 *   category = @Translation("Social & Contact"),
 * )
 */
class SocialContactInfo extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    // Website contact information.
    $form['contact_detail'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact information'),
      '#open' => FALSE,
      '#description' => $this->t('Contact information shows contact detail on the website. e.g. (Address, Phone, Email, etc.)'),
    ];
    // Contact title.
    $form['contact_detail']['contact_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title helps to show contact title. (Optional)'),
      '#default_value' => $this->configuration['contact_detail']['contact_title'],
    ];
    // Address details.
    $form['contact_detail']['address'] = [
      '#type' => 'details',
      '#title' => $this->t('Address'),
      '#open' => FALSE,
    ];
    $form['contact_detail']['address']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Enter your label (Optional).'),
      '#default_value' => $this->configuration['contact_detail']['address']['label'],
    ];
    $form['contact_detail']['address']['value'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Address'),
      '#resizable' => FALSE,
      '#format' => 'full_html',
      '#default_value' => $this->configuration['contact_detail']['address']['value']['value'],
    ];
    $form['contact_detail']['address']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($this->configuration['contact_detail']['address']['weight']) ? $this->configuration['contact_detail']['address']['weight'] : 0,
      '#delta' => 5,
      '#description' => $this->t('This helps to re-order contact fields (Optional).'),
    ];
    // E-mail details.
    $form['contact_detail']['email'] = [
      '#type' => 'details',
      '#title' => $this->t('E-mail'),
      '#open' => FALSE,
    ];
    $form['contact_detail']['email']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Enter your label (Optional).'),
      '#default_value' => $this->configuration['contact_detail']['email']['label'],
    ];
    $form['contact_detail']['email']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail'),
      '#description' => $this->t('Enter your email address (Required).'),
      '#default_value' => $this->configuration['contact_detail']['email']['value'],
    ];
    $form['contact_detail']['email']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($this->configuration['contact_detail']['email']['weight']) ? $this->configuration['contact_detail']['email']['weight'] : 1,
      '#delta' => 5,
      '#description' => $this->t('This helps to re-order contact fields (Optional).'),
    ];
    // Phone details.
    $form['contact_detail']['phone'] = [
      '#type' => 'details',
      '#title' => $this->t('Phone'),
      '#open' => FALSE,
    ];
    $form['contact_detail']['phone']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Enter your label (Optional).'),
      '#default_value' => $this->configuration['contact_detail']['phone']['label'],
    ];
    $form['contact_detail']['phone']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#description' => $this->t('Enter your phone number (Optional).'),
      '#default_value' => $this->configuration['contact_detail']['phone']['value'],
    ];
    $form['contact_detail']['phone']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($this->configuration['contact_detail']['phone']['weight']) ? $this->configuration['contact_detail']['phone']['weight'] : 2,
      '#delta' => 5,
      '#description' => $this->t('This helps to re-order contact fields (Optional).'),
    ];
    // Mobile details.
    $form['contact_detail']['mobile'] = [
      '#type' => 'details',
      '#title' => $this->t('Mobile'),
      '#open' => FALSE,
    ];
    $form['contact_detail']['mobile']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Enter your label (Optional).'),
      '#default_value' => $this->configuration['contact_detail']['mobile']['label'],
    ];
    $form['contact_detail']['mobile']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mobile'),
      '#description' => $this->t('Enter your mobile number (Optional).'),
      '#default_value' => $this->configuration['contact_detail']['mobile']['value'],
    ];
    $form['contact_detail']['mobile']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($this->configuration['contact_detail']['mobile']['weight']) ? $this->configuration['contact_detail']['mobile']['weight'] : 3,
      '#delta' => 5,
      '#description' => $this->t('This helps to re-order contact fields (Optional).'),
    ];
    // Fax details.
    $form['contact_detail']['fax'] = [
      '#type' => 'details',
      '#title' => $this->t('FAX'),
      '#open' => FALSE,
    ];
    $form['contact_detail']['fax']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Enter your label (Optional).'),
      '#default_value' => $this->configuration['contact_detail']['fax']['label'],
    ];
    $form['contact_detail']['fax']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fax'),
      '#description' => $this->t('Enter your fax number (Optional).'),
      '#default_value' => $this->configuration['contact_detail']['fax']['value'],
    ];
    $form['contact_detail']['fax']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($this->configuration['contact_detail']['fax']['weight']) ? $this->configuration['contact_detail']['fax']['weight'] : 4,
      '#delta' => 5,
      '#description' => $this->t('This helps to re-order contact fields (Optional).'),
    ];

    // Website social information.
    $form['social_detail'] = [
      '#type' => 'details',
      '#title' => $this->t('Social Information'),
      '#open' => FALSE,
      '#description' => $this->t('Social information shows social links on the website. e.g. (Facebook, LinkedIn, Twitter, etc.)'),
    ];
    // Social title.
    $form['social_detail']['social_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title helps to show social title. (Optional)'),
      '#default_value' => $this->configuration['social_detail']['social_title'],
    ];
    // Facebook details.
    $form['social_detail']['facebook'] = [
      '#type' => 'details',
      '#title' => $this->t('Facebook'),
      '#open' => FALSE,
    ];
    $form['social_detail']['facebook']['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#description' => $this->t('Enter your facebook profile link.'),
      '#attributes' => [
        'placeholder' => $this->t('https://www.facebook.com/username/'),
      ],
      '#default_value' => $this->configuration['social_detail']['facebook']['link'],
    ];
    $form['social_detail']['facebook']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class Name'),
      '#description' => $this->t('Enter your custom/fonts CSS classes (Optional).'),
      '#default_value' => $this->configuration['social_detail']['facebook']['class'],
    ];
    $form['social_detail']['facebook']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($this->configuration['social_detail']['facebook']['weight']) ? $this->configuration['social_detail']['facebook']['weight'] : 0,
      '#delta' => 5,
      '#description' => $this->t('This helps to re-order contact fields (Optional).'),
    ];
    // LinkedIn details.
    $form['social_detail']['linkedin'] = [
      '#type' => 'details',
      '#title' => $this->t('LinkedIn'),
      '#open' => FALSE,
    ];
    $form['social_detail']['linkedin']['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#description' => $this->t('Add your linkedin profile link.'),
      '#attributes' => [
        'placeholder' => $this->t('https://www.linkedin.com/username/'),
      ],
      '#default_value' => $this->configuration['social_detail']['linkedin']['link'],
    ];
    $form['social_detail']['linkedin']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class Name'),
      '#description' => $this->t('Enter your custom/fonts CSS classes (Optional).'),
      '#default_value' => $this->configuration['social_detail']['linkedin']['class'],
    ];
    $form['social_detail']['linkedin']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($this->configuration['social_detail']['linkedin']['weight']) ? $this->configuration['social_detail']['linkedin']['weight'] : 1,
      '#delta' => 5,
      '#description' => $this->t('This helps to re-order contact fields (Optional).'),
    ];
    // Twitter details.
    $form['social_detail']['twitter'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter'),
      '#open' => FALSE,
    ];
    $form['social_detail']['twitter']['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#description' => $this->t('Add your twitter profile link.'),
      '#attributes' => [
        'placeholder' => $this->t('https://twitter.com/username/'),
      ],
      '#default_value' => $this->configuration['social_detail']['twitter']['link'],
    ];
    $form['social_detail']['twitter']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class Name'),
      '#description' => $this->t('Enter your custom/fonts CSS classes (Optional).'),
      '#default_value' => $this->configuration['social_detail']['twitter']['class'],
    ];
    $form['social_detail']['twitter']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($this->configuration['social_detail']['twitter']['weight']) ? $this->configuration['social_detail']['twitter']['weight'] : 2,
      '#delta' => 5,
      '#description' => $this->t('This helps to re-order contact fields (Optional).'),
    ];
    // Youtube details.
    $form['social_detail']['youtube'] = [
      '#type' => 'details',
      '#title' => $this->t('Youtube'),
      '#open' => FALSE,
    ];
    $form['social_detail']['youtube']['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#description' => $this->t('Add your youtube profile link.'),
      '#attributes' => [
        'placeholder' => $this->t('https://www.youtube.com/username/'),
      ],
      '#default_value' => $this->configuration['social_detail']['youtube']['link'],
    ];
    $form['social_detail']['youtube']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class Name'),
      '#description' => $this->t('Enter your custom/fonts CSS classes (Optional).'),
      '#default_value' => $this->configuration['social_detail']['youtube']['class'],
    ];
    $form['social_detail']['youtube']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($this->configuration['social_detail']['youtube']['weight']) ? $this->configuration['social_detail']['youtube']['weight'] : 3,
      '#delta' => 5,
      '#description' => $this->t('This helps to re-order contact fields (Optional).'),
    ];
    // Pinterest details.
    $form['social_detail']['pinterest'] = [
      '#type' => 'details',
      '#title' => $this->t('Pinterest'),
      '#open' => FALSE,
    ];
    $form['social_detail']['pinterest']['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#description' => $this->t('Add your pinterest profile link.'),
      '#attributes' => [
        'placeholder' => $this->t('https://www.pinterest.com/username/'),
      ],
      '#default_value' => $this->configuration['social_detail']['pinterest']['link'],
    ];
    $form['social_detail']['pinterest']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class Name'),
      '#description' => $this->t('Enter your custom/fonts CSS classes (Optional).'),
      '#default_value' => $this->configuration['social_detail']['pinterest']['class'],
    ];
    $form['social_detail']['pinterest']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($this->configuration['social_detail']['pinterest']['weight']) ? $this->configuration['social_detail']['pinterest']['weight'] : 4,
      '#delta' => 5,
      '#description' => $this->t('This helps to re-order contact fields (Optional).'),
    ];
    // Instagram details.
    $form['social_detail']['instagram'] = [
      '#type' => 'details',
      '#title' => $this->t('Instagram'),
      '#open' => FALSE,
    ];
    $form['social_detail']['instagram']['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#description' => $this->t('Add your instagram profile link here.'),
      '#attributes' => [
        'placeholder' => $this->t('https://www.instagram.com/username/'),
      ],
      '#default_value' => $this->configuration['social_detail']['instagram']['link'],
    ];
    $form['social_detail']['instagram']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class Name'),
      '#description' => $this->t('Enter your custom/fonts CSS classes (Optional).'),
      '#default_value' => $this->configuration['social_detail']['instagram']['class'],
    ];
    $form['social_detail']['instagram']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($this->configuration['social_detail']['instagram']['weight']) ? $this->configuration['social_detail']['instagram']['weight'] : 5,
      '#delta' => 5,
      '#description' => $this->t('This helps to re-order contact fields (Optional).'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);

    $values = $form_state->getValues();
    // Contact elements.
    $contact_elements = ['phone', 'mobile', 'fax'];

    // Contact elements.
    $social_elements = [
      'facebook',
      'linkedin',
      'twitter',
      'youtube',
      'pinterest',
      'instagram',
    ];

    // Email address validation.
    $contact_email = $values['contact_detail']['email']['value'];
    if ($contact_email == '') {
      $form_state->setErrorByName('email', $this->t('Email field is required.'));
    }
    elseif ($contact_email != '' && !$this->isValidEmailAddress($contact_email)) {
      $form_state->setErrorByName('email', $this->t('The email address @mail is not valid.', ['@mail' => $contact_email]));
    }

    // Number fields validation of content elements.
    foreach ($contact_elements as $contact_name) {
      $raw_value = $form_state->getValue([
        'contact_detail',
        $contact_name,
        'value',
      ]);
      // Checking the entered value is numeric or not.
      if ((!empty($raw_value)) && (!preg_match('/^[0-9\-\(\)\/\+\s]*$/', $raw_value))) {
        $form_state->setError($contact_elements, $this->t('The @keys number @value is not valid, It must be numeric.', ['@keys' => $contact_name, '@value' => $raw_value]));
      }
      // Checking the length of numeric value.
      if ((!empty($raw_value)) && (strlen($raw_value) < 5 || strlen($raw_value) > 20)) {
        $form_state->setError($contact_elements, $this->t('The @keys number length should be in between 5 to 15 digit.', ['@keys' => $contact_name]));
      }
    }

    // Domain URL validation.
    foreach ($social_elements as $social_name) {
      $raw_value = $form_state->getValue(['social_detail', $social_name, 'link']);
      // Social fields domain validation.
      if ((!empty($raw_value)) && (!preg_match("/^(?:https?:\/\/)?(?:[a-z0-9-]+\.)*((?:[a-z0-9-]+\.)[a-z]+)/i", $raw_value))) {
        $form_state->setError($social_elements, $this->t('Please enter valid @keys link.', ['@keys' => $social_name]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    // Store values in below variables.
    $this->setConfigurationValue('contact_detail', $form_state->getValue('contact_detail'));
    $this->setConfigurationValue('social_detail', $form_state->getValue('social_detail'));

  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $configuration = $this->getConfiguration();
    // Assing both the value to custom variable.
    $contact_details = $configuration['contact_detail'];

    // Slice first value from contact and social info.
    if (isset($contact_details["address"]) && !empty($contact_details["address"]["value"]["value"])) {
      $contact_slice_val = array_slice($contact_details, 1);
    }
    else {
      $contact_slice_val = array_slice($contact_details, 2);
    }
    // Get contact fields after sorting.
    $sort_contact_values = $this->blockFieldsSortByWeight($contact_slice_val, 'weight');

    // Global variable.
    $contact_values = [];
    foreach ($sort_contact_values as $contact_key => $contact_value) {
      // Checking label is set or not.
      if (isset($contact_value['value']) && !empty($contact_value['value'])) {
        // Checking label if labels are blank then array key used as labels.
        if ($contact_value['label'] == '') {
          $contact_value['fieldset'] = Unicode::ucfirst($contact_key);
        }
        else {
          $contact_value['fieldset'] = $contact_value['label'];
        }
        // Added "mailto:" for email field.
        if ($contact_key == 'email') {
          $contact_value['value'] = '<a href="mailto:' . $contact_value['value'] . '" class="contact-' . $contact_key . '">' . $contact_value['value'] . '</a>';
        }
        // Added "tel:" for phone & mobile field.
        if ($contact_key == 'phone' || $contact_key == 'mobile') {
          $contact_value['value'] = '<a href="tel:' . $contact_value['value'] . '" class="contact-' . $contact_key . '">' . $contact_value['value'] . '</a>';
        }
        // Assigned changes label and values to new object variables.
        $contact_values[] = $contact_value;
      }
    }
    // Social medias.
    $social_details = $configuration['social_detail'];
    $social_slice_val = array_slice($social_details, 1);
    // Get contact fields after sorting.
    $sort_social_values = $this->blockFieldsSortByWeight($social_slice_val, 'weight');
    // Global variable.
    $social_values = [];
    foreach ($sort_social_values as $social_key => $social_value) {
      // Checking label is set or not.
      if (isset($social_value['link']) && !empty($social_value['link'])) {
        // Checking label if labels are blank then array key used as labels.
        $social_value['label'] = Unicode::ucfirst(str_replace('_', ' ', $social_key));
        // Assigned changes label and link to new object variables.
        $social_values[] = $social_value;
      }
    }
    // Assign array to $output variable.
    $output = [
      '#theme' => 'social_contact_info_block',
      '#contact_title' => $contact_details['contact_title'],
      '#contact_detail' => $contact_values,
      '#social_title' => $social_details['social_title'],
      '#social_detail' => $social_values,
      '#attached' => [
        'library' => ['social_contact_info/social_contact_info'],
      ],
    ];

    return $output;
  }

  /**
   * Implementation of sorting social and contact fields.
   */
  public function blockFieldsSortByWeight($array_values, $sub_key) {
    foreach ($array_values as $key => $array_value) {
      $sort_array_values[$key] = Unicode::strtolower($array_value[$sub_key]);
    }
    // Array sorting.
    asort($sort_array_values);

    foreach ($sort_array_values as $old_key => $values) {
      $sorting_value[$old_key] = $array_values[$old_key];
    }
    return $sorting_value;
  }

  /**
   * Create function to validate email.
   *
   * @param string $emailAddress
   *   The email address come from block contact email to validation it.
   *
   * @return bool
   *   The email address is true or false.
   */
  public static function isValidEmailAddress($emailAddress) {
    $validator = new EmailValidator();
    return $validator->isValid($emailAddress);
  }

}
