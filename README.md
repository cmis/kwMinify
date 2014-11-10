kwMinify
========

Minify for Oxid
13:32 31-5-2013
---------------

add to config.inc.php:

    // Minify
    $this->kwUseMinify = 2; //<0|1|2>

0 = turn minify off.
1 = turn minify on.
2 = turn minify on, if shop is in production mode.

Copy the smarty functions to:

    /core/smarty/plugins/

