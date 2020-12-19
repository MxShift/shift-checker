<?php
// Ported from "snapshot.sh v0.2 created by mrgr"

// initialization
require('../config.php');
require('../includes/functions.php');
require('../includes/init.php');

// constants
$shiftConfig = $pathtoapp . "config.json";
$snapshotInit = ".init";
$snapDir = dirname(__FILE__) . "/";

// variables
$now = date("d-m-Y_H:i:s");
$snapshotName = "shift_db_" . $now . ".sql.gz";
$snapshotLocation = $snapDir . $snapshotName;

// get db credentials
$config_data = getJSONdata($shiftConfig);

$dbName = $config_data['db']['database'];
$dbUser = $config_data['db']['user'];
$dbPass = $config_data['db']['password'];

// first run
// next move it to init.php module
if (file_exists($snapshotInit) === false) {

    file_put_contents($snapshotInit, 'true');  // create .init file to run this instructions just once
    exec("sudo chown postgres:" . exec("/usr/bin/id -run") . " $snapDir");  // change owner of snapshot directory to postgres
    exec("sudo chmod -R 777 " . $snapDir);  // add full rights to snapshot directory
}

echo "___________________________________________________\n";
echo $now."\n\n";

// command line options
if ($argc > 1) {

    switch ($argv[1]) {
        
        case "create":
            create_snapshot();
            break;

        case "restore":
            restore_snapshot();
            break;

        default:
            if (substr($argv[$i], 1, 1) == '-') {
                echo "Unknown option: {$argv[$i]}\n";
            }
            
            break;
    }
} else {
    echo "No commands sent. Try 'create' or 'restore'\n";
}

function create_snapshot() {

    global $now, $snapshotLocation, $dbName, $dbUser, $dbPass;

    echo " + Creating snapshot\n";
    echo "--------------------------------------------------\n\n";
    // create the dump
    exec("export PGPASSWORD=$dbPass && sudo su postgres -c 'pg_dump -Fp -Z 1 $dbName > $snapshotLocation' 2>&1", $output);
    // get height from db
    exec('export PGPASSWORD=' . $dbPass . ' && psql -d ' . $dbName . ' -U ' . $dbUser . ' -h localhost -p 5432 -t -c "select height from blocks order by height desc limit 1;"', $blockHeight);

    $created = empty($output);  // if output is not empty means here is a some error message

    if ($created) {

        $fileSize=exec("du -h ". $snapshotLocation . " | cut -f1");
        echo "$now -- OK snapshot created successfully at block" . $blockHeight[0] . " " . $fileSize."B \n";
    } else {

        system("sudo rm -f $snapshotLocation");
        exit("X Failed to create snapshot.\n");
    }
}


function restore_snapshot() {

    global $now, $snapDir, $dbName, $dbUser, $dbPass;

    echo " + Restoring snapshot\n";
    echo "--------------------------------------------------\n\n";
    $snapshotFile = exec("ls -t " . $snapDir . "shift_db_* | head  -1");

    if (!file_exists($snapshotFile)) {
        exit("! No snapshot to restore, please consider create it first \n");
    }

    echo "Snapshot to restore: $snapshotFile \n";

    // drop db
    exec('sudo -u postgres dropdb --if-exists "' . $dbName . '" 2> /dev/null');
    exec('sudo -u postgres createdb -O "' . $dbUser . '" "' . $dbName . '" 2> /dev/null');
    exec("sudo -u postgres psql -t -c \"SELECT count(*) FROM pg_database where datname='" . $dbName . "'\" 2> /dev/null", $output);

    $created = trim($output[0]); // if 1 - true, if 0 - false
    unset($output);

    if ($created) {
        echo "√ Database reset successfully.\n";
    } else {
        exit("X Failed to create Postgresql database.\n");
    }

    // restore dump
    exec('export PGPASSWORD=' . $dbPass . ' && gunzip -fcq "' . $snapshotFile . '" | psql -d ' . $dbName . ' -U ' . $dbUser . ' -h localhost -q 2> /dev/null', $output);

    $restored = !empty($output);  // if output is empty means restoration is failed

    if ($restored) {
        echo "OK snapshot restored successfully.\n";
    } else {
        echo "X Failed to restore.\n";
    }
}


function getJSONdata($file) {

    global $pathtoapp;

    if (file_exists($file)) {
        $str_data = file_get_contents($file);
        $JSON_data = json_decode($str_data, true);
        return $JSON_data;
    } else {
        exit("Error: No shift-lisk installation detected in the directory $pathtoapp \nPlease, change config \nor install: https://github.com/ShiftNrg/shift-lisk");
    }
}

?>