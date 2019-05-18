# jquery.timepicker Polyfill

Provides a light wrapper for [jquery.timepicker](http://jonthornton.github.io/jquery-timepicker/)

Uses modernizr to detect browser support for html5 `time` inputs and applies the polyfill if not.

TODO: need configurable options:
    - vary the time format
    - vary the time interval presentation

TODO: is it safe to run this for every form?

TODO: is H:i:s always the expected format of the input by the builtin element validator?