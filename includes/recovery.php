<?php

echo "\n[ RECOVERY ]\n\n";

// Start to check syncing
echo "\t\t\tRecovery: ";

if ($recoveryEnabled === true) {
    echo "enabled\n\n";

    // Let's define and show variables
    if ($switchingEnabled === false) {

        // Check height on Trusted node
        ['height' => $blockchain] = getNodeAPIData($trustedNode);

        $heightBlockchain = $blockchain;

        // Check height, consensus and syncing on Main node
        ['height' => $heightLocal,
        'consensus' => $consensusLocal,
        'syncing' => $syncingLocal] 
        = getNodeAPIData($localNode);

        $forgingLocal = checkForging($localNode, $public);

        printNodeData("Local", $blockchain, $heightLocal, $consensusLocal, $syncingLocal, $forgingLocal);

        // a message for Telegram notifications
        $dataTmsg = "*".$nodeName."*:
        ```\n\nHeight Blockchain: ".$heightBlockchain.""
        ."\nHeight Node:     ".$heightLocal.""
        ."\nConsensus Node:  ".$consensusLocal."%"
        ."\nSyncing Node:    ".json_encode($syncingLocal).""
        ."\nForging Node:     ".$forgingLocal."```";

    } else {

        // Consensus is enabled, variables are defined and shown from the CONSENSUS block
        if ($thisMain === true) {
            $heightLocal = $heightMain;
            $syncingLocal = $syncingMain;
            $consensusLocal = $consensusMain;

            // If trusted node is down => Use Backup height
            if ($blockchain > 0) {
                $heightBlockchain = $blockchain;

            } else {
                $heightBlockchain = $heightBackup;
            }

        } else {
            // We are Backup here
            $heightLocal = $heightBackup;
            $syncingLocal = $syncingBackup;
            $consensusLocal = $consensusBackup;

            // If trusted node is down => Use Main height
            if ($blockchain > 0) {
                $heightBlockchain = $blockchain;

            } else {
                $heightBlockchain = $heightBackup;

            }
        }

        // a message for Telegram notifications
        $dataTmsg = "*".$nodeName."*:
        ```\n\nHeight Blockhain:  ".$blockchain.""
        ."\n\nConsensus Main: ".$consensusMain."%"
        ."\nHeight Main:    ".$heightMain.""
        ."\nSyncing Main:   ".json_encode($syncingMain).""
        ."\nForging Main:   ".$forgingMain.""
        ."\n\nConsensus Backup:  ".$consensusBackup."%"
        ."\nHeight Backup:     ".$heightBackup.""
        ."\nSyncing Backup:    ".json_encode($syncingBackup).""
        ."\nForging Backup:    ".$forgingBackup."```";
    }

    // MAIN LOGIC HERE
    // We are going to check a Local node => $localNode = "http://127.0.0.1:netPort"
    if ($heightLocal < ($heightBlockchain - 10)) {

        // Going to check if it syncing
        if ($syncingLocal === true) {

            // Local node is syncing. Let's check if we can much faster restore from a snapshot
            if ($heightLocal < ($heightBlockchain - $snapThreshold)) {

                // Checking if we don't have failed recoveries from a local snapshot
                if ($db_data["recovery_from_snapshot"]) {

                    $Tmsg = "*".$nodeName."*: node is syncing, but reached a threshold.\n\t\t\tGoing to restore from snapshot.";
                    echo "\t\t\t".$Tmsg."\n";

                    // Second parameter $recoveryEnabled must be true for sending syncing messages to telegram
                    sendMessage($Tmsg, $recoveryMessages);
                    sendMessage($dataTmsg, $recoveryMessages);

                    echo "\t\t\tRestore from the last snapshot: \n";
                    shiftManager("stop");
                    sleep(3);
                    $restored = shiftSnapshot("restore");
                    shiftManager("start");

                    echo "\n\n\t\t\tRestored: $restored";

                    // Wait for height synced then turn it true
                    $db_data["recovery_from_snapshot"] = false;
                    saveToJSONFile($db_data, $database);

                    if ($restored == $restoredMsg) {

                        // Set counter for next good message
                        $db_data["rebuild_message_counter"] += 1;
                        saveToJSONFile($db_data, $database);

                        $Tmsg = "*".$nodeName."*: blockchain is restored from the last snapshot!";
                        sendMessage($Tmsg, $recoveryMessages);
                        sendMessage($dataTmsg, $recoveryMessages);

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
                        sendMessage($Tmsg, $recoveryMessages);
                        sendMessage($dataTmsg, $recoveryMessages);
                        echo "\n\t\t\t".$Tmsg."\n\n";

                        // Going to rebuild
                        shiftManager("rebuild");

                        // Pause to wait for start node sync.
                        pauseToWaitNodeAPI(120);
                    }
                }
            } 
            
            // Node is syncing. Last local snapshot blockchain height is lower than actual node's height.
            // Just wait for full sync

            // Sending a message in the first time and then every 10 minutes
            if (!$db_data["syncing_message_sent"] || $db_data["rebuild_message_counter"] % 10 === 0) {

                $Tmsg = "*".$nodeName."*: node is syncing.\n\t\t\tAll we need to do is wait... *(~‾▿‾)~*";
                echo "\t\t\t".$Tmsg."\n\n";
                sendMessage($Tmsg, $recoveryMessages);
                sendMessage($dataTmsg, $recoveryMessages);

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

        // Let's wait for 60 sec for restore syncing status then turn rebuild
        if ($syncingLocal === false && $db_data["corrupt_snapshot"] == true) {

            echo "\t\t\tLet's wait for syncing status\n\n";
            pauseToWaitNodeAPI(120);

            // Check syncing status one more time
            $statusLocal = @file_get_contents($localNode."/api/loader/status/sync");

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

                $Tmsg = "*".$nodeName."*:\n\n$stopEmoji Height threshold is reached and not syncing\n$recoveryEmoji Going to restore from a local snapshot";
                echo "\t\t\t".$Tmsg."\n\n";
                sendMessage($Tmsg, $recoveryMessages);
                sendMessage($dataTmsg, $recoveryMessages);
    
                echo "\t\t\tRestore from the last snapshot: \n";
                shiftManager("stop");
                sleep(3);
                $restored = shiftSnapshot("restore");
                shiftManager("start");
    
                echo "\n\n\t\t\tRestored: $restored";

                // Wait for height synced then turn it true
                $db_data["recovery_from_snapshot"] = false;
                saveToJSONFile($db_data, $database);
                
                if ($restored == $restoredMsg) {

                    // Set counter for next good message
                    $db_data["rebuild_message_counter"] += 1;
                    saveToJSONFile($db_data, $database);
    
                    // Pause to wait for start node sync.
                    pauseToWaitNodeAPI(120);
    
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
                sendMessage($Tmsg, $recoveryMessages);
                echo "\n\t\t\t".$Tmsg."\n\n";

                shiftManager("rebuild");

                // Set counter for a next good message
                $db_data["rebuild_message_counter"] += 1;
                $db_data["corrupt_snapshot"] = true;
                saveToJSONFile($db_data, $database);

                // Pause to wait for start node sync.
                pauseToWaitNodeAPI(120);

            }
        }

    } else {

        echo "\n\t\t\tHeight on this node is fine.\n\n";

        // If we have bad messages send a good one
        if ($db_data["rebuild_message_counter"] > 0) {

            $Tmsg = "*".$nodeName."*: height is fine now.";
            sendMessage($dataTmsg, $recoveryMessages);
            sendMessage($Tmsg, $recoveryMessages);

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
