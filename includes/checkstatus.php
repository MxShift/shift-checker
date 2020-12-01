<?php

echo "\n[ STATUS ]\n\n";
echo "\t\t\tLet's check if our node is still running: ";

// Check status with shift_manager.bash. Use PHP's ob_ function to create an output buffer
ob_start();
$check_status = passthru("cd $pathtoapp && bash shift_manager.bash status | cut -z -b1-3");
$check_output = ob_get_contents();
ob_end_clean();

// Somewhere here we need to add a check for manual rebuild by the user

// If status is not OK...
if (strpos($check_output, $okayMsg) === false) {
    
    // Echo something to our log file
    $Tmsg = "Delegate ".$nodeName." not running. Restarting Shift";
    sendMessage($Tmsg);

    echo "\n\t\t\t".$Tmsg."\n";
    //Restarting Shift
    shiftManager("reload");
    pauseToWaitNodeAPI(20);

// If status is OK
} else {
    echo "YES!\n";
}
