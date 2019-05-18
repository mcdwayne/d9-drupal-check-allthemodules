# DISRUPT TOOLS

This suite is primarily a set of APIs and tools to improve
the developer experience.

For the moment, it includes the following tools:
  - ImageStyle -- tools to make it easy for modules to generate Image Styles,
  - SlugManager -- tools to make it easy to generate and manage custom Slug.
  - TaxonomyHelpers -- Service to make it easy to work with Taxonomy Term.

## Disrupt Tools versions

Disrupt Tools is only available for Drupal 8 !
The module is ready to be used in Drupal 8, there are no known issues.

## Dependencies

The Drupal 8 version of Disrupt Tools requires nothing ! Feel free to use it.

## Supporting organizations

This project is sponsored by Antistatique. We are a Swiss Web Agency,
Visit us at [www.antistatique.net](https://www.antistatique.net) or Contact us.

## Examples

**ImageStylesGenerator**

```php
// Load it with injection on production
$isg = Drupal::service('disrupt_tools.image_style_generator');

// Generate and retrieve image style from Field:
$styles = $isg->fromField($node->field_image, ['thumb' => 'thumbnail']);
var_dump($styles);

// Generate and retrieve image style from File ID:
$fid = $node->field_image->entity->id();
$styles = $isg->fromFile($fid, ['thumb' => 'thumbnail']);
var_dump($styles);
```

**SlugManager**

```php
// Load it with injection on production
$sm = Drupal::service('disrupt_tools.slug_manager');

// Retrieve slug from taxonomy alias url:
$slug = $sm->taxonomy2Slug($term, '/work/');
var_dump($slug);

// Retrieve term from slug of name alias:
$term = $sm->slug2Taxonomy('it', '/work/');
var_dump($term);
```

**TaxonomyHelpers**

```php
// Load it with injection on production
$th = Drupal::service('disrupt_tools.taxonomy_helpers');

// Get all the siblings terms of a given taxonomy tid.
$siblings = $sm->getSiblings(1);
var_dump($siblings);

// Get the top parent term of given taxonomy term.
$parent = $sm->getTopParent(3);
var_dump($parent);

// Retrieve the depth of a given term id into his vocabulary.
$depth = $sm->getDepth(1);
var_dump($depth);

// Retrieve the all parents of a given term id into his vocabulary.
$parents = $sm->getParents(1);
var_dump($parents);

// Finds all terms in a given vocabulary ID and filter them by conditions.
$em = \Drupal::service('entity.manager');
$this->taxo = $em->getStorage('taxonomy_term');
$flat = $th->loadTreeBy('tags', 0, ['field' => 'value'], NULL);
var_dump($flat);

// Converting a flat array of Drupal\taxonomy\Entity\Term into a nested tree.
$em = \Drupal::service('entity.manager');
$this->taxo = $em->getStorage('taxonomy_term');
$flat = $this->taxo->loadTree('tags', 0, NULL, TRUE);
$nested = $th->buildTree($tree);
var_dump($nested);
```

**MenuHelpers** *(soon)*

```php
// Get the top parent in the menu of the current page.

// Get the full active trail.

```
