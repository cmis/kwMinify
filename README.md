kwMinify
========

Minify for Oxid

Install minify in:

    /min/

Add to config.inc.php:

    // Minify
    $this->kwUseMinify = 2; //<0|1|2>

0 = turn minify off.
1 = turn minify on.
2 = turn minify on, if shop is in production mode.

Copy the smarty functions to:

    /core/smarty/plugins/

Then use:

    [{kwscript}] and [{kwstyle}], instead of [{ox...}].

in your templates.
