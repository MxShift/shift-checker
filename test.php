<?php

/*  CHECKING IF THE SETTINGS ARE CORRECT
_________________________________________ */


require(dirname(__FILE__).'/config.php');
require(dirname(__FILE__).'/includes/functions.php');
require(dirname(__FILE__).'/includes/init.php');

$endStyle = "\033[0m";
$green = "\e[32m";
$red = "\e[31m";
$bold = "\e[1m";
$uline = "\e[4m";
$dim = "\e[2m";


echo "\t\t$uline$bold TEST STARTED $endStyle\n\n";

// user input
$stdin = fopen("php://stdin", "r");

echo "Is this your ".$uline."Main node".$endStyle." (".$bold."m".$endStyle.") or your ".$uline."Backup node".$endStyle." (".$bold."b".$endStyle.")? ".$dim."(m/b)".$endStyle.": ";
$inputRole = trim(fgets($stdin));

echo "".$uline."Mainnet".$endStyle." (".$bold."m".$endStyle.") or ".$uline."Testnet".$endStyle." (".$bold."t".$endStyle.")? ".$dim."(m/t)".$endStyle.": ";
$inputNetwork = trim(fgets($stdin));

fclose($stdin);

$inputRole = (($inputRole == "m") ? "MAIN   " : "BACKUP");
$inputNetwork = (($inputNetwork == "m") ? "MAINNET" : "TESTNET");

$shiftDir = $pathtoapp;
$localRole = (($thisMain) ? "MAIN   " : "BACKUP");
$localNetwork = ((strpos($localNode, "9305")) ? "MAINNET" : "TESTNET");
$localAPI = ((ping($localNode)) ? "AVAIBLE" : "INACCESSIBLE");
$localForgingAPI = ((checkForging($localNode, $public) == "true" || (checkForging($localNode, $public) == "false")) ? "AVAIBLE" : (((checkForging($localNode, $public) == "error") ? "INACCESSIBLE" : "NO SECRET")));
$shiftDirNetwork = ((strpos(tailCustom($shiftDir."config.json", 500), "9305")) ? "MAINNET" : ((strpos(tailCustom($shiftDir."config.json", 500), "9405")) ? "TESTNET" : "NO SHIFT-LISK INSTALLATION"));
['height' => $localHeight] = getNodeAPIData($localNode);

if ($recoveryEnabled || $createSnapshots || $switchingEnabled) {

    $trustedNode = $trustedNode; // add check
    ['height' => $trustedHeight] = getNodeAPIData($trustedNode);
    // $trustedAPI = ((ping($trustedNode)) ? "AVAIBLE" : "INACCESSIBLE");
    $trustedAPI = "AVAIBLE";
}

if ($switchingEnabled) {

    $remoteNode = (($switchingEnabled) ? $remoteNode : "SWITCHING DISABLED");
    $remoteNetwork = (($remoteNode == "DISABLED") ? "DISABLED" : ((strpos($remoteNode, "9305") ? "MAINNET" : ((strpos($remoteNode, "9405") ? "TESTNET" : "NO PORT")))));
    $remoteAPI = ((ping($remoteNode)) ? "AVAIBLE" : "INACCESSIBLE");
    $remoteForgingAPI = ((checkForging($remoteNode, $public) == "true" || (checkForging($remoteNode, $public) == "false")) ? "AVAIBLE" : (((checkForging($remoteNode, $public) == "error") ? "INACCESSIBLE" : "NO SECRET")));
    ['height' => $remoteHeight] = getNodeAPIData($remoteNode);


    echo "\n\n";
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
    echo "\n\n";
    myEcho("shift-lisk directory:", $shiftDir);
    (($recoveryEnabled || $createSnapshots) ? myEcho("Trusted node:     ", $trustedNode) : "");
    echo "\n";
    myEcho("This node is:     ", $localRole, $inputRole); // must be MAIN
    echo "\n";
    myEcho("Directory network:", $shiftDirNetwork, $localNetwork); // must be same as a local network
    myEcho("Local node network:", $localNetwork, $inputNetwork);  // must be same as an input network
    echo "\n";
    myEcho("Local node API:", $localAPI, "AVAIBLE"); // must be avaible
    myEcho("Local forging API:", $localForgingAPI, "AVAIBLE"); // must be avaible

    if ($recoveryEnabled || $createSnapshots) {
        myEcho("Trusted node API:", $trustedAPI, "AVAIBLE"); // must be avaible
        echo "\n";
        myEcho("Trusted node height:", $trustedHeight, $localHeight, "height"); // must be same as local height
        myEcho("Local node height:", $localHeight, $trustedHeight, "height"); // must be same as trusted height
    } else {
        myEcho("Local node height:", "ok", "ok");
    }
}

function myEcho($string, $value, $compare=false, $h=false) {
    global $bold, $red, $green, $endStyle;

    if ($compare === false) {
        echo $bold.$string.$endStyle." \t $value \n";
    } else {
        echo $bold.$string.$endStyle." \t $value" . redOrGreen($value, $compare, $h) . "\n";
    }
}


function redOrGreen($first, $second, $h=false) {
    global $bold, $red, $green, $endStyle;

    if ($h == "height") {
        return (($first >= $second - 2) ? "\t".$bold.$green."OK".$endStyle : "\t".$bold.$red."FAIL".$endStyle);
    }

    return (($first === $second) ? "\t".$bold.$green."OK".$endStyle : "\t".$bold.$red."FAIL".$endStyle);
}

?>