<?php

echo "\n[ SYNCING ]\n\n";

// Start to check syncing
echo "\t\t\tSyncing: ";

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

        $dataTmsg = "*".gethostname()."*:
        ```\n\nHeight Explorer: ".$heightBlockchain.""
        ."\nConsensus Node:  ".$consensusLocal."%"
        ."\nSyncing Node:    ".json_encode($syncingLocal).""
        ."\nHeight Node:     ".$heightLocal."```";

    } else {

        // Consensus is enabled, variables are defined and shown from the CONSENSUS block
        if ($master === true) {
            $heightLocal = $heightMaster;
            $syncingLocal = $syncingMaster;
            $consensusLocal = $consensusMaster;

            // If Explorer is down => Use Slave height
            if ($heightExplorer > 0) {
                $heightBlockchain = $heightExplorer;

            } else {
                $heightBlockchain = $heightSlave;

            }

        } else {
            // We are Slave here
            $heightLocal = $heightSlave;
            $syncingLocal = $syncingSlave;
            $consensusLocal = $consensusSlave;

            // If Explorer is down => Use Master height
            if ($heightExplorer > 0) {
                $heightBlockchain = $heightExplorer;

            } else {
                $heightBlockchain = $heightSlave;

            }
        }

        $dataTmsg = "*".gethostname()."*:
        ```\n\nHeight Explorer:  ".$heightExplorer.""
        ."\n\nConsensus Master: ".$consensusMaster."%"
        ."\nHeight Master:    ".$heightMaster.""
        ."\nSyncing Master:   ".json_encode($syncingMaster).""
        ."\nForging Master:   ".$forgingMaster.""
        ."\n\nConsensus Slave:  ".$consensusSlave."%"
        ."\nHeight Slave:     ".$heightSlave.""
        ."\nSyncing Slave:    ".json_encode($syncingSlave).""
        ."\nForging Slave:    ".$forgingSlave."```";
    }

    // START UPDATE HERE
    // Switch from SQLite3 to JSON file

    //Going to use database for counting rebuilds and messages
    $database = $baseDir."check_rebuild.sqlite3";
    $db = new SQLite3($database) or die("\n\t\t\tUnable to open database");
    $table = "rebuilds";

    // Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS $table (
                id INTEGER PRIMARY KEY,  
                counter INTEGER,
                time INTEGER)");

    // Let's check if any rows exists in our table
    $check_exists = $db->query("SELECT count(*) AS count FROM $table");
    $row_exists   = $check_exists->fetchArray();
    $numExists    = $row_exists['count'];

    // If no rows exist in our table, add one
    if ($numExists < 1) {
        echo "\t\t\tNo rows... Adding a row for you.\n";

        $insert = "INSERT INTO $table (counter, time) VALUES ('0', time())";
        $db->exec($insert) or die("\n\t\t\tFailed to add a row!");
    }

    // Get counter value from our database
    $check_count    = $db->query("SELECT * FROM $table LIMIT 1");
    $row            = $check_count->fetchArray();
    $counter        = $row['counter'];

    //echo "\n\t\t\tCounter: $counter\n\n"; //DELETE

    // We are going to check a Local node => $apiHost = "http://127.0.0.1:netPort"
    if ($heightLocal < ($heightBlockchain - 10)) {
        // Going to check if it syncing
        if ($syncingLocal === true) {
            // Local node is syncing. Everything going to be okay
            if ($heightLocal < ($heightBlockchain - $snapThreshold)) {

                // Disabling double messaging
                if ($counter < 2) {
                    $Tmsg = "*".gethostname()."*: node is syncing, but reached a threshold.\n\t\t\tGoing to restore from snapshot.";
                    echo "\t\t\t".$Tmsg."\n";
                    // Second parameter $restoreEnable = true; for syncing massages if enabled
                    sendMessage($Tmsg, $restoreEnable);
                    sendMessage($dataTmsg, $restoreEnable);
                }

                echo "\t\t\tRestore from the last snapshot: \n";
                system("cd $pathtoapp && ./shift_manager.bash stop");
                sleep(3);
                $restored = system("cd $snapshotDir && echo y | ./shift-snapshot.sh restore");
                system("cd $pathtoapp && ./shift_manager.bash reload");

                echo "\n\n\t\t\tRestored: $restored";

                if ($restored == $restoredMsg) {
                    echo "\n\t\t\tRestored: true\n\n";

                    // Lets reset counting in database
                    $query = "UPDATE $table SET counter='0', time=time()";
                    $db->exec($query) or die("\n\t\t\tUnable to set counter to 0!");

                    $Tmsg = "*".gethostname()."*: blockchain is restored from the last snapshot!";
                    sendMessage($Tmsg, $restoreEnable);
                    sendMessage($dataTmsg, $restoreEnable);

                } else {
                    echo "\n\t\t\tRestored: NO!";

                    // Disabling double messaging
                    if ($counter < 1) {
                        $Tmsg = "*".gethostname()."*: error! Going to rebuild with shift-manager.";
                        sendMessage($Tmsg, $restoreEnable);
                        sendMessage($dataTmsg, $restoreEnable);
                        echo "\n\t\t\t".$Tmsg."\n\n";
                    }

                    if ($counter < 1) {
                        // Going to rebuild
                        system("cd $pathtoapp && ./shift_manager.bash rebuild");
                        // Add 1 to database
                        $query = "UPDATE $table SET counter='1', time=time()";
                        $db->exec($query) or die("\n\t\t\tUnable to plus the rebuild counter!");

                    } else {

                        // Set to send message every 2 minutes
                        if (($counter % 2) !== 0 && $counter < 6) {
                            $Tmsg = "*".gethostname()."*: Restore from snapshot failed.\n\t\t\tNode is rebuilded and syncing now.";
                            sendMessage($Tmsg, $restoreEnable);
                            sendMessage($dataTmsg, $restoreEnable);
                            echo "\n\t\t\t".$Tmsg."\n\n";
                        }

                        // Sending set for every 5 minutes
                        if ($counter > 6 && ($counter % 5) === 0 && $counter < 16) {
                            $Tmsg = "*".gethostname()."*: Restore from snapshot failed.\n\t\t\tNode is rebuilded and syncing now.";
                            sendMessage($Tmsg, $restoreEnable);
                            sendMessage($dataTmsg, $restoreEnable);
                            echo "\n\t\t\t".$Tmsg."\n\n";
                        }

                        // Sending set for every 10 minutes
                        if ($counter > 16 && ($counter % 10) === 0) {
                            $Tmsg = "*".gethostname()."*: Restore from snapshot failed.\n\t\t\tNode is rebuilded and syncing now.";
                            sendMessage($Tmsg, $restoreEnable);
                            sendMessage($dataTmsg, $restoreEnable);
                            echo "\n\t\t\t".$Tmsg."\n\n";
                        }

                        // Add +1 to counter
                        $query = "UPDATE $table SET counter=($counter + 1), time=time()";
                        $db->exec($query) or die("\n\t\t\tUnable to plus the rebuild counter!");
                    }
                }

            } else {

                if (($counter % 2) !== 0) {
                    $Tmsg = "*".gethostname()."*: node is syncing.\n\t\t\tAll we need to do is wait... *(~‾▿‾)~*";
                    echo "\t\t\t".$Tmsg."\n\n";
                    sendMessage($Tmsg, $restoreEnable);
                    sendMessage($dataTmsg, $restoreEnable);
                }

                // Add +1 to counter
                $query = "UPDATE $table SET counter=($counter + 1), time=time()";
                $db->exec($query) or die("\n\t\t\tUnable to plus the rebuild counter!");
            }

        } else {
            // Local node is probably stuck
            $Tmsg = "*".gethostname()."*: height threshold is reached and not syncing.\n\t\t\tGoing to restore from snapshot.";
            echo "\t\t\t".$Tmsg."\n\n";
            sendMessage($Tmsg, $restoreEnable);
            sendMessage($dataTmsg, $restoreEnable);

            echo "\t\t\tRestore from the last snapshot: \n";
            system("cd $pathtoapp && ./shift_manager.bash stop");
            sleep(3);
            $restored = system("cd $snapshotDir && echo y | ./shift-snapshot.sh restore");
            system("cd $pathtoapp && ./shift_manager.bash reload");

            echo "\n\n\t\t\tRestored: $restored";

            if ($restored == "OK snapshot restored successfully.") {
                echo "\n\t\t\tRestored: true\n\n";
                // Set counter for next good message
                $query = "UPDATE $table SET counter=($counter + 1), time=time()";
                $db->exec($query) or die("\n\t\t\tUnable to plus the rebuild counter!");
                // Set pause for waiting node online
                echo "\t\t\tPause: 20 sec. for start node sync.\n\n";
                sleep(20);

            } else {
                echo "\n\t\t\tRestored: NO!\n\n";
                // Going to rebuild
                $Tmsg = "*".gethostname()."*: Error! Going to rebuild with shift-manager.";
                sendMessage($Tmsg, $restoreEnable);
                echo "\n\t\t\t".$Tmsg."\n\n";

                system("cd $pathtoapp && ./shift_manager.bash rebuild");
                // Set counter for next good message
                $query = "UPDATE $table SET counter=($counter + 1), time=time()";
                $db->exec($query) or die("\n\t\t\tUnable to plus the rebuild counter!");
                echo "\t\t\tPause: 120 sec.\n\n";
                sleep(120);

            }
        }

    } else {
        echo "\t\t\tHeight on this node is fine.\n\n";

        // If we have bad messsages send a Good one
        if ($counter > 0) {
            $Tmsg = "*".gethostname()."*: height is fine now.";
            sendMessage($Tmsg, $restoreEnable);

        }

        // Lets reset counting in database
        $query = "UPDATE $table SET counter='0', time=time()";
        $db->exec($query) or die("\n\t\t\tUnable to set counter to 0!");
    }
    
} else {
    // Syncing is disabled in config.php
    echo "disabled\n\n";
}

// Test messages here
//echo "\t\t\t".$dataTmsg."\n\n";
//sendMessage($dataTmsg, $restoreEnable);

// Message all
//$Tmsg = gethostname().": All is okay. Im working!";
//echo "\t\t\t".$Tmsg."\n\n";
//sendMessage($Tmsg);
