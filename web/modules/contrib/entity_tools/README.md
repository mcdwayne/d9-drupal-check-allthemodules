# Entity Tools

Entity Tools provides syntactic sugar over entities for 
custom modules and themes (Twig templates in general).
It comes with helpers to select, load and display content 
or configuration entities.

The goal is to try to improve the DX by providing an uniform way
to work with entities, without using strings parameters
so we can safely rely on class constants or methods instead.

Select, load and display helpers are meant to be independent.
You can still execute a core EntityQuery that gets you the entity
ids and then rely on the load and display helpers.

**This module is currently under active development, constant refactoring
and subject to frequent API change, so you shouldn't use it for other purpose
than testing until the dev release is created.**

#### Select and load nodes

If translation is enabled, get only the current interface 
translation language.
Then return nodes as a plain unordered list of teasers.
```
$query = NodeQuery();
$query->getLatest(); // and display sticky first, by default
$query->limit(3);
$nodes = $entityTools->getNodes('article', $query);
$build['node_teasers'] = $this->entityTools->getEntitiesList($nodes);
```

#### Load and build a render array for a node teaser

```
$entityTools->node('1', 'teaser');
```

Ok, I want the same for terms, users, blocks, views, menus, fields!
And btw, give me something for my links.

## Use cases

When you cannot use Views easily:
- Display several entity types (e.g. a mixture of nodes and users).
- Group several queries results but do not want extra markup from several 
blocks.
- Use several view modes for the same query 
(e.g. a highlight teaser for the first row only). 
- Quickly display a node excerpt somewhere in a template.
- Contextual complex conditions that cannot or are hard to be defined via Views.
- Delegate a UI maintainable query to Views and use the Master query for
custom display. 
- Embed Entities, when a module based context is given: Views (dynamic filter),
Blocks (content, plugin, config), Webforms.

If you want syntactic sugar on the entity, translation, field or cache API.

## Features

This module does nothing on its own, it provides helpers as services for modules
and Twig extensions to quickly implement common programming tasks like:

- Fetching and translating Content or Configuration Entities, the easy way.
- Block node loader: select a node from its title then render the desired 
view mode.
Useful for e.g. 'about us' teasers.
- Empty Formatter for Views, guaranteed divitis free.
- Create all type of links: from path, from route, anchor, ...
- Pseudo Fields (@todo).
- ...

In brief, it abstracts the complexity of entity translation, entity types and
bundles across entity types by providing a facade.


## Documentation

- [Documentation for Modules](https://goo.gl/4jkwTk)
- [Documentation for Themes](https://goo.gl/XaE9ap)

## Examples

### Entities

Available from /entity_tools/examples/entity
(get code in EntityExampleController)

@todo add examples

### Twig

Available from /entity_tools/examples/twig

### Blocks and Nodes

- **ExampleNodeBlock**: get a node from its title then select the desired
view mode (defaults to teaser).
- **PromotedNodesBlock**: lists the 3 latest article nodes, 
displays the first as a highlighted view mode (defaults to full) and the
2 others as another view mode (defaults to teaser).
- **PromotedViewsBlock**: lists nodes selected from a View, 
displays the first as a highlighted view mode (defaults to full) and the
2 others as another view mode (defaults to teaser).
