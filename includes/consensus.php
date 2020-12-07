<?php

echo "\n[ CONSENSUS ]\n\n";

echo "\t\t\tConsensus: ";

// We check if the script should work at all.
if ($switchingEnabled === true && !empty($secret)) {
    echo "enabled\n\n";

    if ($thisMain === true) {
        $mainnode = $localNode;
        $backupnode = $remoteNode;
    } else {
        $mainnode = $remoteNode;
        $backupnode = $localNode;
    }

    // Check height on Trusted node
    ['height' => $blockchain] = getNodeAPIData($trustedNode);

    // Check height, consensus and syncing on Main node
    ['height' => $heightMain,
    'consensus' => $consensusMain,
    'syncing' => $syncingMain] 
    = getNodeAPIData($mainnode);

    // Check height, consensus and syncing on Backup node
    ['height' => $heightBackup,
    'consensus' => $consensusBackup,
    'syncing' => $syncingBackup] 
    = getNodeAPIData($backupnode);

    $forgingBackup = checkForging($backupnode, $public);
    $forgingMain = checkForging($mainnode, $public);

    printTwoNodesData(
        $blockchain, $heightMain, $heightBackup, 
        $consensusMain, $consensusBackup,  $syncingMain, $syncingBackup,
        $forgingMain, $forgingBackup
    );

    // THE MAIN LOGIC STARTS HERE
    // LOGIC FOR MAIN NODE
    if ($thisMain === true) {

        echo "\t\t\tMain: true\n";

        // If we are forging..
        if ($forgingMain == "true") {
            echo "\t\t\tMain forging: true\n";

            if ($forgingBackup == "true") {
                echo "\t\t\tBackup forging: true!\n\n";
                $Tmsg = $nodeName . ":\n\nBoth nodes are forging! Probably shift-checker script is disabled on the backup node";
                echo "\t\t\t" . $Tmsg . "\n"; 
                sendMessage($Tmsg);

                // add counter here
                // stop forging on main after 3 times if Backup is still forging
            }

            // Check consensus on Main node
            // If consensus is the same as or lower than the set threshold. Going to restart Shift on Main
            if ($consensusMain <= $threshold && $syncingMain === false) {
                echo "\t\t\t" . $Tmsg . "\n";

                $Tmsg = $nodeName . ": Threshold on Main node reached! Going to check the Backup node and restart Shift on Main.";
                sendMessage($Tmsg);

                // Check consensus on Backup node
                // If consensus on the Backup is below threshold as well, send a telegram message and restart Shift!
                if ($consensusBackup <= $threshold && $syncingBackup === false) {
                    $Tmsg = $nodeName . ": Threshold on Backup node reached too! No healthy server online.";
                    echo "\t\t\t" . $Tmsg . "\n";
                    sendMessage($Tmsg);
                } else {

                    if ($syncingBackup === true) {
                        $Tmsg = $nodeName . ": Threshold reached on Main node, but Backup is syncing. No healthy server online.";
                        echo "\t\t\t" . $Tmsg . "\n\n";
                        sendMessage($Tmsg);
                    } else {
                        echo "\t\t\tConsensus on Backup is sufficient enough to switch to\n";

                        echo "\t\t\tDisabling forging on Main for secret: " . current($sec_array) . " - " . end($sec_array) . "\n\n";
                        disableForging($mainnode, $secret);
                        $forgingMain = false;
                    }
                }

                if (!$recoveryEnabled) {
                    echo "\t\t\tReloading Shift on Main\n";
                    shiftManager("reload");
                }
            } else {

                if ($syncingMain === true) {
                    $Tmsg = $nodeName . ": Main node is forging and syncing. Looks like a bug! Enabling forging on Backup node";
                    echo "\t\t\t" . $Tmsg . "\n";
                    sendMessage($Tmsg);

                    echo "\t\t\tDisabling forging on Main for secret: " . current($sec_array) . " - " . end($sec_array) . "\n\n";
                    disableForging($mainnode, $secret);
                    $forgingMain = false;

                    echo "\t\t\tRestarting Shift on Main\n";
                    shiftManager("reload");
                } else {
                    // Main consensus is high enough to continue forging
                    echo "\n\t\t\tThreshold on Main node not reached.\n\n\t\t\tEverything is okay.\n\n";
                }
            }

        // If we are Main and not forging
        } else {

            if ($forgingMain == "error") {
                echo "\t\t\tMain forging: error!\n";
            } else {
                echo "\t\t\tMain forging: false!\n";
            }

            // Check if the Backup is forging
            if ($forgingBackup == "true") {
                echo "\t\t\tBackup forging: true\n\n";

                // If consensus is the same as or lower than the set threshold
                if ($consensusBackup <= $threshold) {
                    echo "\t\t\tConsensus Backup reached the threshold.\n";
                    echo "\t\t\tChecking consensus, height and syncing on Main node..\n";

                    // If consensus is the same as or lower than the set threshold..
                    if ($consensusMain <= $threshold && $syncingMain === false) {
                        echo "\t\t\tThreshold on Main node reached as well! Restarting Shift..\n";

                        if (!$recoveryEnabled) {
                            echo "\t\t\tRestarting Shift on Main\n";
                            shiftManager("reload");
                        }
                    } else {

                        if ($syncingMain === true) {
                            echo "\t\t\tMain node is syncing. Doing nothing\n";

                            $Tmsg = $nodeName . ": Warning! Consensus Backup reached the threshold, but Main node is syncing. No healthy servers online!";
                            echo "\t\t\t" . $Tmsg . "\n";
                            sendMessage($Tmsg);
                        } else {
                            // Consensus is sufficient on Main. Going to check syncing of Mainnode with good consensus
                            echo "\t\t\tConsensus on Main is sufficient.\n";

                            if ($heightMain < ($blockchain - 101)) {
                                echo "\t\t\tBut seems Main node is syncing. Doing nothing..\n";

                                $Tmsg = $nodeName . ": Warning! Consensus Backup reached the threshold, but seems Main node is syncing. No healthy servers online!";
                                echo "\t\t\t" . $Tmsg . "\n";
                                sendMessage($Tmsg);
                            } else {
                                echo "\t\t\tMain node is synced!\n";
                                echo "\t\t\tEnabling forging on Main for secret: " . current($sec_array) . " - " . end($sec_array) . "\n\n";
                                enableForging($mainnode, $secret);
                                $forgingMain = true;
                            }
                        }
                    }
                } else {
                    echo "\t\t\tConsensus on Backup is sufficient. Doing nothing\n\n";
                }
            } else {
                if ($forgingBackup == "error") {
                    echo "\t\t\tChecking of Backup's forging got an error!\n";
                } else {
                    echo "\t\t\tBackup is not forging as well!\n";
                }

                // Backup is also not forging! Compare consensus on both nodes and enable forging on node with highest consensus an height
                $Tmsg = $nodeName . ": Main and Backup are both not forging! Going to enable forging on the best node.";
                sendMessage($Tmsg);

                echo "\t\t\tLet's compare consensus and enable forging on best node\n";

                if ($consensusMain >= $consensusBackup && $heightMain >= ($heightBackup - 3)) {
                    echo "\t\t\tEnabling forging on Main for secret: " . current($sec_array) . " - " . end($sec_array) . "\n\n";
                    enableForging($mainnode, $secret);
                    $forgingMain = true;
                } else {
                    echo "\t\t\tNeed to enable forging on Backup\n\n";
                    // enableForging($backupnode, $secret);
                } // end: compare consensus
            } // end: backup forging is false
        } // end: main forging is false
    } // end: we are the main

    // LOGIC FOR THE BACKUP NODE
    if ($thisMain === false) {
        // If we land here, we are the Backup
        echo "\t\t\tBackup: true\n";

        echo "\t\t\tMain online: ";
        // Check if the Main is online
        $up = ping($mainnode);

        if ($up) {
            // Main is online.
            echo "true\n";

            echo "\t\t\tBackup forging: ";

            // If Backup is forging.
            if ($forgingBackup == "true") {

                echo "true!";

                if ($forgingMain == "true") {
                    
                    echo "\n\t\t\tMain forging: true!\n";
                    echo "\n\t\t\tDisabling forging on Backup for secret: " . current($sec_array) . " - " . end($sec_array) . "\n";
                    disableForging($backupnode, $secret);
                    $forgingBackup = false;
                }

                echo "\n\n\t\t\tEverything seems okay.\n\n";

            } else {
                // Main node is online, backup node is not forging.

                if ($forgingBackup == "error") {
                    echo "error\n";
                } else {
                    echo "false\n";
                }

                // Check if the Main is syncing
                echo "\t\t\tMain syncing: ";

                // Compare height on Main node and Backup node.
                if ($heightMain < ($heightBackup - 5)) {
                    // Height on Main is lags behind. Enable forging on Backup
                    echo "true\n";
                    echo "\t\t\tEnabling forging on Backup for secret: " . current($sec_array) . " - " . end($sec_array) . "\n";
                    enableForging($backupnode, $secret);
                    $forgingBackup = true;
                } else {
                    // Main syncing:
                    echo "false";

                    // Let's check if Main is okay to forge if not start forging on Backup
                    if ($forgingMain == "false") {

                        if ($consensusMain <= $threshold && $syncingMain === false) {
                            echo "\n\t\t\tThreshold on Main node reached!\n";
                            echo "\t\t\tEnabling forging on Backup for secret: " . current($sec_array) . " - " . end($sec_array) . "\n";
                            enableForging($backupnode, $secret);
                            $forgingBackup = true;
                        }

                        if ($consensusMain < $consensusBackup && $heightMain < ($heightBackup - 3)) {
                            echo "\n\t\t\tThreshold on Main node reached!\n";
                            echo "\t\t\tEnabling forging on Backup for secret: " . current($sec_array) . " - " . end($sec_array) . "\n";
                            enableForging($backupnode, $secret);
                            $forgingBackup = true;
                        }
                    }

                    // Main is synced. Let's check if shift-checker is working on Main node.
                    if ($forgingMain == "false" && !$forgingBackup) {
                        echo "\n\n\t\t\tBoth nodes are not forging!";
                        echo "\n\t\t\tLet's check if shift-checker is run on the main node.";

                        $db_data["script_disabled_counter"] -= 1;
                        saveToJSONFile($db_data, $database);

                        if ($db_data["script_disabled_counter"] == 0) {

                            $db_data["script_disabled_counter"] = 3;
                            saveToJSONFile($db_data, $database);

                            $Tmsg = $nodeName . ": Looks like shift-checker is disabled on the main node.\n\t\t\tStart forging on the backup node.\n\n";
                            echo "\n\t\t\t" . $Tmsg . "\n";
                            sendMessage($Tmsg, true);

                            echo "\t\t\tEnabling forging on Backup for secret: " . current($sec_array) . " - " . end($sec_array) . "\n";
                            enableForging($backupnode, $secret);
                            $forgingBackup = true;
                        } else {

                            echo "\n\t\t\t" . $db_data["script_disabled_counter"] . " minutes to start forging on the backup node.";
                        }
                    }

                    echo "\n\n\t\t\tEverything will be okay.\n\n";
                }
            }
        } else {
            // Main is offline. Let's check if we are forging, if not; enable it.
            echo "false!\n";

            echo "\t\t\tBackup forging: ";

            // If we are forging..
            if ($forgingBackup == "true") {
                echo "true!\n\n";

                // If consensus on the Backup is below threshold divided by two (because of "Main is offline") as well, restart Shift!
                if ($consensusBackup <= ($threshold / 2) && $syncingBackup === false) {
                    $Tmsg = $nodeName . ": Threshold on Backup node reached! No healthy server online.";
                    echo "\t\t\t" . $Tmsg . "\n";
                    sendMessage($Tmsg);

                    // Restart Shift on Backup if restoring is disabled
                    if (!$recoveryEnabled) {
                        echo "\t\t\tRestarting Shift on Main\n\n";
                        shiftManager("reload");
                    }
                } else {

                    if ($syncingBackup === true) {
                        $Tmsg = $nodeName . ": Backup node is forging and syncing. Looks like a bug! Disabling forging on Backup node.";
                        echo "\t\t" . $Tmsg . "\n";
                        sendMessage($Tmsg);

                        echo "\t\t\tDisabling forging on Backup for secret: " . current($sec_array) . " - " . end($sec_array) . "\n";
                        disableForging($backupnode, $secret);
                        $forgingBackup = false;

                        echo "\t\t\tRestarting Shift on Backup\n\n";
                        shiftManager("reload");
                    } else {
                        // All is fine. Do nothing..
                        echo "\t\t\tConsensus is fine!\n\n";
                    }
                }
            } else {

                if ($forgingBackup == "error") {
                    echo "error\n";
                } else {
                    echo "false!\n\n\t\t\tWe are not forging! Let's enable it\n";
                }

                $Tmsg = $nodeName . ": Main node seems offline. Backup starts forging now";
                sendMessage($Tmsg);

                echo "\t\t\tEnabling forging on Backup for secret: " . current($sec_array) . " - " . end($sec_array) . "\n\n";
                enableForging($backupnode, $secret);
                $forgingBackup = true;
            }
        }
    }
    // DEBUG
    if ($forgingBackup == "true" && $forgingMain == "true") {
        $Tmsg = "Both nodes are forging";
        sendMessage($Tmsg);
    }

    if ($forgingBackup == "false" && !$forgingMain == "false") {
        $Tmsg = "Both nodes are not forging";
        sendMessage($Tmsg);
    }
    // END DEBUG

} else {
    echo "disabled or no secret\n\n";

    if ($recoveryEnabled === false) {
        // Check height on Trusted node
        ['height' => $blockchain] = getNodeAPIData($trustedNode);

        // Check height, consensus and syncing on Main node
        ['height' => $heightMain,
        'consensus' => $consensusMain,
        'syncing' => $syncingMain] 
        = getNodeAPIData($mainnode);

        $forgingMain = checkForging($mainnode, $public);

        printNodeData("Local", $blockchain, $heightMain, $consensusMain, $syncingMain, $forgingMain);
    }
} // END: ENABLED CONSENSUS CHECK
