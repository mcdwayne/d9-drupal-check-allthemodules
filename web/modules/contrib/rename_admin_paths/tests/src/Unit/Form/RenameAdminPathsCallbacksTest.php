<?php

namespace Drupal\Tests\rename_admin_paths\Unit\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\rename_admin_paths\Form\RenameAdminPathsCallbacks;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

class RenameAdminPathsCallbacksTest extends UnitTestCase
{
  public function testValidatePathWithoutValue()
  {
    $element = [];
    $this->getCallbackInstance()->validatePath($element, $this->getInvalidFormState());
  }

  /**
   * @dataProvider validValues
   */
  public function testWithValidValue($value)
  {
    $element = ['#value' => $value];
    $this->getCallbackInstance()->validatePath($element, $this->getValidFormState());
  }

  /**
   * @dataProvider invalidValues
   */
  public function testWithInvalidValue($value)
  {
    $element = ['#value' => $value];
    $this->getCallbackInstance()->validatePath($element, $this->getInvalidFormState());
  }

  /**
   * @return array
   */
  public function validValues()
  {
    return [
      ['backend'],
      ['back-end'],
      ['Backend'],
      ['Back-End'],
      ['Back_End'],
      ['Back-End_123'],
      ['admin2'],
      ['user2']
    ];
  }

  /**
   * @return array
   */
  public function invalidValues()
  {
    return [
      ['backend!'],
      ['back@end'],
      ['(Backend)'],
      ['Back~End'],
      ['Back=End'],
      ['Back-End+123'],
      ['admin'],
      ['user'],
      ['Admin']
    ];
  }

  /**
   * @return \Drupal\rename_admin_paths\Form\RenameAdminPathsCallbacks
   */
  private function getCallbackInstance()
  {
    $translator = $this->createMock(TranslationInterface::class);
    $translator->method('translateString')->willReturn('Error');

    $callbacks = new RenameAdminPathsCallbacks();
    $callbacks->setStringTranslation($translator);

    return $callbacks;
  }

  /**
   * @return \Drupal\Core\Form\FormStateInterface
   */
  private function getValidFormState()
  {
    $form_state = $this->prophesize(FormStateInterface::class);
    $form_state->setError()->shouldNotBeCalled();

    return $form_state->reveal();
  }

  /**
   * @return \Drupal\Core\Form\FormStateInterface
   */
  private function getInvalidFormState()
  {
    $form_state = $this->prophesize(FormStateInterface::class);
    $form_state->setError(Argument::any(), Argument::any())->shouldBeCalled();

    return $form_state->reveal();
  }
}
