# Organic Groups : Site Taxonomy
Module to support sharing a common vocabulary in multiple Sites. Each Site has
its own taxonomy terms.


## Functionality
This module provides:
* Support global vocabularies with Site specific terms.
* Manage terms per Site.
* Select only from terms within the Site when creating content.
* A token provider for terms within a Site (used for path aliases). Only
  available when the **og_sm_path module** is enabled.
* An Organic Groups context handler to get the context by the Sites a taxonomy
  term belongs to.

> **NOTE** : vocabulary terms will be automatically filtered to only those
> related to the current Site context.
>
> Make sure that you have setup the context detection properly.
> See og_sm_context and og_sm_path modules.



## Requirements
* Organic Groups Site Manager
* Taxonomy



## Installation
1. Enable the module.
2. Create a global vocabulary.
3. Add the Organic Groups audience field to the vocabulary.
4. Grant Organic Groups roles the proper taxonomy permissions.
5. Setup the OG context providers on admin/config/group/context:
   - Enable the "**Site Taxonomy Term**" detection method.


### Configure auto path aliases for terms
The module adds extra tokens for taxonomy paths (only when the og_sm_path module
is also enabled).

1. Configure the alias for content on admin/config/search/path/patterns:
   - Overall or per Vocabulary  : `[term:site-path]/...`.


### TIP: hide the OG audience field
You can hide the OG Audience field when creating/editing Site terms within a
Site context.

* Install the entityreference_prepopulate module and edit the OG Audience field
  of the vocabularies.
* Enable "Entity reference prepopulate".
* Set the action to "Hide field".
* Check "Apply action on edit".
* Enable OG Context as provider and move it to the first position.
* Disable URL as provider.



## API
### Get all the vocabulary names
Get a list of all vocabulary names of the vocabularies who have an Organic
Groups Audience field.

Will return the vocabulary labels keyed by their machine name.

```php
$names = og_sm_taxonomy_get_vocabulary_names();
```


### Get all vocabularies
Get all the vocabulary objects that have the Group Audience field.

Will return the vocabulary objects keyed by their machine name.

```php
$vocabularies = og_sm_taxonomy_get_vocabularies();
```


### Check if a vocabulary has the OG Audience field
Check if the given vocabulary machine name is a vocabulary with the Organic
Groups Audience field.

```php
$has_og_audience = og_sm_taxonomy_is_vocabulary('machine_name');
```


### Get all the Sites a Term belongs to
Get all the Site nodes a Taxonomy Term belongs to.

```php
$sites = og_sm_taxonomy_term_get_sites($term);
```


### Get the Site a Term belongs to
Get the Site node a Taxonomy Term belongs to.

If a term belongs to multiple Sites, only the first will be returned.

```php
$site = og_sm_taxonomy_term_get_site($term);
```


### Check if a term is used within Site(s)
Check if the Term is used within one or more Sites.

```php
$is_site_term = og_sm_taxonomy_term_is_site_term($term);
```


### Check if a term belongs to a Site
Check if the term belongs to the given Site object.

```php
$is_member = og_sm_taxonomy_term_is_site_member($term, $site);
```


### Check if a user can manage a vocabulary
Check if a user can manage a vocabulary within the given Site context:

```php
$has_access = og_sm_taxonomy_admin_vocabulary_access($site, $vocabulary);
```


### Check if a user can edit/delete a term
Check if a user can edit and delete a given term.

```php
$has_access = og_sm_taxonomy_term_edit_access($term);
```


### Get all vocabulary terms for a Site
Get an array of taxonomy terms by the vocabulary and Site.

```php
$terms = og_sm_taxonomy_get_vocabulary_terms_by_site($vocabulary, $site);
```
