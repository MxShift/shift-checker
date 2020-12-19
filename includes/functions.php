<?php

// PING function..
function ping($url, $timeout=3)
{
    // check for an extra slash
    $url = rtrim($url, '/');

    $find = array("http://", "https://");
    $node = explode(":", str_replace($find, "", $url));

    // if no port in a url
    if (count($node) === 1) {
        ob_start();
        $output = passthru("curl -Is $url | grep HTTP | cut -d ' ' -f2");
        $output = mb_substr(trim(ob_get_contents()), 0, 3); // getting a HTTP status code
        ob_end_clean();
    
        if ($output == "200") {
            return true;
        } else {
            return false;
        }
    }

    // if url has a port
    $host = $node[0];
    $port = $node[1];

    $fsock = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!is_resource($fsock)) {
        return false;
    } else {
        return true;
    }
}

    
// Tail function
function tailCustom($filepath, $lines = 1, $adaptive = true)
{

    // Current date
    $date = date("Y-m-d H:i:s");

    // Open file
    $f = @fopen($filepath, "rb");
    //if ($f === false) return false;
    if ($f === false) {
        return "\t\t\tUnable to open file!\n";
    }

    // Sets buffer size, according to the number of lines to retrieve.
    // This gives a performance boost when reading a few lines from the file.
    if (!$adaptive) {
        $buffer = 4096;
    } else {
        $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
    }

    // Jump to last character
    fseek($f, -1, SEEK_END);

    // Read it and adjust line number if necessary
    // (Otherwise the result would be wrong if file doesn't end with a blank line)
    if (fread($f, 1) != "\n") {
        $lines -= 1;
    }

    // Start reading
    $output = '';
    $chunk = '';

    // While we would like more
    while (ftell($f) > 0 && $lines >= 0) {

        // Figure out how far back we should jump
        $seek = min(ftell($f), $buffer);

        // Do the jump (backwards, relative to where we are)
        fseek($f, -$seek, SEEK_CUR);

        // Read a chunk and prepend it to our output
        $output = ($chunk = fread($f, $seek)) . $output;

        // Jump back to where we started reading
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

        // Decrease our line counter
        $lines -= substr_count($chunk, "\n");
    }

    // While we have too many lines
    // (Because of buffer size we might have read too many)
    while ($lines++ < 0) {

        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);
    }

    // Close file and return
    fclose($f);
    return trim($output);
}

// Log rotation function
function rotateLog($logfile, $max_logfiles=3, $logsize=10485760)
{

    // Current date
    $date = date("Y-m-d H:i:s");
    
    if (file_exists($logfile)) {
        
        // Check if log file is bigger than $logsize
        if (filesize($logfile) >= $logsize) {
            echo $date." - [ LOGFILES ] Log file exceeds size: $logsize. Let me rotate that for you...\n";
            system("gzip -c $logfile > $logfile.".time().".gz && rm $logfile", $rotate);
            if ($rotate == 0) {
                echo "\t\t\tLog file rotated.\n";

                echo "\t\t\tCleaning up old log files...\n";
                $logfiles = glob($logfile."*");
                foreach ($logfiles as $file) {
                    if (is_file($file)) {
                        if (time() - filemtime($file) >= 60 * 60 * 24 * $max_logfiles) {
                            if (unlink($file)) {
                                echo "\t\t\tDeleted log file $file\n";
                            }
                        }
                    }
                }
            } else {
                echo "Rotate failed code: $rotate \n";
            }
        } else {
            echo "\t\t\tLog size has not reached the limit yet: (".filesize($logfile)."/$logsize)\n";
        }
    } else {
        echo "\t\t\tCannot find a log file to rotate..\n";
    }
}

// Check publicKey
function checkPublic($server, $secret)
{
    // check for an extra slash
    $server = rtrim($server, '/');
    
    ob_start();
    $check_public = passthru("curl -s --connect-timeout 10 -d 'secret=$secret' $server/api/accounts/open");
    $check_public = ob_get_contents();
    ob_end_clean();

    // If status is not OK...
    if (strpos($check_public, "success") === false) {
        return "error";
    } else {
        $check = json_decode($check_public, true);

        if ($check["success"] === false) {
            return "bad secret";
        } else {
            return $check["account"]["publicKey"];
        }
    }
}

// Check forging
function checkForging($server, $publicKey)
{
    // check if secret passphrase is added to config
    global $secret;
    $sec_array = explode(" ", $secret);

    if (count($sec_array) < 12) {
        return "secret not set in config!";
    }

    // check for an extra slash
    $server = rtrim($server, '/');

    ob_start();
    $check_forging = passthru("curl -s --connect-timeout 10 -XGET $server/api/delegates/forging/status?publicKey=$publicKey");
    $check_forging = ob_get_contents();
    ob_end_clean();
    $check = json_decode($check_forging, true);

    // If status is not OK...
    if ($check === null || isset($check['enabled']) === false) {
        return "error";
    } else {
        if ($check['enabled']) {
            return "true";
        } else {
            return "false";
        }
    }
}

// Disable forging
function disableForging($server, $secret)
{
    // check for an extra slash
    $server = rtrim($server, '/');

    ob_start();
    $check_status = passthru("curl -s --connect-timeout 10 -d 'secret=$secret' $server/api/delegates/forging/disable");
    $check_output = ob_get_contents();
    ob_end_clean();

    // If status is not OK...
    if (strpos($check_output, "success") === false) {
        return "error";
    } else {
        return "disabled";
    }
}

// Enable forging
function enableForging($server, $secret)
{
    // check for an extra slash
    $server = rtrim($server, '/');

    ob_start();
    $check_status = passthru("curl -s --connect-timeout 10 -d 'secret=$secret' $server/api/delegates/forging/enable > /dev/null");
    $check_output = ob_get_contents();
    ob_end_clean();

    // If status is not OK...
    if (strpos($check_output, "success") === false) {
        return "error";
    } else {
        return "enabled";
    }
}

// Send Telegram message
function sendMessage($message, $force=false)
{
    global $debugMessages, $recoveryMessages, $telegramApiKey, $telegramId;

    // if ($debugMessages || $recoveryMessages || $force) {

    //     $telegramUrl = "https://api.telegram.org/bot".($telegramApiKey)."/sendMessage";
    //     return exec("curl -s -d 'chat_id=$telegramId&parse_mode=Markdown&text=$message' $telegramUrl");
    // }

    if ($debugMessages === true && $force === false) {
        $telegramUrl = "https://api.telegram.org/bot".($telegramApiKey)."/sendMessage";
        return exec("curl -s -d 'chat_id=$telegramId&parse_mode=Markdown&text=$message' $telegramUrl");
    }

    if ($recoveryMessages === true && $force === true && $debugMessages === true) {
        $telegramUrl = "https://api.telegram.org/bot".($telegramApiKey)."/sendMessage";
        return exec("curl -s -d 'chat_id=$telegramId&parse_mode=Markdown&text=$message' $telegramUrl");
    }

    if ($recoveryMessages === true && $force === true && $debugMessages === false) {
        $telegramUrl = "https://api.telegram.org/bot".($telegramApiKey)."/sendMessage";
        return exec("curl -s -d 'chat_id=$telegramId&parse_mode=Markdown&text=$message' $telegramUrl");
    }
}

// Save an array to JSON file
function saveToJSONFile($arr, $file_path)
{
    $fh = fopen($file_path, 'w')
    or die("Error opening ".$file_path." file");
    fwrite($fh, json_encode($arr, JSON_UNESCAPED_UNICODE));
    fclose($fh);

}

// Pause
function pauseToWaitNodeAPI($seconds)
{
    // Pause to wait for start node sync. Use 120
    echo "\n\t\t\tPause: $seconds sec.\n\n";
    sleep($seconds);

}

// Shift manager
function shiftManager($command)
{
    global $pathtoapp;

    if ($command == "rebuild") {

        system("cd $pathtoapp && echo y | bash shift_manager.bash $command");

    } else if ($command == "status_output") {
        // Use PHP's ob_ function to create an output buffer
        ob_start();
        passthru("cd $pathtoapp && bash shift_manager.bash status | cut -z -b1-3");
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    } else {
        system("cd $pathtoapp && bash shift_manager.bash $command");
    }
}

// Shift snapshot
function shiftSnapshot($command)
{
    global $pathtoapp, $snapshotDir;

    if ($command === "create") {
        ob_start();
        passthru("cd $snapshotDir && SHIFT_DIRECTORY=\"$pathtoapp\" bash snap.sh $command");
        $output = ob_get_contents();
        ob_end_clean();

        $output_array = explode(" ", $output);

        $blockHeight = $output_array[14];
        $fileSize = $output_array[15];

        return ['output' => $output, 'size' => $fileSize, 'height' => $blockHeight];
    }

    $output = system ("cd $snapshotDir && SHIFT_DIRECTORY=\"$pathtoapp\" bash snap.sh $command");

    return $output;
}

// Shift snapshot name
function snapshotPath($date)
{
    global $snapshotDir;

    $path = glob($snapshotDir.'shift_db_'.$date.'*.sql.gz');

    return $path;
}

// Shift remove old snapshot files
function removeOldSnapshots()
{
    global $snapshotDir, $maxSnapshots;

    $files = glob($snapshotDir.'shift_db_*.sql.gz');
            
    foreach ($files as $file) {
        if (is_file($file)) {
            if (time() - filemtime($file) >= 60 * 60 * 24 * $maxSnapshots) {
                if (unlink($file)) {
                    echo "\t\t\tDeleted snapshot $file\n";
                }
            }
        }
    }
}


// Check height, consensus and syncing on Main node
function getNodeAPIData($node)
{
    // check for an extra slash
    $node = rtrim($node, '/');

    $statusNode = @file_get_contents($node."/api/loader/status/sync");

    if ($statusNode === false) {
        $heightNode = 0;
        $consensusNode = 0;
        $syncingNode = false;
    } else {
        $statusNode = json_decode($statusNode, true);
    
        if (isset($statusNode['height']) === false) {
            $heightNode = "error";
        } else {
            $heightNode = $statusNode['height'];
        }
    
        $syncingNode = $statusNode['syncing'];
        $consensusNode = $statusNode['consensus'];
    }

    return ['height' => $heightNode, 'consensus' => $consensusNode, 'syncing' => $syncingNode];
    // return array($heightNode, $consensusNode, $syncingNode);

}

// echo with node data
function printNodeData($node, $blockchain, $height, $consensus, $syncing, $forging)
{
    echo "\t\t\tHeight Blockchain: $blockchain\n\n";
    echo "\t\t\tHeight $node: $height\n";
    echo "\t\t\tConsensus $node: ".$consensus."%\n";
    echo "\t\t\tSyncing $node: ".json_encode($syncing)."\n"; // Boolean to string
    echo "\t\t\tForging $node: $forging\n";
}

// echo with nodes data
function printTwoNodesData(
    $blockchain, $heightMain, $heightBackup, 
    $consensusMain, $consensusBackup,  $syncingMain, $syncingBackup,
    $forgingMain, $forgingBackup
    )
{
    echo "\t\t\tHeight Blockchain: $blockchain\n\n";

    echo "\t\t\tHeight Main: $heightMain";
    echo "\t\tHeight Backup: $heightBackup \n";

    echo "\t\t\tConsensus Main: " . $consensusMain . "%";
    echo "\t\tConsensus Backup: " . $consensusBackup . "%\n";

    echo "\t\t\tSyncing Main: " . json_encode($syncingMain); // Boolean to string
    echo "\t\t\tSyncing Backup: " . json_encode($syncingBackup) . "\n";

    echo "\t\t\tForging Main: " . $forgingMain;
    echo "\t\t\tForging Backup: " . $forgingBackup . "\n\n";
}


// Remove lock file
function releaseScript()
{
    global $lockfile;
    
    $lf_removed = unlink($lockfile);

    if (!$lf_removed) {
        echo "[ LOCKFILE ] Unable to remove lock file!\n";
    }
}

// Create lock file
function lockScript()
{
    global $lockfile;
    
    $lf_created = touch($lockfile);

    if (!$lf_created) {
        exit("[ LOCKFILE ] Error touching $lockfile\n");
    }
}

function waitDoAndExit($command, $pause=20, $stop=false) {

    global $lockfile;

    $wait = true;

    if (file_exists($lockfile)) {
        echo "Waiting for the end of a previous script launch\n";
    }

    do {
        if (!file_exists($lockfile)) {
            echo "\n";
            doAndExit($command, $pause, $stop);
            $wait = false;
        }
        echo "~";
        // echo "\033[0G";
        sleep(1);
    } while ($wait);
}


function doAndExit($command, $pause, $stop)
{
    global $db_data, $database, $bold, $endStyle, $red, $green;

    lockScript();
    
    if ($command == "update") {

        shiftManager("update_manager");
        shiftManager("update_client");
        shiftManager("update_wallet");

    } else if ($command == "status") {

        shiftManager("status");
        echo $bold."shift-checker manually stopped:".$endStyle." " . (($db_data["manual_stop"]) ? $red."true".$endStyle : $green."false".$endStyle) . "\n";
        releaseScript();
        exit();

    } else {

        shiftManager($command);
    }
    
    $db_data["manual_stop"] = $stop;
    saveToJSONFile($db_data, $database);
    pauseToWaitNodeAPI($pause);
    releaseScript();
    exit();
}