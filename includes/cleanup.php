<?php

echo "\n[ LOGFILES ]\n\n";
echo "\t\t\tPerforming log rotation and cleanup...\n";
rotateLog($logfile, $max_logfiles, $logsize);

// Remove lock file
releaseScript();
