<?php

/*  CHECKING IF THE SETTINGS ARE CORRECT
_________________________________________ */


require(dirname(__FILE__).'/config.php');
require(dirname(__FILE__).'/includes/functions.php');
require(dirname(__FILE__).'/includes/init.php');

echo "\t\tTEST STARTED\n\n";

$endColor = "\033[0m";
$green = "\e[32m";

$shiftDir = $pathtoapp;
$localRole = (($thisMain) ? "MAIN" : "BACKUP");
$localNetwork = ((strpos($localNode, "9305")) ? "MAINNET" : "TESTNET");
$localAPI = ((ping($localNode)) ? "AVAIBLE" : "INACCESSIBLE");
$localForgingAPI = ((checkForging($localNode, $public) == "true" || (checkForging($localNode, $public) == "false")) ? "AVAIBLE" : (((checkForging($localNode, $public) == "error") ? "INACCESSIBLE" : "NO SECRET")));
$shiftDirNetwork = ((strpos(tailCustom($shiftDir."config.json", 500), "9305")) ? "MAINNET" : ((strpos(tailCustom($shiftDir."config.json", 500), "9405")) ? "TESTNET" : "NO SHIFT-LISK INSTALLATION"));
['height' => $localHeight] = getNodeAPIData($localNode);

if ($recoveryEnabled || $createSnapshots || $switchingEnabled) {
    $trustedNode = $trustedNode; // add check
    ['height' => $trustedHeight] = getNodeAPIData($trustedNode);
}

if ($switchingEnabled) {
    $remoteNode = (($switchingEnabled) ? $remoteNode : "SWITCHING DISABLED");
    $remoteNetwork = (($remoteNode == "DISABLED") ? "DISABLED" : ((strpos($remoteNode, "9305") ? "MAINNET" : ((strpos($remoteNode, "9405") ? "TESTNET" : "NO PORT")))));
    $remoteAPI = ((ping($remoteNode)) ? "AVAIBLE" : "INACCESSIBLE");
    $remoteForgingAPI = ((checkForging($remoteNode, $public) == "true" || (checkForging($remoteNode, $public) == "false")) ? "AVAIBLE" : (((checkForging($remoteNode, $public) == "error") ? "INACCESSIBLE" : "NO SECRET")));
    ['height' => $remoteHeight] = getNodeAPIData($remoteNode);
}


if ($switchingEnabled) {
    echo "This node is: \t\t $localRole \n";
    echo "Remote node: \t\t $remoteNode \n";
    echo "\n";
    echo "shift-lisk directory: \t $shiftDir \n";
    echo "\n";
    echo "Directory network: \t $shiftDirNetwork \n";  // must be same as local and remote network
    echo "Local node network: \t $localNetwork \n"; // must be same as directory and remote network
    echo "Remote node network: \t $remoteNetwork \n"; // must be same as local and directory network
    echo "\n";
    echo "Local node API: \t $localAPI \n"; // must be avaible
    echo "Remote node API: \t $remoteAPI \n"; // must be avaible
    echo "\n";
    echo "Local forging API: \t $localForgingAPI \n"; // must be avaible
    echo "Remote forging API: \t $remoteForgingAPI \n"; // must be avaible
    echo "\n";
    echo "Trusted node: \t\t $trustedNode \n";
    echo "\n";
    echo "Trusted node height: \t $trustedHeight \n"; // must be same as remote and local
    echo "Local node height: \t $localHeight \n"; // must be same as trusted and remote
    echo "Remote node height: \t $remoteHeight \n"; // must be same as trusted and local
}

if (!$switchingEnabled) {
    echo "This node is: \t\t $localRole \n"; // must be MAIN
    echo "\n";
    echo "shift-lisk directory: \t $shiftDir \n";
    echo "\n";
    echo "Directory network: \t $shiftDirNetwork \n"; // must be same as local network
    echo "Local node network: \t $localNetwork \n";
    echo "\n";
    echo "Local node API: \t $localAPI \n"; // must be avaible
    echo "Local forging API: \t $localForgingAPI \n"; // must be avaible
    echo (($recoveryEnabled || $createSnapshots) ? "\nTrusted node: \t\t $trustedNode \n" : "");
    echo "\n";
    echo (($recoveryEnabled || $createSnapshots) ? "Trusted node height: \t $trustedHeight \n" : ""); // must be same as local
    echo "Local node height: \t $localHeight \n"; // must be same as trusted
}

?>