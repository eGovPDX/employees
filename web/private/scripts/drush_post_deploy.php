<?php

echo "Start running drush deploy...\n";
passthru('drush deploy -v -y 2>&1');
echo "Done running drush deploy.\n";

echo "Post our custom Solr schema to Pantheon's Solr server...\n";
passthru('drush search-api-pantheon:postSchema pantheon_solr8 ../private/solr-conf 2>&1');
