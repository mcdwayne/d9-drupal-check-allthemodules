***
***

# INSTALLATION
Install the module as usual, more info can be found on:

[Installing Drupal 8 Modules](https://drupal.org/node/1897420)

Be sure to read the entire docs and form descriptions before working with
Ultimenu to avoid headaches for just ~5-minute read.

Ultimenu is so simple that it might hurt. Once you tame it, you'll love it!


## REQUIREMENTS
* Drupal core optional menu.module should be enabled.
* Drupal **Main navigation** like at Standard profile:
  **/admin/structure/menu/manage/main**

  If not, just have a menu with the same machine name **main**.


## RECOMMENDED
* [Ajaxin](http://dgo.to/ajaxin)
  To have decent loading animations.


## USAGE / CONFIGURATION
Ultimenu supports multiple mega menus for the header, sidebars, or footer.

Check out **OFF-CANVAS MENU** section below for the **Main navigation** part.
And repeat for the rest of mega menus accordingly. Only one off-canvas menu
can exist on a site. The rest are optional sidebars, footer mega menus.

**Please keep this in mind to avoid confusion**

Ultimenu will create two things:

* **blocks** based on Menu name.
* **regions** based on enabled Menu items (item titles, if not using HASH).


## CONFIGURING OFF-CANVAS MENU (THE MAIN NAVIGATION)
1. Visit **/admin/structure/ultimenu**.
   + Enable **Main navigation** under **Ultimenu blocks**.  
     Leave the rest alone till you get a grasp and or need more mega menus.  
     **Save!**
   + Once saved, enable any relevant region under **Ultimenu regions**.  
     **Save!**

2. Visit **/admin/appearance**, and switch your theme temporarily to Bartik
   to see it working immediately with default values.
3. Clear cache, your theme needs to know the newly created regions.   
4. Visit **/admin/structure/block**.    
   + Under **Header** or any header region like **Primary menu**, hit **Place
     block** button.
   + Search for **Ultimenu: Main navigation**, hit **Place block** button.
     This is the Ultimenu block container containing regions and blocks in one.
   + Fill out the rest of form. The required for now are **Off-canvas element**
     and **On-canvas element**. Use default values for Bartik. You can leave
     them empty later, once done with **STYLING** section.
     **Save!**
   + Find the new **Ultimenu regions**, normally at the bottom.
   + Add any other block, except the Ultimenu block itself, to each newly
     created **region** prefixed with **Ultimenu:main** accordingly.
     **Save!**

### **Important!**

Do not add Ultimenu blocks into Ultimenu regions, else broken.
Watch for the repeated **Save**. It means it must be saved one at a time.
Check out **STYLING** section to understand more about off-canvas.
