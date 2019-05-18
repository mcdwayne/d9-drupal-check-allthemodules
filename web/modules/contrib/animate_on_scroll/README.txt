--------------------------------------------------------------------------------
                                 Animate On Scroll
--------------------------------------------------------------------------------


Description
===========
Animate On Scroll (AOS) library allows you to animate elements as you scroll
down, and up.If you scroll back to top, elements will animate to it's previous
state and are ready to animate again if you scroll down.This module provides
integration with AOS library.


Requirements
============
Animate On Scroll Library. (Check demo at: http://michalsnik.github.io/aos/)
  1) Download AOS libraray from https://github.com/michalsnik/aos
  2) Copy aos library in your libraries directory, so aos.js will be located
     at /libraries/aos/dist/aos.js.


Installation
============
  1) Copy the 'aos' module into your Drupal /modules directory and enable it.
  2) As this module provides the integration of AOS library, to get the
     animations on page simply add `data-aos` attribute to element,
     like <div data-aos="animation_name"></div>  in your html.

     e.g. <div data-aos="fade-zoom-in" data-aos-offset="200" data-aos-easing="ease-in-sine" data-aos-duration="600"></div>

     You can check all available animations & easing options at
     https://github.com/michalsnik/aos


Roadmap
=======
  1) Expose configurations for AOS global options.
  2) Expose configuration to disable AOS library on admin pages.
