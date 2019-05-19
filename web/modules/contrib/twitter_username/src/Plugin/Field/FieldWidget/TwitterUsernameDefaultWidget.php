<?php

namespace Drupal\twitter_username\Plugin\Field\FieldWidget;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\twitter_username\Plugin\Field\FieldType\TwitterUsername;
use GuzzleHttp\Exception\RequestException;

/**
 * Plugin implementation of the 'twitter_username_textfield' widget.
 *
 * @FieldWidget(
 *   id = "twitter_username_textfield",
 *   label = @Translation("Twitter username textfield"),
 *   field_types = {
 *     "twitter_username"
 *   },
 * )
 */
class TwitterUsernameDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => 60,
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => TwitterUsername::TWITTER_USERNAME_MAX_LENGTH,
      '#field_prefix' => "@",
      '#element_validate' => array(array($this, 'validateElement')),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'validate_existance' => 0,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['validate_existance'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Ensure the twitter username exists'),
      '#description' => $this->t('Use the Twitter API to ensure the username actually exists. Note that this is an expensive network call. To avoid timeouts only use this when you have a limited amount of field values. If the Twitter API is not reachable a watchdog error will be logged and the name accepted.'),
      '#default_value' => $this->getSetting('validate_existance'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $validate = $this->getSetting('validate_existance') ? 'On' : 'Off';
    $summary[] = $this->t('Validate twitter username: @validate', ['@validate' => $validate]);
    return $summary;
  }

  /**
   * Form element validate handler for Twitter username.
   */
  public static function validateElement($element, FormStateInterface $form_state) {
    if ($twitter_username = $element['#value']) {
      // Ensure the username contains only valid characters.
      if (!preg_match('/^[A-Za-z0-9_]+$/', $twitter_username)) { // '/^\w+$/'
        $form_state->setError($element, t('Invalid twitter username (alphanumerics only)'));
      }
    }

    // I'm sure this is not the way to get the field widget settings..
    $field_settings = $element['#element_validate'][0][0];

    if ($validate = $field_settings->getSetting('validate_existance')) {
      try {
        // Query the Twitter User page.
        // Since v1.1, we could not request the API with OAuth token.
        $client = \Drupal::httpClient();
        $client->get('https://twitter.com/' . $twitter_username, ['method' => 'HEAD']);
      }
      catch (RequestException $e) {
        $response_code = $e->getCode();

        // HTTP status code 404 means the username doesn't exist.
        if ($response_code == 404) {
          $form_state->setError($element, t('The twitter username doesn\'t exist.'));
        }
        // Log and display an  error if we get an unexpected status code.
        else {
          $message = "The Twitter API returned the unexpected status code %code. That means it's not guaranteed the username %username actually exists.";
          $message_args = ['%code' => $response_code, '%username' => $twitter_username];

          drupal_set_message(t("The Twitter API returned the unexpected status code %code. That means it's not guaranteed the username %username actually exists.", $message_args), 'warning');
          \Drupal::logger('twitter_username')->notice($message, $message_args);
        }
      }
    }
  }
}
