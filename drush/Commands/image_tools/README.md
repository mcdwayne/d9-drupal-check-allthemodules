# Image Tools

This module converts and resizes images with the aim for better performance on Drupal 8 pages. It provides two drush
commands and an admin gui integration. For Module testing an demo image creation command exists.

The module can also be used with the <https://thunder.org/> Project.

# Drush 8/9 Commands

The first command lets you convert uploaded PNG Images to JPG that they can be optimized with other tools. PNG-Files 
with transparency will be excluded.

```bash
drush image:convertPngToJpeg --dry_run
drush image:convertPngToJpeg 
```

With the second command you can resize all the images to an given max width, default is 2048. Here its possible to
include PNG-Files. If the have transparency the Background will be set to white.

```bash
drush image:resize --dry_run
drush image:resize --include_png --max_width=2000
```

The demo image creation command creates per default 1000 images with an default widht of 2100 pixels. 

```bash
drush image:create:demo
drush image:create:demo --amount=100 --width=2050
```

# Admin

The Admin UI is placed under Configuration / Media / Image Tools. 

For the conversion and resizing the images we using batch processes. This avoids runtime errors like connection 
timeouts. 
