<?php

echo "\n[ FORKING ]\n\n";
echo "\t\t\tGoing to check for forked status now...\n";

// Set the database to save our counts to
if (file_exists($database_j)) {
    $str_data = file_get_contents($database_j);
    $db_data = json_decode($str_data, true);
} else {
    file_put_contents($database_j, '{}');
    $db_data = json_decode('{}', true);
}

// Checking if JSON has the necessary keys
if (!array_key_exists("fork_counter", $db_data)) {

    $db_data["fork_counter"] = 0;
}

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
        $Tmsg = "Hit max_count on ".gethostname().". I am going to restore from a snapshot.";
        echo "\t\t\t".$Tmsg."\n";
        sendMessage($Tmsg);

        // Perform snapshot restore
        system("cd $pathtoapp && ./shift_manager.bash stop");
        sleep(3);
        system("cd $snapshotDir && echo y | ./shift-snapshot.sh restore");
        system("cd $pathtoapp && ./shift_manager.bash reload");

        // Reset counters
        echo "\t\t\tFinally, I will reset the counter for you...\n";

        $db_data["fork_counter"] = 0;
        saveToJSONFile($db_data, $database_j);

    } else {
        echo "\t\t\tWe hit max_count and want to restore from snapshot.\n
            \t\t\tHowever, restore from snapshot is not enabled or\n
            \t\t\tpath to snapshot directory ($snapshotDir) does not seem to exist.\n";
    }

} 

// else
if (($fork_counter + $counted_now) <= $max_count) {

    $db_data["fork_counter"] = $fork_counter + $counted_now;
    saveToJSONFile($db_data, $database_j);

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
                
            } else {
                echo "\n\t\t\tNo snapshot exists for today, I'll create one for you now!\n";
            
                ob_start();
                $create = passthru("cd $snapshotDir && ./shift-snapshot.sh create");
                $check_createoutput = ob_get_contents();
                ob_end_clean();

                // If buffer contains "OK snapshot created successfully"
                if (strpos($check_createoutput, 'OK snapshot created successfully') !== false) {
                    $Tmsg = "Created daily snapshot on ".gethostname().".";
                    echo "\t\t\t".$Tmsg."\n";
                    sendMessage($Tmsg, $restoreEnable);
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
saveToJSONFile($db_data, $database_j);

