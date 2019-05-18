<?php

namespace Drupal\formfactorykits\Services;

use Drupal\kits\Services\KitsInterface;

/**
 * Interface FormFactoryKitsInterface
 *
 * @package Drupal\formfactorykits\Kits
 */
interface FormFactoryKitsInterface extends KitsInterface {

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Button\ButtonKit
   */
  public function button($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Checkboxes\CheckboxKit
   */
  public function checkbox($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Checkboxes\CheckboxesKit
   */
  public function checkboxes($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Color\ColorKit
   */
  public function color($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Container\ContainerKit
   */
  public function container($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Date\DateKit
   */
  public function date($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Date\DateListKit
   */
  public function dateList($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Date\DateTimeKit
   */
  public function dateTime($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Container\DetailsKit
   */
  public function details($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Text\EmailKit
   */
  public function email($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Media\ManagedFile\FileKit
   */
  public function file($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Media\FileKit
   */
  public function fileUnmanaged($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Markup\HeadingKit
   */
  public function heading($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\HiddenKit
   */
  public function hidden($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Media\ManagedFile\ImageKit
   */
  public function image($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Button\ImageButtonKit
   */
  public function imageButton($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Markup\ItemKit
   */
  public function item($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Markup\MarkupKit
   */
  public function markup($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Entity\NodeAutoCompleteKit
   */
  public function nodeAutoComplete($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Text\Number\NumberKit
   */
  public function number($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Text\PasswordKit
   */
  public function password($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Text\PasswordConfirmKit
   */
  public function passwordConfirm($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Text\PathKit
   */
  public function path($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Radios\RadiosKit
   */
  public function radios($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Text\Number\RangeKit
   */
  public function range($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Text\SearchKit
   */
  public function search($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Select\SelectKit
   */
  public function select($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Button\SubmitButtonKit
   */
  public function submit($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Container\Table\TableKit
   */
  public function table($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Container\Table\TableRowKit
   */
  public function tableRow($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Table\TableSelectKit
   */
  public function tableSelect($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Text\Number\TelephoneKit
   */
  public function telephone($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Entity\TaxonomyTermSelectKit
   */
  public function taxonomyTermSelect($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Markup\TextKit
   */
  public function text($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Text\TextFieldKit
   */
  public function textField($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Text\TextAreaKit
   */
  public function textArea($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Entity\UserAutoCompleteKit
   */
  public function userAutoComplete($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array|string $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\Text\UrlKit
   */
  public function url($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Field\ValueKit
   */
  public function value($id = NULL, $parameters = [], $context = []);

  /**
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   *
   * @return \Drupal\formfactorykits\Kits\Container\Tabs\VerticalTabsKit
   */
  public function verticalTabs($id = NULL, $parameters = [], $context = []);
}
