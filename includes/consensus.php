<?php

echo "\n[ CONSENSUS ]\n\n";

  echo "\t\t\tConsensus: ";

  // We check if the script should work at all.
  if ($consensusEnable === true && !empty($secret)) {
    echo "enabled\n\n";

    // Check height, consensus and syncing on Blockhain
    $heightExplorer = @file_get_contents($explorer."/api/statistics/getLastBlock");

    if ($heightExplorer === false) {
        $heightExplorer = 0;
    } else {
        $heightExplorer = json_decode($heightExplorer, true);
        $heightExplorer = $heightExplorer['block']['height'];
    }

    // Check height, consensus and syncing on Main node
    $statusMain = @file_get_contents($mainnode.":".$mainport."/api/loader/status/sync");

    if ($statusMain === false) {
        $consensusMain = 0;
        $heightMain = 0;
        $syncingMain = false;
    } else {
        $statusMain = json_decode($statusMain, true);

        if (isset($statusMain['height']) === false) {
            $heightMain = "error";
        } else {
            $heightMain = $statusMain['height'];
        }

        $syncingMain = $statusMain['syncing'];
        $consensusMain = $statusMain['consensus'];
    }

    // Check height, consensus and syncing on Backup node
    $statusBackup = @file_get_contents($backupnode.":".$backupport."/api/loader/status/sync");

    if ($statusBackup === false) {
        $consensusBackup = 0;
        $heightBackup = 0;
        $syncingBackup = false;
    } else {
        $statusBackup = json_decode($statusBackup, true);

        if (isset($statusBackup['height']) === false) {
            $heightBackup = "error";
        } else {
            $heightBackup = $statusBackup['height'];
        }

        $syncingBackup = $statusBackup['syncing'];
        $consensusBackup = $statusBackup['consensus'];
    }

    // Get publicKey of the secret to use in forging checks
    $public = checkPublic($apiHost, $secret);

    // Secret to array
    $sec_array = explode(" ", $secret);

    $forgingBackup = checkForging($backupnode.":".$backupport, $public);
    $forgingMain = checkForging($mainnode.":".$mainport, $public);

    echo "\t\t\tHeight Explorer: $heightExplorer\n\n";

    echo "\t\t\tConsensus Main: ".$consensusMain."%";
    echo "\t\tConsensus Backup: ".$consensusBackup."%\n";

    echo "\t\t\tHeight Main: $heightMain";
    echo "\t\tHeight Backup: $heightBackup \n";

    echo "\t\t\tSyncing Main: ".json_encode($syncingMain); // Boolean to string
    echo "\t\t\tSyncing Backup: ".json_encode($syncingBackup)."\n";

    echo "\t\t\tForging Main: ".$forgingMain;
    echo("\t\t\tForging Backup: ".$forgingBackup."\n\n");

    // THE MAIN LOGIC STARTS HERE
    // LOGIC FOR MAIN NODE
    if ($main === true) {

        echo "\t\t\tMain: true\n";

        // Check if we are forging
        $forging = checkForging($mainnode.":".$mainport, $public);

        // If we are forging..
        if ($forging == "true") {
            echo "\t\t\tMain forging: true\n\n";

            // Forging on the Backup should be/stay disabled for secret until we perform a consensus check.
            // This way we ensure that forging is only disabled on nodes the Main chooses.
            echo "\t\t\tDisabling forging on Backup for secret: ".current($sec_array)." - ".end($sec_array)."\n";
            disableForging($backupnode.":".$backupport, $secret);

            // Check consensus on Main node
            // If consensus is the same as or lower than the set threshold. Going to restart Shift on Main
            if ($consensusMain <= $threshold && $syncingMain === false) {
                echo "\t\t\t".$Tmsg."\n";

                $Tmsg = $nodeName.": Threshold on Main node reached! Going to check the Backup node and restart Shift on Main.";
                sendMessage($Tmsg);

                // Check consensus on Backup node
                // If consensus on the Backup is below threshold as well, send a telegram message and restart Shift!
                if ($consensusBackup <= $threshold && $syncingBackup === false) {
                    $Tmsg = $nodeName.": Threshold on Backup node reached! No healthy server online.";
                    echo "\t\t\t".$Tmsg."\n";
                    sendMessage($Tmsg);

                } else {

                    if ($syncingBackup === true) {
                        $Tmsg = $nodeName.": Threshold reached on Main node, but Backup is syncing. No healthy server online.";
                        echo "\t\t\t".$Tmsg."\n\n";
                        sendMessage($Tmsg);
                    } else {
                        echo "\t\t\tConsensus on Backup is sufficient enough to switch to..\n";
            
                        echo "\t\t\tEnabling forging on Backup for secret: ".current($sec_array)." - ".end($sec_array)."\n";
                        enableForging($backupnode.":".$backupport, $secret);

                        echo "\t\t\tDisabling forging on Main for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                        disableForging($mainnode.":".$mainport, $secret);
                    }
                }

                if (!$restoreEnable) {
                    echo "\t\t\tRestarting Shift on Main\n";
                    system("cd $pathtoapp && ./shift_manager.bash reload");
                }

            } else {

                if ($syncingMain === true) {
                    $Tmsg = $nodeName.": Main node is forging and syncing. Looks like a bug! Enabling forging on Backup node";
                    echo "\t\t\t".$Tmsg."\n";
                    sendMessage($Tmsg);

                    echo "\t\t\tEnabling forging on Backup for secret: ".current($sec_array)." - ".end($sec_array)."\n";
                    enableForging($backupnode.":".$backupport, $secret);

                    echo "\t\t\tDisabling forging on Main for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                    disableForging($mainnode.":".$mainport, $secret);

                    echo "\t\t\tRestarting Shift on Main\n";
                    passthru("cd $pathtoapp && ./shift_manager.bash reload");

                } else {
                    // Main consensus is high enough to continue forging
                    echo "\t\t\tThreshold on Main node not reached.\n\n\t\t\tEverything is okay.\n\n";
                }
            }

        // If we are Main and not forging..
        } else {

            if ($forging == "error") {
                echo "\t\t\tMain forging: error!\n";
            } else {
                echo "\t\t\tMain forging: false!\n";
            }
            // Check if the Backup is forging
            $forging = checkForging($backupnode.":".$backupport, $public);

            // If Backup is forging..
            if ($forging == "true") {
                echo "\t\t\tBackup forging: true\n\n";

                // If consensus is the same as or lower than the set threshold..
                if ($consensusBackup <= $threshold) {
                    echo "\t\t\tConsensus Backup reached the threshold.\n";
                    echo "\t\t\tChecking consensus, height and syncing on Main node..\n";
        
                    // If consensus is the same as or lower than the set threshold..
                    if ($consensusMain <= $threshold && $syncingMain === false) {
                        echo "\t\t\tThreshold on Main node reached as well! Restarting Shift..\n";

                        if (!$restoreEnable) {
                            echo "\t\t\tRestarting Shift on Main\n";
                            system("cd $pathtoapp && ./shift_manager.bash reload");
                        }
                    } else {

                        if ($syncingMain === true) {
                            echo "\t\t\tMain node is syncing. Doing nothing..\n";
            
                            $Tmsg = $nodeName.": Warning! Consensus Backup reached the threshold, but Main node is syncing. No healthy servers online!";
                            echo "\t\t\t".$Tmsg."\n";
                            sendMessage($Tmsg);

                        } else {
                            // Consensus is sufficient on Main. Going to check syncing of Mainnode with 100% consensus
                            echo "\t\t\tConsensus on Main is sufficient.\n";

                            if ($heightMain < ($heightExplorer - 101)) {
                                echo "\t\t\tBut seems Main node is syncing. Doing nothing..\n";

                                $Tmsg = $nodeName.": Warning! Consensus Backup reached the threshold, but seems Main node is syncing. No healthy servers online!";
                                echo "\t\t\t".$Tmsg."\n";
                                sendMessage($Tmsg);

                            } else {
                                echo "\t\t\tMain node is synced!\n";
                                echo "\t\t\tEnabling forging on Main for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                                enableForging($mainnode.":".$mainport, $secret);

                                echo "\t\t\tDisabling forging on Backup..\n";
                                echo "\t\t\tDisabling forging on Backup for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                                disableForging($backupnode.":".$backupport, $secret);
                            }
                        }
                    }
                } else {
                    echo "\t\t\tConsensus on Backup is sufficient. Doing nothing..\n\n";
                }
            } else {
                if ($forging == "error") {
                    echo "\t\t\tChecking of Backup's forging got an error!\n";

                } else {
                    echo "\t\t\tBackup is not forging as well!\n";
                }
                // Backup is also not forging! Compare consensus on both nodes and enable forging on node with highest consensus an height..
                $Tmsg = $nodeName.": Main and Backup are both not forging! Going to enable forging on the best node.";
                sendMessage($Tmsg);

                echo "\t\t\tLet's compare consensus and enable forging on best node..\n";

                if ($consensusMain > $consensusBackup && $heightMain > ($heightBackup - 10)) {
                    echo "\t\t\tEnabling forging on Main for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                    enableForging($mainnode.":".$mainport, $secret);

                } else {
                    echo "\t\t\tEnabling forging on Backup for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                    enableForging($backupnode.":".$backupport, $secret);

                } // end: compare consensus
            } // end: backup forging is false
        } // end: main forging is false
    } // end: we are the main

    // LOGIC FOR THE BACKUP NODE
    if ($main === false) {
        // If we land here, we are the Backup
        echo "\t\t\tBackup: true\n";

        echo "\t\t\tMain online: ";
        // Check if the Main is online
        $find = array("http://","https://");
        $up = ping(str_replace($find, "", $mainnode), $mainport);

        if ($up) {
            // Main is online. Do nothing..
            echo "true\n";

            // Check if we are forging
            echo "\t\t\tBackup forging: ";
            $forging = checkForging($backupnode.":".$backupport, $public);
        
            // If we are forging..
            if ($forging == "true") {
                echo "true!\n\n\t\t\tEverything seems okay.\n\n";
            } else {

                if ($forging == "error") {
                    echo "error\n";
                } else {
                    echo "false\n";
                }
                // Check if the Main is synced
                echo "\t\t\tMain syncing: ";

                // COMPARE HEIGHT on Main node and Backup node
                if ($heightMain < ($heightBackup - 10)) {
                    // Enable forging on Backup
                    echo "true\n";
                    echo "\t\t\tEnabling forging on Backup for secret: ".current($sec_array)." - ".end($sec_array)."\n";
                    enableForging($backupnode.":".$backupport, $secret);
                } else {
                    // Main is synced. Do nothing..
                    echo "false\n\n\t\t\tEverything will be okay.\n\n";
                }
            }
        } else {
            // Main is offline. Let's check if we are forging, if not; enable it.
            echo "false!\n";

            echo "\t\t\tBackup forging: ";
            $forging = checkForging($backupnode.":".$backupport, $public);
        
            // If we are forging..
            if ($forging == "true") {
                echo "true!\n\n";

                // Send Telegram ANNOYING!!! Backup is forging!
                // $Tmsg = $nodeName.": Main node seems offline. Backup is forging though..";
                // sendMessage($Tmsg);
        
                // If consensus on the Backup is below threshold divided by two (becouse of Main is offline) as well, restart Shift!
                if ($consensusBackup <= ($threshold / 2) && $syncingBackup === false) {
                    $Tmsg = $nodeName.": Threshold on Backup node reached! No healthy server online.";
                    echo "\t\t\t".$Tmsg."\n";
                    sendMessage($Tmsg);

                    // Restart Shift on Backup if restoring is disabled
                    if (!$restoreEnable) {
                        echo "\t\t\tRestarting Shift on Main\n\n";
                        system("cd $pathtoapp && ./shift_manager.bash reload");
                    }
                } else {

                    if ($syncingBackup === true) {
                        $Tmsg = $nodeName.": Backup node is forging and syncing. Looks like a bug! Enabling forging on Main node.";
                        echo "\t\t".$Tmsg."\n";
                        sendMessage($Tmsg);

                        echo "\t\t\tDisabling forging on Backup for secret: ".current($sec_array)." - ".end($sec_array)."\n";
                        disableForging($backupnode.":".$backupport, $secret);

                        echo "\t\t\tRestarting Shift on Backup\n\n";
                        system("cd $pathtoapp && ./shift_manager.bash reload");
                    } else {
                        // All is fine. Do nothing..
                        echo "\t\t\tConsensus is fine!\n\n";
                    }
                }
            } else {

                if ($forging == "error") {
                    echo "error\n";
                } else {
                    echo "false!\n\n\t\t\tWe are not forging! Let's enable it..\n";
                }
                // Enable forging on the Backup
                // If Telegram is enabled, send a message that the Main seems offline
                $Tmsg = $nodeName.": Main node seems offline. Backup will enable forging now..";
                sendMessage($Tmsg);

                echo "\t\t\tEnabling forging on Backup for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                enableForging($backupnode.":".$backupport, $secret);
            }
        }
    }
  } else {
      echo "disabled (or no secret)\n\n";
  } // END: ENABLED CONSENSUS CHECK?
