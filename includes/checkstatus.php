<?php

echo "\n[ STATUS ]\n\n";
echo "\t\t\tLet's check if our node is still running: ";

// Check status with shift_manager.bash. Use PHP's ob_ function to create an output buffer
ob_start();
$check_status = passthru("cd $pathtoapp && bash shift_manager.bash status | cut -z -b1-3");
$check_output = ob_get_contents();
ob_end_clean();

// Somewhere here we need to add a check for manual rebuild by the user

$apiIsDown = !ping($localNode);

// If status is not OK...
if (strpos($check_output, $okayMsg) === false || $apiIsDown) {

    echo "NO!\n";
    
    // Echo something to our log file
    $Tmsg = "Node ".$nodeName." not running. Restarting Shift";
    sendMessage($Tmsg);

    echo "\n\t\t\t".$Tmsg."\n";
    //Restarting Shift
    shiftManager("reload");
    pauseToWaitNodeAPI(20);

    // get public again
    $public = checkPublic($localNode, $secret);

// If status is OK
} else {
    echo "YES\n";
}
