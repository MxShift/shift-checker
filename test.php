<?php

/*  CHECKING IF THE SETTINGS ARE CORRECT
_________________________________________ */


require(dirname(__FILE__).'/config.php');
require(dirname(__FILE__).'/includes/functions.php');
require(dirname(__FILE__).'/includes/init.php');

echo "TEST STARTED\n\n";

$endColor = "\033[0m";
$green = "\e[32m";

$shiftDir = $pathtoapp;
// $shiftDir = $homeDir."shift-lisk/";
$localRole = (($thisMain) ? "MAIN" : "BACKUP");
$remoteNode = (($switchingEnabled) ? $remoteNode : "SWITCHING DISABLED");
$localNetwork = ((strpos($localNode, "9305")) ? "MAINNET" : "TESTNET");
$remoteNetwork = (($remoteNode == "DISABLED") ? "DISABLED" : ((strpos($remoteNode, "9305") ? "MAINNET" : ((strpos($remoteNode, "9405") ? "TESTNET" : "NO PORT")))));
$localAPI = ((ping($localNode)) ? "AVAIBLE" : "INACCESSIBLE");
$remoteAPI = ((ping($remoteNode)) ? "AVAIBLE" : "INACCESSIBLE");
$localForgingAPI = ((checkForging($localNode, $public) == "true" || (checkForging($localNode, $public) == "false")) ? "AVAIBLE" : (((checkForging($localNode, $public) == "error") ? "INACCESSIBLE" : "NO SECRET")));
$remoteForgingAPI = ((checkForging($remoteNode, $public) == "true" || (checkForging($remoteNode, $public) == "false")) ? "AVAIBLE" : (((checkForging($remoteNode, $public) == "error") ? "INACCESSIBLE" : "NO SECRET")));
$trustedNode = (($recoveryEnabled) ? $trustedNode : "REECOVERY DISABLED");
$shiftDirNetwork = ((strpos(tailCustom($shiftDir."config.json", 500), "9305")) ? "MAINNET" : ((strpos(tailCustom($shiftDir."config.json", 500), "9405")) ? "TESTNET" : "NO SHIFT-LISK INSTALLATION"));


echo "This node is: \t\t $localRole \n";
echo "Remote node: \t\t $remoteNode \n";
echo "\n";
echo "shift-lisk directory: \t $shiftDir \n";
echo "\n";
echo "Directory network: \t $shiftDirNetwork \n";
echo "Local node network: \t $localNetwork \n";
echo "Remote node network: \t $remoteNetwork \n";
echo "\n";
echo "Local node API: \t $localAPI \n";
echo "Remote node API: \t $remoteAPI \n";
echo "\n";
echo "Local forging API: \t $localForgingAPI \n";
echo "Remote forging API: \t $remoteForgingAPI \n";
echo "\n";
echo "Trusted node: \t\t $trustedNode \n";

?>