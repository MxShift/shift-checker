<?php

/*  USER CONFIG
__________________________ */

    $nodeName           = "Your Node Name"." on ".gethostname();    // Name of this node delegate for Telegram messages.

// Consensus settings
    $consensusEnable    = true;                       // Set true for enable consensus check. Be sure to add nodes addresses first
    $main               = true;                       // 'true' if this is your main node, 'false' if it's your backup node
    $localNode          = "http://127.0.0.1:9305";    // Local node IP address. Use http://127.0.0.1:PORT
    $remoteNode         = "http://NODE_IP:9305";      // Remote node IP address. Use node IP address:port
    $secret             = "passphrase";               // Your twelve word passphrase is placed here. Required for consensus check

// Recovery settings
    $recoveryEnabled    = true;
    $trustedNode        = "https://wallet.shiftnrg.org";          // Used for checking blockchain height. Replace it to mainnet or testnet trusted node

// Snapshot settings
    $createsnapshot     = true;                    // Do you want to create snapshots with shift-snapshot?
    $max_snapshots      = 1;                       // How many snapshots to preserve? (in days)

// Telegram Bot
    $telegramAll        = false;                   // Change it to false to disable all messages exept recovery messages from Telegram bot
    $SyncingMessage     = true;                    // Change it to true if you want to recieve messagese of your recovery status
    $telegramId         = "here";                  // Your Telegram ID
    $telegramApiKey     = "here";                  // Your Telegram API key


/*  GENERAL CONFIG
__________________________ */

// You may leave the settings below as they are

// You should have installed shift-checker as a normal user, so the line below should work by default.
// However, if you installed as root (please don't..) change the path below to $homeDir = "/root/";
    $homeDir            = "/home/".get_current_user()."/";
    $pathtoapp          = $homeDir."shift-lisk/";             // Full path to your shift installation    
    $baseDir            = dirname(__FILE__)."/";              // Folder which contains THIS file
    $date               = date("Y-m-d H:i:s");                // Current date

?>
