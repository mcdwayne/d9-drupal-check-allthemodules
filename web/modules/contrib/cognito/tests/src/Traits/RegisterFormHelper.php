<?php

namespace Drupal\Tests\cognito\Traits;

use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\Form\Email\AdminRegisterForm;
use Drupal\cognito\Form\Email\ProfileForm;
use Drupal\cognito\Form\Email\RegisterForm;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\Tests\cognito\Unit\CognitoMessagesStub;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Trait to help test the registration form.
 */
trait RegisterFormHelper {

  /**
   * Constructs the register form.
   *
   * @param \Drupal\cognito\Aws\CognitoInterface $cognito
   *   The cognito service.
   * @param \Drupal\externalauth\ExternalAuthInterface $externalAuth
   *   (Optional) The externalAuth service.
   *
   * @return \Drupal\cognito\Form\Email\RegisterForm
   *   The constructed register form.
   */
  protected function getRegisterForm(CognitoInterface $cognito, ExternalAuthInterface $externalAuth = NULL) {
    return $this->injectFormDependencies(RegisterForm::class, $cognito, $externalAuth);
  }

  /**
   * Constructs the admin register form.
   *
   * @param \Drupal\cognito\Aws\CognitoInterface $cognito
   *   The cognito service.
   *
   * @return \Drupal\cognito\Form\Email\AdminRegisterForm
   *   The constructed register form.
   */
  protected function getAdminRegisterForm(CognitoInterface $cognito) {
    return $this->injectFormDependencies(AdminRegisterForm::class, $cognito);
  }

  /**
   * Constructs the profile form.
   *
   * @param \Drupal\cognito\Aws\CognitoInterface $cognito
   *   The cognito service.
   *
   * @return \Drupal\cognito\Form\Email\ProfileForm
   *   The constructed register form.
   */
  protected function getProfileForm(CognitoInterface $cognito) {
    return $this->injectFormDependencies(ProfileForm::class, $cognito);
  }

  /**
   * Helper to inject all the dependencies needed by AccountForm.
   *
   * @param string $class
   *   The form class to instantiate.
   * @param \Drupal\cognito\Aws\CognitoInterface $cognito
   *   The cognito service.
   * @param \Drupal\externalauth\ExternalAuthInterface $externalAuth
   *   (Optional) The externalAuth service.
   *
   * @return mixed
   *   The instantiated form.
   */
  protected function injectFormDependencies($class, CognitoInterface $cognito, ExternalAuthInterface $externalAuth = NULL) {
    $entityRepository = $this->createMock(EntityRepositoryInterface::class);
    $languageManager = $this->createMock(LanguageManagerInterface::class);
    $entityTypeBundle = $this->createMock(EntityTypeBundleInfoInterface::class);
    $time = $this->createMock(TimeInterface::class);
    $translation = $this->getStringTranslation();
    $externalauth = $externalAuth ?: $this->createMock(ExternalAuthInterface::class);
    $authmap = $this->createMock(AuthmapInterface::class);
    $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

    return new $class($entityRepository, $languageManager, $entityTypeBundle, $time, $translation, $cognito, new CognitoMessagesStub(), $externalauth, $authmap, $eventDispatcher);
  }

  /**
   * Returns a stub translation manager that just returns the passed string.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\StringTranslation\TranslationInterface
   *   A mock translation object.
   */
  public function getStringTranslation() {
    $translation = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');
    $translation->expects($this->any())
      ->method('translate')
      ->willReturnCallback(function ($string, array $args = [], array $options = []) use ($translation) {
        // @codingStandardsIgnoreStart
        return new TranslatableMarkup($string, $args, $options, $translation);
        // @codingStandardsIgnoreEnd
      });
    $translation->expects($this->any())
      ->method('translateString')
      ->willReturnCallback(function (TranslatableMarkup $wrapper) {
        return $wrapper->getUntranslatedString();
      });
    $translation->expects($this->any())
      ->method('formatPlural')
      ->willReturnCallback(function ($count, $singular, $plural, array $args = [], array $options = []) use ($translation) {
        $wrapper = new PluralTranslatableMarkup($count, $singular, $plural, $args, $options, $translation);
        return $wrapper;
      });
    return $translation;
  }

}
