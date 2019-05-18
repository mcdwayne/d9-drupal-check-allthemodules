Image Field Repair is a module that repairs image field values that are touched
by [issue [#2644468]: Multiple image upload breaks image dimensions](https://www.drupal.org/node/2644468).

That issue
----------
- TL;DR: image dimensions, width and height, may be stored incorrectly for all
  but the 1st image on a multi-upload.
- Is currently (end of april 2018) still open, even though it has a reportedly
  working patch.
- Does not currently provide an upgrade function (hook_update_N) even though
  comment #37 asks for it.
- IMO, Comment #38 is too easy: a small site I am currently building had 1389
  incorrect dimensions on 2316 images, not a number you would like to delete and
  upload again (certainly mot without my [Duplicate Images](https://www.drupal.org/project/duplicate_images) project. that unfortunately still only exists for D7).

Usage
-----
After installing and enabling the module, go to Admin - Configuration - Media -
Image Field Repair (admin/config/media/image_file_repair/dimensions) and press
the "Start" button. This will repair any incorrect image dimensions using a
batch. This can take a long time, but leave it running and go do some other
stuff :)

As there's still no fix in core itself, this module also fixes the bug using 
code similar to the patch in comment #91. So after having repaired your image
fields you can leave this module enabled and use multi-upload on your image
widgets again. When the core issue gets fixed, you can uninstall and remove this
module.

If you don't want this module to fix the bug, you can either uninstall it, or
add this line to your settings.php:
```
$settings['image_field_repair_disable_fix_2644468'] = TRUE;
```

My goals with this module
-------------------------
- I no longer wish that the code of this module makes it to the patch as an
  upgrade hook. The issue is open for too long and should get fixed as soon as
  possible, without further delays regarding an update path.
- I still do hope that a small hook_update_N() will be added that shows a
  message about the possible corruption and refers to a page on drupal.org
  further describing the problem and solution (ie use this module) or just links
  directly to the project page.
- Until the issue gets patched, this module will serve those who ran into the
  problem and want the resulting corruption fixed now.
- Even though I did some development for the D8 image module, I hardly had any
  knowledge of the new D8 APIs, especially the entity and field API. So I also
  developed this module to get some experience with D8 module development.

Installation
------------
As usual.

Questions or problems
---------------------
Use [this module's issue queue](https://www.drupal.org/project/issues/image_field_repair).

Author
------
[fietserwin](https://www.drupal.org/u/fietserwin), but many thanks to:
[vaplas](https://www.drupal.org/u/vaplas) for the many improvements.
