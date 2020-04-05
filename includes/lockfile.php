<?php

// Check if lock file exists
if (file_exists($lockfile)) {

	// Check age of lock file and touch it if older than 20 minutes
	if((time()-filectime($lockfile)) >= 1200){
	
		echo $date." - [ LOCKFILE ] Lock file is older than 20 minutes. Going to touch it and continue..\n";
		
		if (!touch($lockfile)){
		  exit("[ LOCKFILE ] Error touching $lockfile\n");
		}

	// If file is younger than 20 minutes, exit!
	}else{
		exit("[ LOCKFILE ] A previous job is still running...\n");
	}

}else{
  // Lock file does not exist, let's touch it
  if (!touch($lockfile)){
    exit("[ LOCKFILE ] Error touching $lockfile\n");
  }
}