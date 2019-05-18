# Media Entity Libsyn
Libsyn integration for the core Media module.

## Installation

1. Enable the `media_entity_libsyn` module.

## Creating a Libsyn Podcast media type

1. Go to `/admin/structure/media` and click 'Add media type' to create a new bundle.
2. Under **Media source** select 'Libsyn Podcast.'
3. If the **Field with source information** dropdown says '-Create-' then a new source field will be created on your bundle. By default this will be a Link field.
4. Alternately, you can re-use an existing source field by selecting it from the **Field with source information** dropdown. However, you can only do this when you are creating the media type.
5. Click **Save** to save the media type.
6. Go to the **Manage display** section for the media type.
7. For the source field selected, select **Libsyn embed** under **Format**.
8. Click on the settings icon to configure the embedded player.
9. Save.

## Adding a podcast episode

1. You must create a Libsyn Podcast media type. See above.
2. Go to `media/add/[your podcast media type]` to add a podcast episode to your Drupal site.
3. You will then navigate to a podcast episode page on libsyn.com. Here is an example: http://modulesunraveled.libsyn.com/143-the-role-of-features-in-drupal-8-with-mike-potter-modules-unraveled-podcast
4. Paste the podcast episode URL into the Libsyn field.
5. Save the media content.

## Making your podcast episode viewable by users

1. Add a content type.
2. Add an entity reference field to the content type.
3. Make sure the entity reference field targets media entities, of whichever media type you added above.
4. Make sure that its display shows the rendered entity.
5. Add a piece of content of the type you just created.
6. Use the autocomplete field to type in the name of a podcast media entity.

## License

http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
