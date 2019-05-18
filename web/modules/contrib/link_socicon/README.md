link_socicon module provides:
- field formatter Link Socicon: turn your links (field type link from Drupal's core) into Socicon's icons (http://socicon.com/). 
- socicon library definition so you can use it anywhere

Installation
---

1. Download Socicon (http://socicon.com)
2. Copy downloaded folder to link_socicon (Correct path is modules/link_socicon/socicon)
3. Enable module link_socicon

Use as a field formatter
---

1. Add Link field to your content type, maybe you need to enable module Link first
2. Follow Installation steps
3. In your content type display settings (admin/structure/types/manage/{your-content-type}/display), select Link Socicon
as field Link display.

Use as a library
---

1. Follow Installation steps
2. In your Drupal's render-able array, attach socicon library

    ```$elements = array(
          '#type' => 'container',
          '#attached' => array(
            'library' => array(
              'link_socicon/socicon',
            ),
          ),
        );
    ```
    
3. Follow Socicon offical instruction to use it - http://socicon.com

  ```
  <span class="socicon">A</span> <!-- Twitter icon -->
  ```

Change icon color, size
---

```.socicon {
       font-family: 'socicon' !important;
       font-size: 16px;
       color: #cacaca;
   }
```