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
$localRole = (($thisMain) ? "MAIN" : "BACKUP");
$remoteNode = (($switchingEnabled) ? $remoteNode : "SWITCHING DISABLED");
$localNetwork = ((strpos($localNode, "9305")) ? "MAINNET" : "TESTNET");
$remoteNetwork = (($remoteNode == "DISABLED") ? "DISABLED" : ((strpos($remoteNode, "9305") ? "MAINNET" : ((strpos($remoteNode, "9405") ? "TESTNET" : "NO PORT")))));
$localAPI = ((ping($localNode)) ? "AVAIBLE" : "INACCESSIBLE");
$remoteAPI = ((ping($remoteNode)) ? "AVAIBLE" : "INACCESSIBLE");
$localForgingAPI = ((checkForging($localNode, $public) == "true" || (checkForging($localNode, $public) == "false")) ? "AVAIBLE" : (((checkForging($localNode, $public) == "error") ? "INACCESSIBLE" : "NO SECRET")));
$remoteForgingAPI = ((checkForging($remoteNode, $public) == "true" || (checkForging($remoteNode, $public) == "false")) ? "AVAIBLE" : (((checkForging($remoteNode, $public) == "error") ? "INACCESSIBLE" : "NO SECRET")));
$trustedNode = (($recoveryEnabled) ? $trustedNode : "REECOVERY DISABLED");

echo "shift-lisk directory: \t $shiftDir \n";
echo "\n";
echo "This node is: \t\t $localRole \n";
echo "Remote node: \t\t $remoteNode \n";
echo "\n";
echo "This node network: \t $localNetwork \n";
echo "Remote node network: \t $remoteNetwork \n";
echo "\n";
echo "This node API: \t\t $localAPI \n";
echo "Remote node API: \t $remoteAPI \n";
echo "\n";
echo "This forging API: \t $localForgingAPI \n";
echo "Remote forging API: \t $remoteForgingAPI \n";
echo "\n";
echo "Trusted node: \t\t $trustedNode \n";
?>