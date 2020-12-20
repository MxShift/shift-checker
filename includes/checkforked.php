<?php

echo "\n[ FORKING ]\n\n";
echo "\t\t\tGoing to check for forked status now...\n";


// Tail shift.log
$last = tailCustom($shiftlog, $linestoread);

// Count how many times the fork message appears in the tail
$counted_now = substr_count($last, $msg);

// Get counter value from our database
$fork_counter = $db_data["fork_counter"];

$nodeIsForking = ($fork_counter + $counted_now) >= $max_count;

$goodSnapshot = (file_exists($snapshotDir) && $createSnapshots && $db_data["corrupt_snapshot"] === false);

$bad_snapshot = $db_data["corrupt_snapshot"] === true && 
                $db_data["synchronized_after_corrupt_snapshot"] === true;

// If fork_counter + current count is greater than $max_count, take action
if ($nodeIsForking) {

    // If shift-snapshot directory exists and restore from snapshot is enabled and snapshot is not corrupt
    if ($goodSnapshot) {
        $Tmsg = $nodeName.":\n\n$forkEmoji Node probably is *forking*.\n$recoveryEmoji Going to restore from a local snapshot.";
        echo "\t\t\t".$Tmsg."\n";
        sendMessage($Tmsg, $recoveryMessages);

        // update shift-lisk client just in case
        shiftManager("update_client");

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
        echo "\t\t\tWe hit max_count and want to restore from snapshot.\n".
            "\t\t\tHowever, restore from snapshot is not enabled or\n".
            "\t\t\tpath to snapshot directory ($snapshotDir) does not seem to exist or\n".
            "\t\t\tthe last snapshot is corrupt.\n";

            // update shift-lisk client just in case
            shiftManager("update_client");

            // add here an alternate way for snapshot dilivery
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
if ($createSnapshots === false) {

    echo "\t\t\tSnapshot setting is disabled.\n";
}

['height' => $heightBlockchain] = getNodeAPIData($trustedNode);
['height' => $heightLocal]= getNodeAPIData($localNode);

$heightIsFine = ($heightLocal + 3) >= $heightBlockchain;

// Check if it's safe to create a daily snapshot and the setting is enabled
if (!$nodeIsForking && $heightIsFine && $createSnapshots) {

    echo "\t\t\tDo we have a new snapshot for today?.. ";

    if ($db_data["snapshot_creation_started"] === false) {

        // Let's check if a snapshot was already created today...
        $snapshots = snapshotPath(date("d-m-Y"));
    
        if (!empty($snapshots)) {
    
            echo "YES!\n";
        } 
    
        // if we don't have a snapshot for today or the last snapshot is corrupt
        if (empty($snapshots) || $bad_snapshot) {
    
            echo "\n\t\t\tNo good snapshot exists for today, I'll create one for you now!\n";
            
            $db_data["snapshot_creation_started"] = true;
            $db_data["recovery_from_snapshot"] = false;
            saveToJSONFile($db_data, $database);
            shiftSnapshot("create");
        }
    } else {
        echo "\n\t\t\tThe snapshot is now being created.";
    }


}

// Finally, make sure the data is saved to a file
saveToJSONFile($db_data, $database);