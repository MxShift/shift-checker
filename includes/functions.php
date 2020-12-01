<?php

// PING function..
function ping($host, $port=80, $timeout=3)
{
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
    ob_start();
    $check_public = passthru("curl -s --connect-timeout 10 -d 'secret=$secret' $server/api/accounts/open");
    $check_public = ob_get_contents();
    ob_end_clean();

    // If status is not OK...
    if (strpos($check_public, "success") === false) {
        return "error";
    } else {
        $check = json_decode($check_public, true);
        return $check['account']['publicKey'];
    }
}

// Check forging
function checkForging($server, $publicKey)
{
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
function sendMessage($message, $sync=false)
{
    global $telegramAll, $SyncingMessage, $telegramApiKey, $telegramId;
    if ($telegramAll === true && $sync === false) {
        $telegramUrl = "https://api.telegram.org/bot".($telegramApiKey)."/sendMessage";
        passthru("curl -s -d 'chat_id=$telegramId&parse_mode=Markdown&text=$message' $telegramUrl >/dev/null");
    }

    if ($SyncingMessage === true && $sync === true && $telegramAll === true) {
        $telegramUrl = "https://api.telegram.org/bot".($telegramApiKey)."/sendMessage";
        passthru("curl -s -d 'chat_id=$telegramId&parse_mode=Markdown&text=$message' $telegramUrl >/dev/null");
    }

    if ($SyncingMessage === true && $sync === true && $telegramAll === false) {
        $telegramUrl = "https://api.telegram.org/bot".($telegramApiKey)."/sendMessage";
        passthru("curl -s -d 'chat_id=$telegramId&parse_mode=Markdown&text=$message' $telegramUrl >/dev/null");
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
    echo "\t\t\tPause: $seconds sec.\n\n";
    sleep($seconds);

}

// Shift manager
function shiftManager($command)
{
    global $pathtoapp;
    system("cd $pathtoapp && bash shift_manager.bash $command");

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
    echo "\t\t\tHeight Explorer: $blockchain\n\n";

    echo "\t\t\tConsensus Main: " . $consensusMain . "%";
    echo "\t\tConsensus Backup: " . $consensusBackup . "%\n";

    echo "\t\t\tHeight Main: $heightMain";
    echo "\t\tHeight Backup: $heightBackup \n";

    echo "\t\t\tSyncing Main: " . json_encode($syncingMain); // Boolean to string
    echo "\t\t\tSyncing Backup: " . json_encode($syncingBackup) . "\n";

    echo "\t\t\tForging Main: " . $forgingMain;
    echo ("\t\t\tForging Backup: " . $forgingBackup . "\n\n");

}



