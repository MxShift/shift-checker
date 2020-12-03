<?php

// INITIALIZATION
// next move it to a file

$floppyEmoji = "%F0%9F%92%BE";
$starEmoji = "%E2%AD%90%EF%B8%8F";
$chainEmoji = "%E2%9B%93";
$storageEmoji = "%F0%9F%97%84";
$forkEmoji = "%F0%9F%94%80";
$recoveryEmoji = "%F0%9F%94%84";

$restoredMsg 	= "OK snapshot restored successfully.";	    // 'Okay' message from shift-snapshot
$createdMsg 	= "OK snapshot created successfully";	    // 'Okay' message from shift-snapshot	

// Get publicKey of the secret to use it in forging checks
$public = checkPublic($apiHost, $secret);

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
}

// END INITIALIZATION

echo "\n[ FORKING ]\n\n";
echo "\t\t\tGoing to check for forked status now...\n";


// Tail shift.log
$last = tailCustom($shiftlog, $linestoread);

// Count how many times the fork message appears in the tail
$counted_now = substr_count($last, $msg);

// Get counter value from our database
$fork_counter = $db_data["fork_counter"];

$nodeIsForking = ($fork_counter + $counted_now) >= $max_count;

// If fork_counter + current count is greater than $max_count, take action
if ($nodeIsForking) {

    // If shift-snapshot directory exists and restore from snapshot is enabled
    if (file_exists($snapshotDir) && $createsnapshot) {
        $Tmsg = $nodeName.":\n\n$forkEmoji Node probably is *forking*.\n$recoveryEmoji Going to restore from a local snapshot.";
        echo "\t\t\t".$Tmsg."\n";
        sendMessage($Tmsg, $recoveryEnabled);

        // Perform snapshot restore
        shiftManager("stop");
        sleep(3);
        shiftSnapshot("restore");
        shiftManager("start");

        // Reset counters
        echo "\t\t\tFinally, I will reset the counter for you\n";

        $db_data["fork_counter"] = 0;
        saveToJSONFile($db_data, $database);

        // Pause to wait for start node sync.
        pauseToWaitNodeAPI(20);

    } else {
        echo "\t\t\tWe hit max_count and want to restore from snapshot.\n
            \t\t\tHowever, restore from snapshot is not enabled or\n
            \t\t\tpath to snapshot directory ($snapshotDir) does not seem to exist.\n";
    }
} 

// else
if ($nodeIsForking === false) {

    $db_data["fork_counter"] = $fork_counter + $counted_now;
    saveToJSONFile($db_data, $database);

    echo "\t\t\t".($fork_counter + $counted_now)." is fine. Restoring starts at: $max_count \n";
}

// SNAPSHOT CREATION LOGIC
// Check snapshot setting
if ($createsnapshot === false) {

    echo "\t\t\tSnapshot setting is disabled.\n";
}

['height' => $heightBlockchain] = getNodeAPIData($trustedNode);
['height' => $heightLocal]= getNodeAPIData($apiHost);

$heightIsFine = ($heightLocal + 3) >= $heightBlockchain;

// Check if it's safe to create a daily snapshot and the setting is enabled
if (!$nodeIsForking && $heightIsFine && $createsnapshot === true) {

    echo "\t\t\tDo we have a new snapshot for today?.. ";
    // Let's check if a snapshot was already created today...
    // Check if path to shift-snapshot exists..
    if (file_exists($snapshotDir)) {

        $snapshots = snapshotPath(date("d-m-Y"));

        if (!empty($snapshots)) {

            echo "YES!\n";
        } 

        $bad_snapshot = $db_data["corrupt_snapshot"] === true && 
                        $db_data["synchronized_after_corrupt_snapshot"] === true;

        // if we don't have a snapshot for today or the last snapshot is corrupt
        if (empty($snapshots) || $bad_snapshot) {

            echo "\n\t\t\tNo good snapshot exists for today, I'll create one for you now!\n";
        
            ['output' => $create_output,
            'size' => $fileSize,
            'height' => $blockHeight] 
            = shiftSnapshot("create");

            // If buffer contains "OK snapshot created successfully"
            if (strpos($create_output, $createdMsg) !== false) {
                $Tmsg = $nodeName.":\n\n$floppyEmoji _created daily snapshot_\n\n$chainEmoji Block: *".$blockHeight."*\n$storageEmoji Size: *".$fileSize."*";
                echo "\t\t\t".$Tmsg."\n";
                sendMessage($Tmsg, $recoveryEnabled);

                $db_data["recovery_from_snapshot"] = true;
                $db_data["corrupt_snapshot"] = false;
                $db_data["synchronized_after_corrupt_snapshot"] = false;
                saveToJSONFile($db_data, $database);
            }

            echo "\t\t\tGoing to remove snapshots older than $max_snapshots days...\n";

            removeOldSnapshots();

            echo "\t\t\tDone!\n\n";
        }
    } else {
        // Path to shift-snapshot does not exist..
        echo "\t\t\tYou have shift-snapshot enabled, but the path to shift-snapshot does not seem to exist.\n
        \t\t\tDid you install shift-snapshot?\n";
    }
}

// Finally, make sure the data is saved to a file
saveToJSONFile($db_data, $database);

