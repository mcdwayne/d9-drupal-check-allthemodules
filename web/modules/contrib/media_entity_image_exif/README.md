# Media Entity Image EXIF

Use this module if:
 - you were using Media Entity Image with Media Entity 1.x
 - you were relying on its EXIF metadata extraction
 - and you want to start using Media in core (> 8.4.0).

Media in core currently does not include the EXIF-related functionality that
Media Entity Image had, so this small module is needed to fill in that gap.

This module, when enabled, will override the "Image" source plugin from core
and all image media types will then have the EXIF extraction handling available.
