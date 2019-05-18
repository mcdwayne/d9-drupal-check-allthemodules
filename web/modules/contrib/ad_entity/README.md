# Advertising Entity

A Drupal module which provides consolidated integration for various types of
advertising instances (mainly advertisement).

## Why you might want consolidated ad management
- All your ads can be turned off on demand, and can be
  attached with targeting via context fields.
- Always be able to replace your existing ads by others of any type
  with just a few clicks, without losing any context.
- All your ads respect your theme's breakpoints in the same way
  by using the Theme Breakpoints JS module.
- Configure consent-aware personalization: This module offers
  a unified way to configure GDPR compliant ads,
  optionally combined with the "Consent" or "EU Cookie Compliance" module.

## Available provider integrations

- <a href="https://www.doubleclickbygoogle.com/">Doubleclick for Publishers
  (DFP)</a>, included in this module.
- <a href="http://www.adtechfactory.com/">AdTech Factory</a>, included in
  this module.
- <a href="https://www.drupal.org/project/ad_entity_vi">Video Intelligence</a>
- <a href="https://www.drupal.org/project/ad_entity_smart">Smart AdServer</a>
- For advanced use cases, the already included ad_entity_generic submodule
  provides a skeleton to manage ads of custom and arbitrary types.

Have a further integration based on ad_entity and want to list it here?
<a href="https://www.drupal.org/node/add/project-issue/ad_entity">
Please tell us!</a>

# Requirements

- The <a href="https://www.drupal.org/project/entity">Entity API</a> module.
- If you want to setup ads, which respect your theme breakpoints, then you need
  the <a href="https://github.com/BurdaMagazinOrg/module-theme_breakpoints_js">
  Theme Breakpoint JS</a> module.

# Quick start

- Install this module.
- Set permissions to view ads. As there are two separate entity types
  (ad_entity and ad_display) involved, make sure your roles do have granted
  view access to both types by setting: "View Advertising entities" and
  "View Display configs for Advertisement".
- You need at least one further module which defines an Advertising type.
  The ad_entity_dfp module for example enables you to create
  types of Doubleclick for Publishers (DFP) advertisement.
  This module can be found in the 'modules' subfolder. 
- Configure global settings for Advertising entities
  at admin/structure/ad_entity/global-settings.
- Create and manage your Advertising entities at admin/structure/ad_entity.
  When your theme has multiple breakpoints, you can create one entity for each.
- Create and manage Display configurations for your Advertisement at
  admin/structure/ad_entity/display. Once you've created a Display config,
  you'll be able to place it as a block at admin/structure/block.

# About view handlers

For each advertising type, there are usually different view handlers to choose:
 - A default HTML view, which is the way for viewing the ads on a regular page.
 - An iframe, which could be used for feeds or publishing on external sources.
 - Facebook Instant Articles (FIA), which is basically an iframe as well.
 - Accelerated Mobile Pages (AMP),
   currently only provided by the DFP submodule.

# About Advertising contexts

A given Advertising context is able to extend or manipulate the information and
defined behavior for Advertising entities being displayed on a page.

Advertising context can be defined by content such as nodes and taxonomy terms.

To enable users defining contexts, the 'Advertising context' field must be
attached to an entity type. Choose unlimited cardinality to let users add
multiple contexts.

The field provides different formatters for the Advertising context.
In the 'Manage display' section of your entity type, place the context field
into the content region to deliver the user-defined context on the page.

## Types of field formatters for delivering Advertising contexts

The available field formatters differ in which context will be delivered.
To just deliver the user-defined context from the given entity,
choose 'Context from entity only'.

If you want to include any context by referenced entities,
choose 'Context from entity with references'.

If you want to include context being attached to terms
which belong to a node, choose
'Context from node with taxonomy (without trees)'.
To include the taxonomy tree,
you can additionally choose between the 'tree aggregation'
and 'tree override' formatter variants.

Using tree aggregation means that all contexts from a term's ancestors
will be included. Please note that such operation could be expensive.

Using tree override means that the first context found in the taxonomy tree
will be used, in case the given term has no context defined by itself.
The first ancestor of the term having a context will be used (bottom-up).
Please note that this operation could be expensive as well.

For taxonomy terms, you can use tree aggregation or tree override as well.

## Appliance modes for Advertising contexts

When choosing the proper field formatter for delivering the Advertising context,
you may additionally choose between <em>frontend appliance</em>
and <em>backend appliance</em> mode. Frontend appliance mode will use
Javascript to apply the delivered context, whereas backend appliance mode
will do this job on the server-side. Backend appliance mode is generally
the recommended mode to choose (next section explains why).

The frontend appliance mode used to be the recommended mode in favor of
saving resources on the server side. It has been shown though that this
would not become true unless your appliance process is very complex.
Most of the appliance processes are not complex though, and if so,
you would need to reconsider the whole process since you don't want a slow
frontend too. The frontend appliance mode still exists, but may be considered
to be deprecated for the next major release (2.x).
If you want to apply Advertising contexts on iframes or feeds,
you'll need to use the backend appliance mode on your field formatters.

# Tips for developers

## Javascript Events

As an alternative way for adjusting the display and behavior of your ads,
the Advertising implementations might provide events for you.

Following events are provided in general:
 - When containers for Advertising entities have been collected from context:
   `adEntity:collected` with an `event.detail` object
   holding `collected, newcomers, context, settings`.
 - After Advertisement has been initialized inside the container:
   `adEntity:initialized` with an `event.detail` object holding `container`.

The AdTech implementation provides the window event `atf:BeforeLoad`
which is being triggered right before `atf_lib.load_tags()`
is called with the `load_arguments` array.

The DFP implementation provides the window event <code>dfp:BeforeDisplay</code>
which is being triggered right after the slot definition and before the
display instruction, giving you the options to act on
the `slot` and its corresponding `targeting`.

## Manually loading and rendering Advertising entities

When you write custom code for embedding Advertising entites, you might want
to use a context which corresponds to a certain (content) entity.
For this use case, you could rebuild the context on the server-side,
e.g. inside a preprocess function like this:
```
if (\Drupal::hasService('ad_entity.context_manager')) {
  $context_manager = \Drupal::service('ad_entity.context_manager');
  // $entity may be a node, term, user or any other entity.
  $context_manager->resetContextDataForEntity($entity);

  // .. Load and view your ad_entity instances.
  // .. $ad_view = $view_builder->view($ad_entity);

  // Reset to previous context data state (if any).
  $ad_view['#post_render'][] = '_ad_entity_reset_to_previous_context_data';
}
```

## Manually initializing ads

Advertising entities have the option to disable automatic initalization.
When the automatic initialization has been disabled, containers of Advertising
entities get the CSS class `initialization-disabled` during theme processing.

To initialize your ads manually, you'll need to remove the class mentioned
above from the containers and call 
`Drupal.ad_entity.restrictAndInitialize(containers, context, settings)`.

## Further tips for avoiding possible problems

It's recommended to always display your Advertising entities through
Display configurations. This way, you're able to change your advertisement
on your whole site and switch between available variants of advertisement.

The default tree aggregations and tree overrides can be expensive operations.
When using a lot terms for nodes with large trees, it's recommended to
write your custom formatter instead, which directly loads the context you want.
