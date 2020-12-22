<?php

// COMMAND LINE OPTIONS

if ($argc > 1) {

    for ($i = 1; $i < $argc; $i++) {
        
        switch ($argv[$i]) {
            
            case "stop":
                waitDoAndExit("stop", 0, true);
                break;

            case "start":
                waitDoAndExit("start");
                break;

            case "reload":
                waitDoAndExit("reload");
                break;

            case "update":
                waitDoAndExit("update");
                break;

            case "update_manager":
                waitDoAndExit("update_manager");
                break;

            case "update_client":
                waitDoAndExit("update_client");
                break;

            case "update_wallet":
                waitDoAndExit("update_wallet");
                break;

            case "rebuild":
                waitDoAndExit("rebuild", 120);
                break;

            case "status":
                waitDoAndExit("status");
                break;

            case "create":
                waitDoAndExit("create");
                break;

            case "restore":
                waitDoAndExit("restore", 120);
                break;

            default:
                if (substr($argv[$i], 1, 1) == '-') {
                    echo "Unknown option: {$argv[$i]}\n";
                }
                
                break;
        }
    }
}

// if shift-lisk was stopped manually, do not continue the script
if ($db_data["manual_stop"]) {
    exit("shift-lisk stopped manually to run it again please use 'php run.php start'\n");
}