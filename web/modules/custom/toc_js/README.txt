CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------

Toc.js module integrate TOC.js library into Drupal.

TOC.js is a jQuery plugin which automatically generate a table of contents for
your page. This module provide :

* An extra field available on node. This extra field display a Toc based on the
  settings of the node type.
* A block which can generate a Toc for any kind of page.

The Table of content is generated from the front end and allow :
  - Smmoth scrolling when jumping to heading
  - Highlight on scroll
  - Custom configuration for the container and the heading you want to target.

You can put as many Toc, with specific settings, as you need on one page.

REQUIREMENTS
------------

Toc.js module works with the jquery TOC.js library.

Required modules

 * node
 * block

SIMILAR MODULES
-------------------

 * The TOC API module provide the API to generate a TOC based on
   configuration entity type. TOC are generate on the backend.
 * The TOC filter module use the TOC API and can generate a TOC for a body field
   using the a filter on the body.
 * The TOC API Node use the TOC API module and generate a TOC on a whole node,
    always from the backend.
 * TOC Formatter set a TOC to the top of a text area field. This module uses
   PHP DOMDocument to manipulate content.
 * See the Comparison of Table of Contents / TOC modules
   https://www.drupal.org/node/2278811

Toc.js have a more lightweight approch than theses modules and delegate the most
part of the work to the awesome TOC.js jquery library, which comes with natively
options (selectors, containers, smooth scrolling, highlight on scroll).

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.
 * Download the library TOC.js
   (https://github.com/jgallen23/toc/tree/greenkeeper/update-all)
   and place the following file toc.min.js under the libraries/toc folder.
   The complete path of shuffle file attended is :
   - libraries/toc/toc.min.js
 * Check the status report
 * You can use composer to download and install the jQuery library. For this you
   have to add to your composer.json file the following repository:

 "repositories": [
         {
             "type": "composer",
             "url": "https://packages.drupal.org/8"
         },
 		{
             "type": "package",
             "package": {
                 "name": "toc/toc",
                 "version": "v0.3.2",
                 "type": "drupal-library",
                 "dist": {
                     "url": "https://raw.githubusercontent.com/jgallen23/toc/greenkeeper/update-all/dist/toc.min.js",
                     "type": "file"
                 }
             }
         }
     ],

 And "toc/toc": "*" in the require part.

CONFIGURATION
-------------

For the Toc.js Block
  * Place a Toc.js block and configure the Toc settings. Toc settings are the
    same that for node. See below.

For node
  * Enable the Table of content on the content type you want place a TOC
  * configure the Toc settings
  * In the manage display page configuration of this content type, place the
  TOC extra field in the region you content to display it, in any view mode you
  need.

The Toc.js settings available are :

  * Title : the title displayed on top of the Toc list.
    Default: "Table of contents".
  * Selectors : the elements to use as headings. Each element separated by
    comma. Default: "h2,h3".
  * container : Element to find all selectors in. Default: ".node".
  * Prefix : Prefix for anchor tags and class names. Default: "toc".
  * List type: The HTML list type to use (ul or ol).
  * Back to top: Add back to top link on heading.
  * Bck to top label: The back to top link label.
  * Smooth scrolling : Enable or disable smooth scrolling on click.
  * ScrollTo offset : Offset to trigger with smooth scrolling enabled.
  * Highlight on scroll : Add a class to heading that is currently in focus.
  * Highlight offset : Offset to trigger the next headline.

This module contains a sub module "Toc.js per node" which permit to
enable/disable the Toc per node. Once this sub module enabled, you must allow
the override per node for the content type.

You have a new Toc.js settings :
  * Permit to enable/disable toc per node

Once this option checked, you can for each node disable the TOC generated.


TROUBLESHOOTING
---------------


FAQ
---


MAINTAINERS
-----------

Current maintainers:
 * flocondetoile - https://drupal.org/u/flocondetoile
