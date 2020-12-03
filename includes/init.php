<?php

// INITIALIZATION
// next move it to a file

$floppyEmoji = "%F0%9F%92%BE";
$starEmoji = "%E2%AD%90%EF%B8%8F";
$chainEmoji = "%E2%9B%93";
$storageEmoji = "%F0%9F%97%84";
$forkEmoji = "%F0%9F%94%80";
$recoveryEmoji = "%F0%9F%94%84";

$restoredMsg 	= "OK snapshot restored successfully.";	    // 'Okay' message from shift-snapshot
$createdMsg 	= "OK snapshot created successfully";	    // 'Okay' message from shift-snapshot	

// Get publicKey of the secret to use it in forging checks
$public = checkPublic($apiHost, $secret);

// Secret to array
$sec_array = explode(" ", $secret);

// Set the database to save our counts to
if (file_exists($database)) {
    $str_data = file_get_contents($database);
    $db_data = json_decode($str_data, true);
} else {
    file_put_contents($database, '{}');
    $db_data = json_decode('{}', true);

    $db_data["fork_counter"] = 0;
    $db_data["corrupt_snapshot"] = false;
    $db_data["synchronized_after_corrupt_snapshot"] = false;
    $db_data["recovery_from_snapshot"] = true;
    $db_data["rebuild_message_counter"] = 0;
    $db_data["syncing_message_sent"] = false;
}

// END INITIALIZATION