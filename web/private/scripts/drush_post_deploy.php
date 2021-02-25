<?php

//Clear the cache to make sure database doesn't conflict
echo "Start running drush deploy...\n";
passthru('drush deploy -v -y 2>&1');
echo "Done running drush deploy.\n";
