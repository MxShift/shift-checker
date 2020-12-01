<?php

// INITIALIZATION
// next move it to a file

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

// Checking if JSON has the necessary keys
// if (!array_key_exists("fork_counter", $db_data)) {
//     $db_data["fork_counter"] = 0;
// }

// END INITIALIZATION

echo "\n[ FORKING ]\n\n";
echo "\t\t\tGoing to check for forked status now...\n";


// Tail shift.log
$last = tailCustom($shiftlog, $linestoread);

// Count how many times the fork message appears in the tail
$counted_now = substr_count($last, $msg);

// Get counter value from our database
$fork_counter = $db_data["fork_counter"];

// If fork_counter + current count is greater than $max_count, take action
if (($fork_counter + $counted_now) >= $max_count) {

    // If shift-snapshot directory exists and restore from snapshot is enabled
    if (file_exists($snapshotDir) && $createsnapshot) {
        $Tmsg = "Hit max_count on ".$nodeName.". I am going to restore from a snapshot.";
        echo "\t\t\t".$Tmsg."\n";
        sendMessage($Tmsg, $restoreEnable);

        // Perform snapshot restore
        system("cd $pathtoapp && ./shift_manager.bash stop");
        sleep(3);
        system ("cd $snapshotDir && SHIFT_DIRECTORY=\"$pathtoapp\" bash shift-snapshot.sh restore");
        system("cd $pathtoapp && ./shift_manager.bash start"); 

        // Reset counters
        echo "\t\t\tFinally, I will reset the counter for you...\n";

        $db_data["fork_counter"] = 0;
        saveToJSONFile($db_data, $database);

        // Pause to wait for start node sync.
        echo "\t\t\tPause: 120 sec.\n\n";
        sleep(120);  

    } else {
        echo "\t\t\tWe hit max_count and want to restore from snapshot.\n
            \t\t\tHowever, restore from snapshot is not enabled or\n
            \t\t\tpath to snapshot directory ($snapshotDir) does not seem to exist.\n";
    }

} 

// else
if (($fork_counter + $counted_now) < $max_count) {

    $db_data["fork_counter"] = $fork_counter + $counted_now;
    saveToJSONFile($db_data, $database);

    echo "\t\t\t".($fork_counter + $counted_now)." is fine. Restoring starts at: $max_count \n";

    // Check snapshot setting
    if ($createsnapshot === false) {

        echo "\t\t\tSnapshot setting is disabled.\n";
    }

    // Check if it's safe to create a daily snapshot and the setting is enabled
    if (($fork_counter + $counted_now) < $max_count && $createsnapshot === true) {

        echo "\t\t\tDo we have a new snapshot for today?.. ";
        // Let's check if a snapshot was already created today...
        // Check if path to shift-snapshot exists..
        if (file_exists($snapshotDir)) {

            $snapshots = glob($snapshotDir.'snapshot/shift_db'.date("d-m-Y").'*.snapshot.tar');

            if (!empty($snapshots)) {

                echo "YES!\n";
            } 

            // if we don't have a snapshot for today of the last snapshot is corrupt
            if (empty($snapshots) || $db_data["corrupt_snapshot"] == true && $db_data["synchronized_after_corrupt_snapshot"] == true) {

                echo "\n\t\t\tNo snapshot exists for today, I'll create one for you now!\n";
            
                // using passthru() for find occurrences of a string $createdMsg
                ob_start();
                $create = passthru("cd $snapshotDir && SHIFT_DIRECTORY=\"$pathtoapp\" bash shift-snapshot.sh create");
                $check_createoutput = ob_get_contents();
                ob_end_clean();

                // If buffer contains "OK snapshot created successfully"
                if (strpos($check_createoutput, $createdMsg) !== false) {
                    $Tmsg = "Created daily snapshot on ".$nodeName.".";
                    echo "\t\t\t".$Tmsg."\n";
                    sendMessage($Tmsg, $restoreEnable);

                    $db_data["recovery_from_snapshot"] = true;
                    $db_data["corrupt_snapshot"] = false;
                    $db_data["recovery_from_snapshot"] = true;
                    $db_data["synchronized_after_corrupt_snapshot"] = false;
                    saveToJSONFile($db_data, $database);
                }

                echo "\t\t\tGoing to remove snapshots older than $max_snapshots days...\n";

                $files = glob($snapshotDir.'snapshot/shift_db*.snapshot.tar');
                
                foreach ($files as $file) {
                    if (is_file($file)) {
                        if (time() - filemtime($file) >= 60 * 60 * 24 * $max_snapshots) {
                            if (unlink($file)) {
                                echo "\t\t\tDeleted snapshot $file\n";
                            }
                        }
                    }
                }

                echo "\t\t\tDone!\n\n";
            }
        } else {
            // Path to shift-snapshot does not exist..
            echo "\t\t\tYou have shift-snapshot enabled, but the path to shift-snapshot does not seem to exist.\n
            \t\t\tDid you install shift-snapshot?\n";
        }
    }
}

// Finally, make sure the data is saved to a file
saveToJSONFile($db_data, $database);

