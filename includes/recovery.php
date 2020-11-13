<?php

echo "\n[ RECOVERY ]\n\n";

// Start to check syncing
echo "\t\t\tRecovery: ";

if ($restoreEnable === true) {
    echo "enabled\n";

    // Let's define and show variables
    if ($consensusEnable === false) {
        // Check height on Explorer
        $heightBlockchain = @file_get_contents($explorer."/api/statistics/getLastBlock");

        if ($heightBlockchain === false) {
            $heightBlockchain = 0;
        } else {
            $heightBlockchain = json_decode($heightBlockchain, true);
            $heightBlockchain = $heightBlockchain['block']['height'];
        }

        // Check height, consensus and syncing on Local node
        $statusLocal = @file_get_contents($apiHost."/api/loader/status/sync");

        if ($statusLocal === false) {
            $consensusLocal = 0;
            $heightLocal = 0;
            $syncingLocal = false;

        } else {
            $statusLocal = json_decode($statusLocal, true);

            if (isset($statusLocal['height']) === false) {
                $heightLocal = "error";

            } else {
                $heightLocal = $statusLocal['height'];

            }

            $syncingLocal = $statusLocal['syncing'];
            $consensusLocal = $statusLocal['consensus'];
        }

        echo "\t\t\tHeight Blockchain: $heightBlockchain\n\n";
        echo "\t\t\tConsensus Local: ".$consensusLocal."%\n";
        echo "\t\t\tHeight Local: $heightLocal \n";
        echo("\t\t\tSyncing Local: ".json_encode($syncingLocal)."\n\n");

        $dataTmsg = "*".$nodeName."*:
        ```\n\nHeight Explorer: ".$heightBlockchain.""
        ."\nConsensus Node:  ".$consensusLocal."%"
        ."\nSyncing Node:    ".json_encode($syncingLocal).""
        ."\nHeight Node:     ".$heightLocal."```";

    } else {

        // Consensus is enabled, variables are defined and shown from the CONSENSUS block
        if ($main === true) {
            $heightLocal = $heightMain;
            $syncingLocal = $syncingMain;
            $consensusLocal = $consensusMain;

            // If Explorer is down => Use Backup height
            if ($heightExplorer > 0) {
                $heightBlockchain = $heightExplorer;

            } else {
                $heightBlockchain = $heightBackup;

            }

        } else {
            // We are Backup here
            $heightLocal = $heightBackup;
            $syncingLocal = $syncingBackup;
            $consensusLocal = $consensusBackup;

            // If Explorer is down => Use Main height
            if ($heightExplorer > 0) {
                $heightBlockchain = $heightExplorer;

            } else {
                $heightBlockchain = $heightBackup;

            }
        }

        $dataTmsg = "*".$nodeName."*:
        ```\n\nHeight Explorer:  ".$heightExplorer.""
        ."\n\nConsensus Main: ".$consensusMain."%"
        ."\nHeight Main:    ".$heightMain.""
        ."\nSyncing Main:   ".json_encode($syncingMain).""
        ."\nForging Main:   ".$forgingMain.""
        ."\n\nConsensus Backup:  ".$consensusBackup."%"
        ."\nHeight Backup:     ".$heightBackup.""
        ."\nSyncing Backup:    ".json_encode($syncingBackup).""
        ."\nForging Backup:    ".$forgingBackup."```";
    }


    // We are going to check a Local node => $apiHost = "http://127.0.0.1:netPort"
    if ($heightLocal < ($heightBlockchain - 10)) {

        // Going to check if it syncing
        if ($syncingLocal === true) {

            // Local node is syncing. Let's check if we can much faster restore from a snapshot
            if ($heightLocal < ($heightBlockchain - $snapThreshold)) {

                // Checking if we don't have failed recoveries from a local snapshot
                if ($db_data["recovery_from_snapshot"]) {

                    $Tmsg = "*".$nodeName."*: node is syncing, but reached a threshold.\n\t\t\tGoing to restore from snapshot.";
                    echo "\t\t\t".$Tmsg."\n";

                    // Second parameter $restoreEnable must be true for sending syncing messages to telegram
                    sendMessage($Tmsg, $restoreEnable);
                    sendMessage($dataTmsg, $restoreEnable);

                    echo "\t\t\tRestore from the last snapshot: \n";
                    system("cd $pathtoapp && ./shift_manager.bash stop");
                    sleep(3);
                    $restored = system("cd $snapshotDir && echo y | ./shift-snapshot.sh restore");
                    system("cd $pathtoapp && ./shift_manager.bash reload");

                    echo "\n\n\t\t\tRestored: $restored";

                    // Wait for height synced then turn it true
                    $db_data["recovery_from_snapshot"] = false;
                    saveToJSONFile($db_data, $database);

                    if ($restored == $restoredMsg) {

                        // Set counter for next good message
                        $db_data["rebuild_message_counter"] += 1;
                        saveToJSONFile($db_data, $database);

                        $Tmsg = "*".$nodeName."*: blockchain is restored from the last snapshot!";
                        sendMessage($Tmsg, $restoreEnable);
                        sendMessage($dataTmsg, $restoreEnable);

                    // Restorind blockchain from a local snapshot is failed.
                    // Try to rebuild from the last official snapshot
                    } else {

                        echo "\n\t\t\tRestored: NO!";

                        $db_data["recovery_from_snapshot"] = false;
                        $db_data["corrupt_snapshot"] = true;
                        $db_data["synchronized_after_corrupt_snapshot"] = false;
                        $db_data["rebuild_message_counter"] += 1;
                        saveToJSONFile($db_data, $database);

                        $Tmsg = "*".$nodeName."*: error! Going to rebuild with shift-manager.";
                        sendMessage($Tmsg, $restoreEnable);
                        sendMessage($dataTmsg, $restoreEnable);
                        echo "\n\t\t\t".$Tmsg."\n\n";

                        // Going to rebuild
                        system("cd $pathtoapp && ./shift_manager.bash rebuild");

                        // Pause to wait for start node sync.
                        echo "\t\t\tPause: 120 sec.\n\n";
                        sleep(120);                        

                    }
                }
            } 
            
            // Node is syncing. Last local snapshot blockchain height is lower than actual node's height.
            // Just wait for full sync

            // Sending a message in the first time and then every 10 minutes
            if (!$db_data["syncing_message_sent"] || $db_data["rebuild_message_counter"] % 10 === 0) {

                $Tmsg = "*".$nodeName."*: node is syncing.\n\t\t\tAll we need to do is wait... *(~‾▿‾)~*";
                echo "\t\t\t".$Tmsg."\n\n";
                sendMessage($Tmsg, $restoreEnable);
                sendMessage($dataTmsg, $restoreEnable);

                $db_data["syncing_message_sent"] = true;
                saveToJSONFile($db_data, $database);
            }

            // Add +1 to counter
            $db_data["rebuild_message_counter"] += 1;
            saveToJSONFile($db_data, $database);
        
        } 
        
        // else    
        // Node is not in synchronizing state. 
        // The local node is probably stuck

        // Let's wait for 20 sec for restore syncing status then turn rebuild
        if ($syncingLocal === false && $db_data["corrupt_snapshot"] == true) {

            echo "\t\t\tPause: 60 sec. to wait for syncing status\n\n";
            sleep(60); 

            // Check syncing status one more time
            $statusLocal = @file_get_contents($apiHost."/api/loader/status/sync");

            if ($statusLocal === false) {
                $consensusLocal = 0;
                $heightLocal = 0;
                $syncingLocal = false;
    
            } else {
                $statusLocal = json_decode($statusLocal, true);
    
                if (isset($statusLocal['height']) === false) {
                    $heightLocal = "error";
    
                } else {
                    $heightLocal = $statusLocal['height'];
    
                }
    
                $syncingLocal = $statusLocal['syncing'];
                $consensusLocal = $statusLocal['consensus'];
            }
        }

        // Node is not in synchronizing state
        // The local node is stuck
        if ($syncingLocal === false) {

            $restored = false;

            if ($db_data["recovery_from_snapshot"]) {

                $Tmsg = "*".$nodeName."*: height threshold is reached and not syncing.\n\t\t\tGoing to restore from snapshot.";
                echo "\t\t\t".$Tmsg."\n\n";
                sendMessage($Tmsg, $restoreEnable);
                sendMessage($dataTmsg, $restoreEnable);
    
                echo "\t\t\tRestore from the last snapshot: \n";
                system("cd $pathtoapp && ./shift_manager.bash stop");
                sleep(3);
                $restored = system("cd $snapshotDir && echo y | ./shift-snapshot.sh restore");
                system("cd $pathtoapp && ./shift_manager.bash reload");
    
                echo "\n\n\t\t\tRestored: $restored";

                // Wait for height synced then turn it true
                $db_data["recovery_from_snapshot"] = false;
                saveToJSONFile($db_data, $database);
                
                if ($restored == $restoredMsg) {

                    // Set counter for next good message
                    $db_data["rebuild_message_counter"] += 1;
                    saveToJSONFile($db_data, $database);
    
                    // Set pause for waiting node online
                    echo "\t\t\tPause: 120 sec. for start node sync.\n\n";
                    sleep(120);
    
                } else {
                    $db_data["recovery_from_snapshot"] = false;
                    $db_data["corrupt_snapshot"] = true;
                    $db_data["synchronized_after_corrupt_snapshot"] = false;
                    saveToJSONFile($db_data, $database);

                    $restored = false;
                }
            }
            
            if ($restored == false) {

                // Add checking for other sapshots
                // HERE

                // Going to rebuild
                $Tmsg = "*".$nodeName."*: height threshold is reached and not syncing. The last snapshot is corrupt! Going to rebuild with shift-manager.";
                sendMessage($Tmsg, $restoreEnable);
                echo "\n\t\t\t".$Tmsg."\n\n";

                system("cd $pathtoapp && ./shift_manager.bash rebuild");

                // Set counter for a next good message
                $db_data["rebuild_message_counter"] += 1;
                $db_data["corrupt_snapshot"] = true;
                saveToJSONFile($db_data, $database);

                // Pause to wait for start node sync
                echo "\t\t\tPause: 120 sec.\n\n";
                sleep(120);

            }
        }

    } else {

        echo "\t\t\tHeight on this node is fine.\n\n";

        // If we have bad messages send a good one
        if ($db_data["rebuild_message_counter"] > 0) {

            $Tmsg = "*".$nodeName."*: height is fine now.";
            sendMessage($dataTmsg, $restoreEnable);
            sendMessage($Tmsg, $restoreEnable);

            // Lets reset counters in the database
            // If snapshot is corrupt recovery from snapshot wiil be set true after creation of a new snapshot
            if ($db_data["corrupt_snapshot"] == false) {
                $db_data["recovery_from_snapshot"] = true;
            }

            // Blockchain is syncronized from rebuild after finding a corrupt local snapshot
            if ($db_data["corrupt_snapshot"] == true) {
                $db_data["synchronized_after_corrupt_snapshot"] = true;
            }

            $db_data["rebuild_message_counter"] = 0;
            $db_data["syncing_message_sent"] = false;


            saveToJSONFile($db_data, $database);

        }

    }

    // Finally, make sure all the data is saved to a file
    saveToJSONFile($db_data, $database);
    
} else {
    // Syncing is disabled in config.php
    echo "disabled\n\n";
}
