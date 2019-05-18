Rocketship Core
-----

## Rocketship Menu Parent Alias token:

* Used mostly for node path aliases
* If the node is in a menu, fetches the parent's path alias and prepends it 
to the current node's alias
* If that parent is also a node with the same token and is also in a menu, 
that means its alias already contained their own parent so you can safely 
build a nice alias structure based on the menu.
* Also includes hook_path_update to update all children's aliases if a parent
 changes theirs
* todo: trigger same logic when someone re-orders the menu links

## Paged current page token

* A new token, [current-page:paged-url], is available. It is identical to the
normal current-page:url token but it adds the page query parameter if present.

## Rocketship class:

* contains helper functions, similar to \Drupal::

## Breakpoints:
* Contains the breakpoints used by all responsive image styles

## Search API
* Contains search api server (db) and index for all content types. Other modules
update the index as needed when installed.

## Field storage
* Contains field storage definitions for fields used by other features. For 
example, field_header_paragraph.

## Image Styles
* Contains all the basic image styles (based on ratios). These are then used 
to create specific Responsive image styles which are linked to content types 
and view modes and the like. There should be no need to create any more basic
 image styles, if the design allows it of course. Use responsive image styles
  wherever possible!
* Also contains "Preview" image style, which only scales the width. This is 
the image style to use for Focal Point widgets.

## Translation information
* We now don't show the language selector anymore when using a multilingual 
site. Instead, we always show what language the user is creating something in
. The functionality for that is located in this module.

## TokenReplacer migrate processor
Requires a string to be passed to it, and only supports global tokens. Will 
replace any global tokens in the string with their values.

## Widgets
#### LinkTargetWidget
* Extends normal Link widget
* Exposes option to set target so the client can decide per link

## DS Fields
#### DSTimeAgoField
* Outputs the time an entity was created as "X minutes/hours/etc ago"
* Updates with AJAX, has fallback normal date format.

#### ScrollToField
* Takes an identifier and some text
* Outputs a link with the identifier as href (+ # of course)
* No support for hashtags on other pages at the moment

#### ShowParentFieldFormatter
* Renders a field from the entity the paragraph is attached to as though it 
was part of the paragraph.
* Useful for theming. If a value from the parent has to be output as part of 
the paragraph, this'll do it.
* If multiple fields need outputting, use Display Suite's copy DSField 
functionality (untested)

#### ConfigurableLink
* Provides configurable link which can be placed in any display mode ( currently supported entity types are: node, taxonomy_term)
* Available configuration options are: link text, link URL ( with autocomplete support), CSS class 
* Token input is supported for title and URL 

## Custom formatters
#### AuthorRender
* Field type: boolean
* Output the highest level parent's author when the field value evaluates to 
true.

#### BreadcrumbRender
* Field type: boolean
* Outputs the breadcrumb if the field value is true

#### ClassLinkFormatter
* Field type: link
* Adds option to add extra classes to the output

#### DownloadLinkFileFormatter
* Field type: file
* Extends GenericFileFormatter, adds extra option for developer set link text

#### FeatherlightImageFormatter
* Field type: image
* Outputs images using the Featherlight library

#### HeaderTextFieldFormatter
* Field type: string & string_long
* Wraps output in selected wrapper

#### LinkVideoEmbedColorbox
* Field type: video_embed_field
* Alternative to thumbnail link which opens video in colorbox
* This one lets you select a field or fallback text, and use that to build a 
link which will open the video in a popup. Extra fallback; if javascript 
fails it's still a link to the video.

#### PositionBasedImageFormatter
* Field type: image
* Works with the value from field_image_position to add classes that let the 
themer know if the image should be aligned left or right

#### PostDateRender
* Field type: boolean
* If checked, outputs the created date of the highest level parent. 
* Currently format is hardcoded, will be fixed so it's part of the formatter 
settings

#### StaticLinkFormatter
* Field type: link
* Allows developer to set text to be used as the link text instead of using 
user defined text
* Don't forget to disable asking for link text in field settings
* Useful if the link text is always the same, such as "Visit this website"

#### RelatedPaddedReferenceItemFormatter
* Field type: entity_reference
* other reference fields from the entity to determine the relationship (still 
needs extra filter to make sure those reference field reference content 
entities, not config entities) 
* the conjunction within a single field is currently OR, so if an entity has 
term A and B, entities that have term A OR B will pop up. Plans for AND will 
have to wait, can't be done using EntityQuery, will require a refactor to a 
database query.
* you can select the conjunction *between* the multiple fields however. If 
you select AND, then there will have to be a match in every field before the 
entity can be used to pad the list
* naturally the entities that are manually added to the entity reference 
field this formatter is on are excluded, as is the entity itself
* you can set how much it should pad. If you set it to 5, it will add 
entities if needed to reach 5. By default it will attempt to reach the 
cardinality for the field, unless it is infinite then it won't pad at all 
unless a manual pad limit is set.
* you can select one other field to sort by and set the sort direction
* You can only select this formatter if the entity has at least one other 
reference field that can be used to create a "relationship"
* You can only select this formatter on reference fields which reference the 
same entity and bundle as the entity the field is attached to
* 'Force padding' will pad the list to reach the limit even if there aren't 
enough items with the relationship. If it only finds 2 items that meet the 
criteria, but the limit is 5, it'll grab 3 other items to reach the limit.

TLDR:
If you've got a "related products" reference field, the user can fill in one 
or two products and you can set the formatter to add other related products 
until the limit is reached. It'll do that using the reference fields selected
 to create a relationship.

## Custom Fields
#### ParagraphTitleReplacementField
- add this to your header paragraphs. It will output either the parent node's
 title in h1 or whatever the client fills in wrapped in whatever they want 
 (widget setting).
- can also be added to nodes. Will simply grab the highest parent it can, or 
fallback to itself.
- Used when the detail page title needs markup (strong, italic)

#### ImageDescriptionTitle
* Image description title field contains an image, title and text (also 
supports icon list or numbers)
* Used in paragraphs

#### LabelValueField
* Custom field with two inputs (both textfield)
* Useful when the client wants to create a list of label: values
* For example, dimensions of a package, properties of a building, etc
* Has a normal formatter defined, as well as a Table formatter
* Also contains a "promoted" value. This is used in the Formatters, to only 
show certain
values on teasers for example. Hidden behind permissions, used in Product 
feature.
* NOTE: we can't filter on this field, as the label, which signifies what it 
is, is also defined by the user. 

#### TabbedItem
* Custom field containing a title and a body

#### TitleDescriptionField
* Custom field containing title and textarea (for when no markup is allowed, 
this can be used instead of TabbedItem)

# Sub-modules:

## Rocketship Content:
* migrates homepage, 404 and 403 page and sets it in system.site
* also disables frontpage, 404 and 403 metatag defaults so node metatags are used

## Rocketship SEO:
* Sets up SEO settings
* Based on Varbase, but with no Yoast (unstable) and small tweaks

## Rocketship Page:
* Creates a 'Page' content type
* Uses Paragraph fields to make it as flexible as possible