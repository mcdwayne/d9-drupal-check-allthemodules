# BYU Calendar

This module will install a block which displays upcoming events.

## Usage

Simply go to your block layout, and there should be a new block called "BYU Calendar." When you place this block where you'd like to have it on your site, there will be a form with a number of different options.

### Categories
 
First off you have the categories. The categories define what calendars you will be pulling events from. The main categories are shown as check boxes. If you want to subscribe to any other calendars, you will need the category ID of that calendar, and put it in the additional category field. The list of these categories are found in the BYU calendar's API. The links are here: https://calendar.byu.edu/api/AllCategories.xml (XML), or https://calendar.byu.edu/api/AllCategories.json (JSON) If you enter multiple categories, separate them with a comma.

### Styles

This will define what way the calendar will be displayed. There are 5 different styles with one that's still in development.
* Classic List - Displays the events in a simple list.
* Vertical Tile - Displays the events separately in vertical cards with the date on top.
* Horizontal Tile - Displays the events separately in horizontal cards with the date to the left.
* Full-Page Rows - Displays the events in rows that span the container.
* Full-Page Rows with Images - Displays the events with a thumbnail on the left.
* Featured Events - Displays the events in the featured events format found on the BYU homepage. **Note**: Each column will only display two events. Also, the limit option has no effect on this component.

### Additional Filters

These define how the events from your calendars will be filtered.

* Days to Look Forward - The number of days ahead from which events should be pulled.
* Limit of Events - The number of events to be displayed.
* Price Filter - The highest price of an event that should be displayed.

## Known Issues

* Text overflow with event titles on vertical tile display is set to hidden right now. Needs a better way to handle text overflow.
* Currently uses experimental libraries from the cdn. Once the changes of those libraries are merged onto the master branch, then we can switch back.