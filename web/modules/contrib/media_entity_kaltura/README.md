# Media Entity Kaltura
Kaltura integration for the [Media Entity](https://www.drupal.org/project/media_entity) module.

## Installation

1. Enable the media_entity and media_entity_kaltura module.
2. Go to `/admin/structure/media` and click 'Add media bundle' to create a new bundle.
3. Under **Type provider** select Kaltura.
4. Save the bundle.
5. Add a field to the bundle to store the kaltura url.
6. Edit the bundle again, and select the field created above as the **Kaltura URL source field**.

## Configuration

1. Go to the **Manage display** section for the media bundle.
2. For the source field selected, select **Kaltura embed** under **Format**.
3. Click on the settings icon to configure the embedded player.
4. Save.

## License

http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
