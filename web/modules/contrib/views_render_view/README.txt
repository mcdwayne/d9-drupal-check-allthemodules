Views Render View

This module renders a view in a views field. To install: 1) Install the module, 2) add the Global Field: "Render View" field 3) choose the view that you want to display from the drop-down select box; 4) enter the arguments that you want to pass to the view as either {{ token }} or static text; 5) enter a separator.

***NOTE: The module can become easily confused if the parent field returns a value with the separator that the module was instructed to watch out for. Enter a unique separator in the options if you experience errors parsing arguments.

The code is essentially a copy-and-paste from core Views modules so the initial release should be stable out-of-the-box.
