<?php

namespace Drupal\Tests\whitelabel\Traits;

use Drupal\Tests\BrowserTestBase;
use Drupal\whitelabel\Entity\WhiteLabel;
use Drupal\whitelabel\WhiteLabelInterface;

/**
 * Provides methods to create additional white label entities.
 *
 * This trait is meant to be used only by test classes extending
 * \Drupal\simpletest\TestBase.
 */
trait WhiteLabelCreationTrait {

  /**
   * Holds the white label provider.
   *
   * @var \Drupal\whitelabel\WhiteLabelProviderInterface
   */
  protected $whiteLabelProvider;

  /**
   * Sets the provided white label.
   *
   * @param \Drupal\whitelabel\WhiteLabelInterface $white_label
   *   The white label entity.
   */
  protected function setCurrentWhiteLabel(WhiteLabelInterface $white_label) {
    if ($this instanceof BrowserTestBase) {
      $this->drupalGet('/whitelabel-test/set/' . $white_label->getToken());
    }
    else {
      if (empty($this->whiteLabelProvider)) {
        $this->whiteLabelProvider = \Drupal::service('whitelabel.whitelabel_provider');
      }
      $this->whiteLabelProvider->setWhiteLabel($white_label);
    }
  }

  /**
   * Unsets the active white label.
   */
  protected function resetWhiteLabel() {
    if ($this instanceof BrowserTestBase) {
      $this->drupalGet('/whitelabel-test/set/reset');
    }
    else {
      if (empty($this->whiteLabelProvider)) {
        $this->whiteLabelProvider = \Drupal::service('whitelabel.whitelabel_provider');
      }
      $this->whiteLabelProvider->resetWhiteLabel();
    }
  }

  /**
   * Creates a white label based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the white label, as used
   *   in entity_create(). Override the defaults by specifying the key and value
   *   in the array, for example:
   *
   *   @code
   *     $this->drupalCreateWhiteLabel(array(
   *       'token' => 'custom-token',
   *       'uid' => 2,
   *     ));
   *   @endcode
   *   The following defaults are provided:
   *   - token: Random string:
   *     @code
   *       $settings['token'] = $this->randomMachineName(),
   *     @endcode
   *   - uid: The currently logged in user, or anonymous.
   *
   * @return \Drupal\whitelabel\WhiteLabelInterface
   *   The created white label entity.
   */
  protected function createWhiteLabel(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'token' => $this->randomMachineName(),
      'uid' => \Drupal::currentUser()->id(),
    ];
    $white_label = WhiteLabel::create($settings);
    $white_label->save();

    return $white_label;
  }

}
