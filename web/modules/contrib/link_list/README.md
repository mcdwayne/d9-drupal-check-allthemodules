# Link List

Link List is a module which extracts and lists all links of a node. The list is
available as a block which can be embedded in the region of your choice.

## INTRODUCTION
If you write an article it probably contains a lot of links. It would be a big
benefit for your reader to get a list of the links at the bottom of the article
(or any other position).

## REQUIREMENTS
No special requirements needed. A clean Drupal 8 instance is a good start.

## RECOMMENDED MODULES
If custom data should be added (example: see CUSTOM TEMPLATE) the module
Linkit (https://www.drupal.org/project/linkit) is recommended.

## INSTALLATION
1. Install the Link List module
2. Add the Link List block in the region of your choice

## CONFIGURATION
When adding or editing the List Link block you have two optional configuration
options provided by Link List.

1. Text before: Add a text which will be rendered before the list. Leave empty
to render no text before.
2. Target: Add a target which should be added to the rendered Link List. Leave
empty to render no target attribute.
3. Apply on links with class: If you only want to list links which have a class,
you can set the class here. Leave empty to list all links.

## CUSTOM TEMPLATE
By default, Link List will use the template link-list.html.twig which renders a
definition list including value, title and the link.

For example:
```html
<a href="https://drupal.org" title="Drupal.org is a great place.">
    Drupal.org
</a>
```

will be extracted and rendered as:
```html
<dl>
    <dt>Drupal.org</dt>
    <dd>Drupal.org is a great place.</dd>
    <dd><a href="https://drupal.org">https://drupal.org</a></dd>
</dl>
```


You can modify (not recommended) or hook the template as you like. The module
itself will parse all attributes - you can add custom attributes link
data-accessed-at which will be available in the twig template as

```twig
link['data-accessed-at']
```
