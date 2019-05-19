This is an advanced module as it basically requires you to know the SQL of the view and how to construct expression-based ORDER BYs.

It aims as a helper module to achieve custom ordering that are normally not possible with the regular sort handlers or that you might need other contribs for it.

By sorting by an expression you can do things like showing NULL items last when sorting by a numeric field, or creating a custom sort order putting some content types on top. The possibilities are endless.

You may use whatever is you have currently available on the query. For that reason you should always enable the "Show the SQL query" on the Views's setting page. Normally, adding a field or the like doesn't necessarily add to the SQL query, so you may find useful adding whatever is you want to use and inside the expression as a sort in the end, and put your expression where you need it.
