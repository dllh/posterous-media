posterous-media
===============

A plugin for doing post-import things to attachments imported via the WordPress Posterous importer.

Once your import is complete, you can deactivate or delete this plugin.

* Inserts a [quicktime] shortcode with dimensions 500 x 285 for imported videos.
* Inserts an [audio] shortcode for imported audio files.
* If more than one image is found in a post, replace matched snippet from post content (a div with class "p_embed") with a [gallery] shortcode.
* Remove "See gallery on Posterous" and "See on Posterous" links from markup.
* Strip height and width tags from images because sometimes they're wrong and the images wind up looking distorted.

See http://wordpress.org/support/topic/re-import-attachments-into-media-library-after-migrating?replies=24#post-3723421
