<?php

/*  USER CONFIG
__________________________ */


// Local node settings
    $nodeName           = "Your Node Name"." on ".gethostname();    // Name of this node delegate for Telegram messages
    $localNode          = "http://127.0.0.1:9305";                  // Local node IP address. Use http://127.0.0.1:PORT

// Сluster settings
    $switchingEnabled   = false;                      // Set true for enable switching betwin your main and backup nodes. Be sure to add a remote node address
    $thisMain           = true;                       // 'true' if this local node is your main node, 'false' if it's your backup node
    $remoteNode         = "http://NODE_IP:9305";      // Remote node IP address. Use node IP address:port
    $secret             = "passphrase";               // Your twelve word passphrase is placed here. Required for consensus check

// Recovery settings
    $recoveryEnabled    = true;                             // Set 'true' to repair your node on fail-down with snapshot or shift_manager
    $createSnapshots    = true;                             // Do you want to create snapshots?
    $maxSnapshots       = 1;                                // How many snapshots to preserve? (in days)
    $trustedNode        = "https://wallet.shiftnrg.org";    // Used for checking blockchain height. Replace it to mainnet or testnet trusted node

// Telegram Bot
    $recoveryMessages   = false;                   // Change it to true if you want to recieve messagese of your recovery status
    $telegramId         = "here";                  // Your Telegram ID
    $telegramApiKey     = "here";                  // Your Telegram API key
    $debugMessages      = false;                   // Change it to false to disable all messages exept recovery messages from Telegram bot


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
