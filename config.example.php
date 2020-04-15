<?php
	/**
     * @version 2.1.2
     * @author Mx
     * @link https://github.com/MxShift/shift-checker
     * @license https://github.com/MxShift/shift-checker/blob/master/LICENSE
     * @version 2.0
     * @author Jan
     * @link https://github.com/lepetitjan/shift-checker
     * @license https://github.com/lepetitjan/shift-checker/blob/master/LICENSE
     */

/*  USER CONFIG
__________________________ */

    $nodeName       = "Your Node Name".gethostname();    // Name of this node delegate for Telegram messages.

// Consensus settings
    $consensusEnable= true;                                	// Enable consensus check? Be sure to check $nodes first..
    $master         = false;                                 // Is this your master node? true/false
    $masternode     = "http://NODE_IP";                   // Master node. Use node IP address or http://127.0.0.1 if it's a local node
    $masterport     = 9405;                                 // Master port. 
    $slavenode      = "http://NODE_IP";      			// Slave node. Use node IP address or http://127.0.0.1 if it's a local node
    $slaveport      = 9405;                                 // Slave port
    $secret         = "your twelve word passphrase is placed here"; 					// Required for consensus check

// Recovery settings
    $restoreEnable  = true; 
	$apiHost        = "http://127.0.0.1:9405";		// Used to calculate $publicKey by $secret for consensus check and to check syncing. Use http://127.0.0.1:netPort    
    $explorer	    = "https://testnet.shiftnrg.com.mx"; 	// Used to check blockchain height

// Snapshot settings
    $createsnapshot	= true;					// Do you want to create snapshots with shift-snapshot?
    $max_snapshots	= 2;					// How many snapshots to preserve? (in days)

// Telegram Bot
    $telegramAll	= false;				// Change to false to disable all messages exept recovery messages from Telegram bot
    $SyncingMessage = true;					// Change to true if you want recieve messagese of your recovery status
    $telegramId 	= "between_double_quotation_marks"; 					// Your Telegram ID
    $telegramApiKey = "between_double_quotation_marks"; 					// Your Telegram API key 


/*  GENERAL CONFIG
__________________________ */

// You should have installed Shift-Checker as normal user, so the line below should work by default.
// However, if you installed as root (please don't..) change the path below to $homeDir = "/root/";
    $homeDir        = "/home/".get_current_user()."/";

// You may leave the settings below as they are

	$date		= date("Y-m-d H:i:s");			// Current date
	$pathtoapp	= $homeDir."shift/";			// Full path to your shift installation	
	$baseDir	= dirname(__FILE__)."/";		// Folder which contains THIS file
	$lockfile	= $baseDir."checkdelegate.lock";	// Name of our lock file
    $database	= $baseDir."db.json";	// Database name to use
	$msg 		= "\"cause\":3";			// Message that is printed when forked
	$shiftlog 	= $pathtoapp."logs/shift.log";		// Needs to be a FULL path, so not ~/shift
	$linestoread	= 30;					// How many lines to read from the end of $shiftlog
	$max_count 	= 3;					// How may times $msg may occur
	$okayMsg 	= "√";					// 'Okay' message from shift_manager.bash

// Consensus settings
	$threshold      = 20;                                   // Percentage of consensus threshold

// Syncing settings
	$restoredMsg 	= "OK snapshot restored successfully.";	// 'Okay' message from shift-snapshot	 	
	$snapThreshold  = 3200;                 		// Threshold in blocks. Use 3200 for daily snapshots and 133 for hourly

// Snapshot settings
	$snapshotDir	= $homeDir."shift-snapshot/";		// Base folder of shift-snapshot

// Log file rotation
	$logfile 	= $baseDir."logs/checkdelegate.log";	// The location of your log file (see section crontab on Github)
	$max_logfiles	= 3;					// How many log files to preserve? (in days)  
	$logsize 	= 5242880;				// Max file size, default is 5 MB

?>
