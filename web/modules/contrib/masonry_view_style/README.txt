This is a simplified <a href="http://masonry.desandro.com/">Masonry</a> Views output style without contrib module dependencies.

You're responsible for styling the grid -- something like this in your theme should get you started:

<code>
.mvs-grid {
    /* wrapper styling */
}

.mvs-grid-item {
    /* grid item styling */
    width: 33%;
}
</code>

The underlying frontend libraries, which are included for convenience:

<ul>
  <li><a href="http://imagesloaded.desandro.com/">Imagesloaded</a></li>
  <li><a href="http://masonry.desandro.com/">Masonry</a></li>
</ul>

These libraries are available on the <a href="https://unpkg.com/#/">Unpkg</a> CDN and you could patch libraries.yml to use these externally.