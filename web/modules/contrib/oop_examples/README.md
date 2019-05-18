OOP Examples
=======================

http://drupal.org/project/oop_examples

The project provides examples of object-oriented programming (OOP) in Drupal
starting from very basic ones. The examples are in sequence: each next
example improves the previous one.

OOP Examples 01 - 03 are applicable to Drupal 7 only and not present here,
because Drupal 8 has built-in PSR-4 support.

Examples available:

OOP Example 04. PSR-4 Namespaces.
PSR-4 is current Drupal 8 style of module namespaces. See 
https://www.drupal.org/node/2156625. Base class Vehicle and derived classes 
Car and Motorcycle have been created.

OOP Example 05. Business logic setup.
Class folders and namespaces structure has been created for further
business logic development.

OOP Example 06. Class field $color. Class constructor.
Class field $color has been added for Vehicle class. Default color
with translation t() is set up in class constructor because expression
is not allowed as field default value. Common method getDescription()
has been introduced.

OOP Example 07. Class field $doors.
Class field $doors has been added for Car class. Some car model derived
classes (Toyota) have been added.

OOP Example 08. ColorInterface.
Interface ColorInterface has been added. Two different class hierarchies:
Vehicle and Fruit implement this interface.

OOP Example 09. More interfaces.
Interfaces DriveInterface and JuiceInterface have been added for class
hierarchies Vehicle and Fruit respectively.

OOP Example 10. Interface Inheritance.
Two inherited interfaces have been made. VehicleInterface is a combination
of ColorInterface and DriveInterface. FruitInterface is a combination
of ColorInterface and JuiceInterface.

OOP Example 11. Dependency injection.
Class Driver has been introduced. Vehicle dependency is provided through
a class constructor. The dependency is passed as a DriveInterface, so future
implementations of the DriveInterface could be passed as well. For example,
Donkey or SpaceShip.

OOP Example 12. Factory.
Factory is an object, which creates another objects. This example provides
a ColorableFactory, which creates classes supporting ColorInterface.

OOP Example 13. Abstract Factory.
Abstract Factory is an object, which creates another objects. This example adds
ColorableFactoryInterface, and class factories that implements this interface.
