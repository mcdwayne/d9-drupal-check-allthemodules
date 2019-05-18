<?php

namespace Drupal\opcachectl\Twig\Extension;

/**
 * Class TypeTest
 *
 * All credits go to https://github.com/victor-in/Craft-TwigTypeTest !
 *
 * @see https://github.com/victor-in/Craft-TwigTypeTest/blob/master/twigtypetest/twigextensions/TwigTypeTestTwigExtension.php
 *
 * @package Drupal\opcachectl\Twig\Extension
 */
class TypeTest extends \Twig_Extension {

	public function getName()	{
		return 'type_test';
	}

	public function getTests() {
		return [
			'of_type' => new \Twig_Test_Method($this, 'ofType')
		];
	}

	public function getFilters() {
		return [
			'get_type' => new \Twig_Filter_Method($this, 'getType')
			];
	}

	function ofType($var, $typeTest=null, $className=null)	{
		switch ($typeTest)
		{
			default:
				return false;
				break;

			case 'array':
				return is_array($var);
				break;

			case 'bool':
				return is_bool($var);
				break;

			case 'class':
				return is_object($var) === true && get_class($var) === $className;
				break;

			case 'float':
				return is_float($var);
				break;

			case 'int':
				return is_int($var);
				break;

			case 'numeric':
				return is_numeric($var);
				break;

			case 'object':
				return is_object($var);
				break;

			case 'scalar':
				return is_scalar($var);
				break;

			case 'string':
				return is_string($var);
				break;
		}
	}

	public function getType($var) {
		return gettype( $var );
	}

}
