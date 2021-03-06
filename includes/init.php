<?php

// INITIALIZATION

// Emoji
$floppyEmoji = "%F0%9F%92%BE";
$starEmoji = "%E2%AD%90%EF%B8%8F";
$chainEmoji = "%E2%9B%93";
$storageEmoji = "%F0%9F%97%84";
$forkEmoji = "%F0%9F%94%80";
$recoveryEmoji = "%F0%9F%94%84";
$stopEmoji = "%E2%9B%94%EF%B8%8F";
$checkmarkEmoji = "%E2%9C%85";
$robotEmoji = "%F0%9F%A4%96";
$wavehandEmoji = "%F0%9F%91%8B";
$signalEmoji = "%F0%9F%93%B6";
$exclamationEmoji = "%E2%9D%97%EF%B8%8F";
$notesEmoji = "%F0%9F%8E%B6";
$xEmoji = "%E2%9D%8C";
$qexclamationEmoji = "%E2%80%BD";
$qeuestionEmoji = "%E2%9D%93";
$greenballEmoji = "%F0%9F%9F%A2";


// markdown
$endStyle = "\033[0m";
$green = "\e[32m";
$red = "\e[31m";
$bold = "\e[1m";
$uline = "\e[4m";
$dim = "\e[2m";


// CONFIG
$database           = $baseDir."db.json";                 // Database name to use
$msg                = "\"cause\":3";                      // Message that is printed when forked
$shiftlog           = $pathtoapp."logs/shift.log";        // Needs to be a FULL path, so not ~/shift
$linestoread        = 30;                                 // How many lines to read from the end of $shiftlog
$max_count          = 3;                                  // How may times $msg may occur
$okayMsg            = "√";                                // 'Okay' message from shift_manager.bash
$lockfile           = $baseDir."run.lock";                // Name of our lock file

// Consensus settings
$threshold          = 30;                                 // Percentage of consensus threshold.

// Recovery settings       
$snapThreshold      = 3200;                               // Threshold in blocks. Use 3200 for daily snapshots and 133 for hourly

// Snapshot settings
$snapshotDir        = $baseDir."snapshot/";                     // Base folder of shift-snapshot
$restoredMsg        = "OK snapshot restored successfully.";	    // 'Okay' message from shift-snapshot
$createdMsg         = "OK snapshot created successfully";	    // 'Okay' message from shift-snapshot	

// Log file rotation
$logfile            = $baseDir."logs/run.log";                  // The location of your log file (see section crontab on Github)
$snaplogfile        = $baseDir."logs/snap.log";
$max_logfiles       = 5;                                        // How many log files to preserve? (in days)  
$logsize            = 524288;                                   // Max file size, default is 0.5 MB


// Get publicKey of the secret to use it in forging checks
$public = checkPublic($localNode, $secret);

// Secret to array
$sec_array = explode(" ", $secret);

// Set the database to save our counts to
if (file_exists($database)) {
    $str_data = file_get_contents($database);
    $db_data = json_decode($str_data, true);
} else {
    file_put_contents($database, '{}');
    $db_data = json_decode('{}', true);

    $db_data["fork_counter"] = 0;
    $db_data["corrupt_snapshot"] = false;
    $db_data["synchronized_after_corrupt_snapshot"] = false;
    $db_data["recovery_from_snapshot"] = true;
    $db_data["rebuild_message_counter"] = 0;
    $db_data["syncing_message_sent"] = false;
    $db_data["script_disabled_countdown"] = 3;
    $db_data["switch_to_remote"] = false;
    $db_data["manual_stop"] = false;
    // $db_data["snapshot_creation_started"] = false;
}
saveToJSONFile($db_data, $database);