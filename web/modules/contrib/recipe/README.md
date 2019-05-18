[![Build Status](https://travis-ci.org/dcameron/recipe.svg?branch=8.x-2.x)](https://travis-ci.org/dcameron/recipe)

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Install
 * Contributing

INTRODUCTION
------------

Current Maintainers: dcam and jvandervort.
Original Author: Moshe Weitzman <weitzman@tejasa.com>

Recipe is a module for sharing cooking recipes.

This module provides a new content type, Recipe, for Drupal 8 sites.  Recipes
are a collection of fields that include typical data you would find in a recipe,
e.g. ingredients, instructions, cooking time, etc.  A view is provided at
/recipe for viewing your recipe collection.  It can be customized to your
preferences or disabled.

Recipe includes several sub-modules.  The most important of these is Ingredient.
The Ingredient module does two things: it provides an Ingredient entity type
and an entity reference field for the ingredients.  The Recipe content type is
dependent on that field.  The field can also be used to define your own custom
recipe content types.  The other sub-modules provide the ability to import
recipes and display or export them in other formats.

This product is RecipeML compatible.

REQUIREMENTS
------------

 * Drupal 8
 * Node module
 * Path module
 * RDF module
 * Views module

INSTALL
-------

 1. Download the Recipe module into the /modules directory in your Drupal site
    root.
 2. Enable Recipe at /admin/modules in your Drupal site.  You will be prompted
    to enable any of its disabled dependencies.
 3. Enable permissions to view or administer Ingredients and Recipe nodes at
    /admin/people/permissions.  Permission to 'View ingredients' is required for
    users to be able to view the content of Recipes' Ingredients field.
 4. Visit /node/add/recipe to start creating Recipes!

Some settings for the module can be configured at /admin/config/content/recipe.

CONTRIBUTING
------------

Feel free to submit patches in the Drupal.org issue queue or via Github pull
requests. If you can, please include test coverage for your contributions.
