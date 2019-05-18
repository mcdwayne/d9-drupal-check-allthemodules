# Element Class Formatter

## Overview
A collection of field formatters which add classes to various elements 
(as opposed to the wrapper markup).

## Features
* Text wrapper - add a wrapper HTML tag with classes to any Text field.
* Link class - add classes to any Link fields anchor tag.
* Entity reference label class - add classes to the Label display of an 
Entity Reference field.
* Email and Telephone class - add classes to the anchor tag of a Telephone 
and Mail link.
* File, Image and Responsive Image class - add classes to a File link or 
Image tag.
* Lists - create a HTML list out of any multi-cardinality field with 
classes on it.

## Requirements
Field

## Similar modules
[Field formatter class](https://www.drupal.org/project/field_formatter_class) 
provides field formatters to add classes to the outer HTML wrapper for any field 
display. This module differs in that it adds the classes to the field element
itself, instead of the fields wrapper. Wrapper classes are easier to add via 
Twig than element classes are.
