jQuery UI filter test script
----------------------------

## Initial setup

- Enable the jQuery UI filter module   
  `drush en -y jquery_ui_filter`
- Enable the jQuery UI filter for the Full HTML text format  
  /admin/config/content/formats/manage/full_html
- Create a test page  
  /node/add/page
    - Title: jQuery UI filter test page
    - Body: Cut-n-paste jquery_ui_filter_example.html
    - Format: Full HTML
- Verify accordion
    - Renders correctly
    - Collapsed
    - Section links work
- Verify tabs
    - Renders correctly
    - Section links work
- Verify bookmark and scroll to
    - Click Section * link
    - Reload page
    
## Global configuration

- Verify default and required configuration can't be deleted.
    - Goto settings form  
      /admin/config/content/formats/jquery_ui_filter
    - Delete accordion and tabs options
    - Save configuration
    - Verify accordion and tabs options are reset to defaults.

- Verify default options 
    - Goto settings form  
      /admin/config/content/formats/jquery_ui_filter
    - Add `collapsed: true` to tabs options.
    - Save configuration
    - Verify tabs are also now collapsed.
      /node/1   

## Verify nested tabs and accordion widgets

- Create a test page  
  /node/add/page
    - Title: jQuery UI filter nested test page
    - Body: Cut-n-paste jquery_ui_filter_example_nested.html
    - Format: Full HTML
- Verify nested accordion and tabs.
- Note: Nested accordion in tabs requires `heightStyle="content"` attribute.

