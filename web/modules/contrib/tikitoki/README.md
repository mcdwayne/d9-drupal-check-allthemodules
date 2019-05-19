Tiki Toki
=========

## Description
Add views display that outputs data in specific JSON format for [Tiki Toki Timelines](https://tiki-toki.com/). You will need to have a Silver account at Tiki Toki website to be able to import the feed into your timeline.

## Installation
### Requirements:
- Drupal 8.4.x
- Views
- Serialization

## Configuration
1. Create a new view, with a display of type "TikiToki Json"
2. Create path for the json feed under "Path settings"
3. Check that views access settings is not restricted (Tiki Toki need a public feed)
4. Add the fields you want to use from your content. You need to have at least a date field and a text field.
5. If you want to map colors with categories you need to first add a [color field](https://www.drupal.org/project/color_field) to the taxonomy. Add the taxonomy term field to the view, and then add the color field by using a relationship (Go to 'Advanced' settings and add a 'Relationships', and add 'Taxonomy term referenced from field_type' [Read more](https://www.drupal.org/docs/8/core/modules/views/add-a-relationship-to-a-view)). After you've added the relationship you can add the color field to the view.
6. Go to the row's settings window ("settings" for fields under "Format") and map your fields to an appropriate JSON fields. It is mandatory to map title, text, start date and end date, the rest are optional. Notice that some fields use default fields formatters that are requiered by Tiki Toki and this cannot be changed. The others can be changed using the field formatter in the view:
  - Title (required)
  - Text (required)
  - Full text
  - Start date (required) (locked format)
  - End date (required) (locked format)
  - Category (entity reference allowed) (locked format)
  - Color (color field allowed) (locked format)
  - Media (image fields allowed) (locked format)
  - Link
7. Limit the view to 500 events (Tiki Toki timeline can't handle more than that)
8. Visit the json feed with the link "View Tiki Toki JSON"
9. Make sure the link use https (http is not allowed). Then copy the link.
10. Go to the Tiki Toki timeline you want to export content to.
11. Go to "Feeds" (under "My timelines"). Click "Add new feed", select JSON as a "source", paste the url into "JSON url" and give the feed a name.
12. You should now see content from your site, exported in the timeline.

## Make the embedded timeline responsive

Once you've added the timeline to your site you need to make some adjustments to make it responsive. Make sure that the width of the iframe is set to 100%. Then use the following jQuery code:

```javascript
$('#tl-timeline-iframe').attr('height', $(window).height());

$(window).resize( function() {
	$('#tl-timeline-iframe').attr('height', $(window).height());
});


var resizeIframe = function() {
	$('#tl-timeline-iframe').attr('height', $(window).height());
	$('#tl-timeline-iframe').attr('width', $(window).width());
}

resizeIframe();

$(window).resize( function() {
	resizeIframe();
});
```

## Credits
Developed by [Valentine94](https://drupal.org/u/valentine94) and [vladdancer](https://drupal.org/u/vladdancer)

Designed by [matsbla](https://drupal.org/u/matsbla)
