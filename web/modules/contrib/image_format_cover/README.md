# Image Format Cover

Drupal module for providing a "cover" image format.

The cover image format resizes an image down (or optionally up) to the smallest
size that completely covers the specified height and width while maintaining
ratio. This is useful for optimizing images that can be used with the `cover`
value for [object-fit] and [background-size].

It is also particularly helpful with [Focal Point] in conjunction with
[object-fit]/[object-position] or [background-size]/[background-position]

[object-fit]: https://developer.mozilla.org/en-US/docs/Web/CSS/object-fit
[object-position]: https://developer.mozilla.org/en-US/docs/Web/CSS/object-position
[background-size]: https://developer.mozilla.org/en-US/docs/Web/CSS/background-size
[background-position]: https://developer.mozilla.org/en-US/docs/Web/CSS/background-position
[Focal Point]: https://www.drupal.org/project/focal_point
