<?php

echo "\n[ CONSENSUS ]\n\n";

  echo "\t\t\tConsensus: ";

  if ($consensusEnable === true && !empty($secret)) {
      echo "enabled\n\n";
      
      // Check height, consensus and syncing on Blockhain
      $heightExplorer = @file_get_contents($explorer."/api/statistics/getLastBlock");

      if ($heightExplorer === false) {
          $heightExplorer = 0;
      } else {
          $heightExplorer = json_decode($heightExplorer, true);
          $heightExplorer = $heightExplorer['block']['height'];
      }

      // Check height, consensus and syncing on Master node
      $statusMaster = @file_get_contents($mainnode.":".$mainport."/api/loader/status/sync");
      
      if ($statusMaster === false) {
          $consensusMaster = 0;
          $heightMaster = 0;
          $syncingMaster = false;
      } else {
          $statusMaster = json_decode($statusMaster, true);

          if (isset($statusMaster['height']) === false) {
              $heightMaster = "error";
          } else {
              $heightMaster = $statusMaster['height'];
          }

          $syncingMaster = $statusMaster['syncing'];
          $consensusMaster = $statusMaster['consensus'];
      }

      // Check height, consensus and syncing on Slave node
      $statusSlave = @file_get_contents($backupnode.":".$backupport."/api/loader/status/sync");

      if ($statusSlave === false) {
          $consensusSlave = 0;
          $heightSlave = 0;
          $syncingSlave = false;
      } else {
          $statusSlave = json_decode($statusSlave, true);

          if (isset($statusSlave['height']) === false) {
              $heightSlave = "error";
          } else {
              $heightSlave = $statusSlave['height'];
          }

          $syncingSlave = $statusSlave['syncing'];
          $consensusSlave = $statusSlave['consensus'];
      }

      // Get publicKey of the secret to use in forging checks
      $public = checkPublic($apiHost, $secret);

      // Secret to array
      $sec_array = explode(" ", $secret);

      $forgingSlave = checkForging($backupnode.":".$backupport, $public);
      $forgingMaster = checkForging($mainnode.":".$mainport, $public);

      echo "\t\t\tHeight Explorer: $heightExplorer\n\n";

      echo "\t\t\tConsensus Master: ".$consensusMaster."%";
      echo "\t\tConsensus Slave: ".$consensusSlave."%\n";

      echo "\t\t\tHeight Master: $heightMaster";
      echo "\t\tHeight Slave: $heightSlave \n";

      echo "\t\t\tSyncing Master: ".json_encode($syncingMaster); // Boolean to string
      echo "\t\tSyncing Slave: ".json_encode($syncingSlave)."\n";

      echo "\t\t\tForging Master: ".$forgingMaster;
      echo("\t\tForging Slave: ".$forgingSlave."\n\n");

      // Check if we are the master node
      if ($main === false) {
          // If we land here, we are the slave
          echo "\t\t\tSlave: true\n";

          echo "\t\t\tMaster online: ";
          // Check if the master is online
          $find = array("http://","https://");
          $up = ping(str_replace($find, "", $mainnode), $mainport);

          if ($up) {
              // Master is online. Do nothing..
              echo "true\n";

              // Check if we are forging
              echo "\t\t\tSlave forging: ";
              $forging = checkForging($backupnode.":".$backupport, $public);
        
              // If we are forging..
              if ($forging == "true") {
                  echo "true!\n\n\t\t\tEverything seems okay.\n\n";
              } else {

                  if ($forging == "error") {
                      echo "error\n";
                  } else {
                      echo "false\n";
                  }
                  // Check if the master is synced
                  echo "\t\t\tMaster syncing: ";

                  // COMPARE HEIGHT on master node and slave node
                  if ($heightMaster < ($heightSlave - 10)) {
                      // Enable forging on slave
                      echo "true\n";
                      echo "\t\t\tEnabling forging on slave for secret: ".current($sec_array)." - ".end($sec_array)."\n";
                      enableForging($backupnode.":".$backupport, $secret);
                  } else {
                      // Master is synced. Do nothing..
                      echo "false\n\n\t\t\tEverything will be okay.\n\n";
                  }
              }
          } else {
              // Master is offline. Let's check if we are forging, if not; enable it.
              echo "false!\n";

              echo "\t\t\tSlave forging: ";
              $forging = checkForging($backupnode.":".$backupport, $public);
        
              // If we are forging..
              if ($forging == "true") {
                  echo "true!\n\n";

                  // Send Telegram ANNOYING!!! Slave is forging!
                  // $Tmsg = $nodeName.": Master node seems offline. Slave is forging though..";
                  // sendMessage($Tmsg);
          
                  // If consensus on the slave is below threshold divided by two (becouse of Master is offline) as well, restart Shift!
                  if ($consensusSlave <= ($threshold / 2) && $syncingSlave === false) {
                      $Tmsg = $nodeName.": Threshold on slave node reached! No healthy server online.";
                      echo "\t\t\t".$Tmsg."\n";
                      sendMessage($Tmsg);

                      // Restart Shift on Slave if restoring is disabled
                      if (!$restoreEnable) {
                          echo "\t\t\tRestarting Shift on Master\n\n";
                          system("cd $pathtoapp && ./shift_manager.bash reload");
                      }
                  } else {

                      if ($syncingSlave === true) {
                          $Tmsg = $nodeName.": Slave node is forging and syncing. Looks like a bug! Enabling forging on master node.";
                          echo "\t\t".$Tmsg."\n";
                          sendMessage($Tmsg);

                          echo "\t\t\tDisabling forging on slave for secret: ".current($sec_array)." - ".end($sec_array)."\n";
                          disableForging($backupnode.":".$backupport, $secret);

                          echo "\t\t\tRestarting Shift on Slave\n\n";
                          system("cd $pathtoapp && ./shift_manager.bash reload");
                      } else {
                          // All is fine. Do nothing..
                          echo "\t\t\tConsensus is fine!\n\n";
                      }
                  }
              } else {

                  if ($forging == "error") {
                      echo "error\n";
                  } else {
                      echo "false!\n\n\t\t\tWe are not forging! Let's enable it..\n";
                  }
                  // Enable forging on the slave
                  // If Telegram is enabled, send a message that the master seems offline
                  $Tmsg = $nodeName.": Master node seems offline. Slave will enable forging now..";
                  sendMessage($Tmsg);

                  echo "\t\t\tEnabling forging on slave for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                  enableForging($backupnode.":".$backupport, $secret);
              }
          }
      } else {
          // If we land here, we are the master
          echo "\t\t\tMaster: true\n";
        
          // Check if we are forging
          $forging = checkForging($mainnode.":".$mainport, $public);

          // If we are forging..
          if ($forging == "true") {
              echo "\t\t\tMaster forging: true\n\n";

              // Forging on the slave should be/stay disabled for secret until we perform a consensus check.
              // This way we ensure that forging is only disabled on nodes the master chooses.
              echo "\t\t\tDisabling forging on slave for secret: ".current($sec_array)." - ".end($sec_array)."\n";
              disableForging($backupnode.":".$backupport, $secret);

              // Check consensus on master node
              // If consensus is the same as or lower than the set threshold. Going to restart Shift on Master
              if ($consensusMaster <= $threshold && $syncingMaster === false) {
                  echo "\t\t\t".$Tmsg."\n";

                  $Tmsg = $nodeName.": Threshold on master node reached! Going to check the slave node and restart Shift on Master.";
                  sendMessage($Tmsg);
        
                  // Check consensus on slave node
                  // If consensus on the slave is below threshold as well, send a telegram message and restart Shift!
                  if ($consensusSlave <= $threshold && $syncingSlave === false) {
                      $Tmsg = $nodeName.": Threshold on slave node reached! No healthy server online.";
                      echo "\t\t\t".$Tmsg."\n";
                      sendMessage($Tmsg);

                  } else {

                      if ($syncingSlave === true) {
                          $Tmsg = $nodeName.": Threshold reached on master node, but slave is syncing. No healthy server online.";
                          echo "\t\t\t".$Tmsg."\n\n";
                          sendMessage($Tmsg);
                      } else {
                          echo "\t\t\tConsensus on slave is sufficient enough to switch to..\n";
              
                          echo "\t\t\tEnabling forging on slave for secret: ".current($sec_array)." - ".end($sec_array)."\n";
                          enableForging($backupnode.":".$backupport, $secret);

                          echo "\t\t\tDisabling forging on master for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                          disableForging($mainnode.":".$mainport, $secret);
                      }
                  }

                  if (!$restoreEnable) {
                      echo "\t\t\tRestarting Shift on Master\n";
                      system("cd $pathtoapp && ./shift_manager.bash reload");
                  }

              } else {

                  if ($syncingMaster === true) {
                      $Tmsg = $nodeName.": Master node is forging and syncing. Looks like a bug! Enabling forging on slave node";
                      echo "\t\t\t".$Tmsg."\n";
                      sendMessage($Tmsg);

                      echo "\t\t\tEnabling forging on slave for secret: ".current($sec_array)." - ".end($sec_array)."\n";
                      enableForging($backupnode.":".$backupport, $secret);

                      echo "\t\t\tDisabling forging on master for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                      disableForging($mainnode.":".$mainport, $secret);

                      echo "\t\t\tRestarting Shift on Master\n";
                      passthru("cd $pathtoapp && ./shift_manager.bash reload");

                  } else {
                      // Master consensus is high enough to continue forging
                      echo "\t\t\tThreshold on master node not reached.\n\n\t\t\tEverything is okay.\n\n";
                  }
              }

          // If we are Master and not forging..
          } else {

              if ($forging == "error") {
                  echo "\t\t\tMaster forging: error!\n";
              } else {
                  echo "\t\t\tMaster forging: false!\n";
              }
              // Check if the slave is forging
              $forging = checkForging($backupnode.":".$backupport, $public);

              // If slave is forging..
              if ($forging == "true") {
                  echo "\t\t\tSlave forging: true\n\n";

                  // If consensus is the same as or lower than the set threshold..
                  if ($consensusSlave <= $threshold) {
                      echo "\t\t\tConsensus slave reached the threshold.\n";
                      echo "\t\t\tChecking consensus, height and syncing on master node..\n";
            
                      // If consensus is the same as or lower than the set threshold..
                      if ($consensusMaster <= $threshold && $syncingMaster === false) {
                          echo "\t\t\tThreshold on master node reached as well! Restarting Shift..\n";

                          if (!$restoreEnable) {
                              echo "\t\t\tRestarting Shift on Master\n";
                              system("cd $pathtoapp && ./shift_manager.bash reload");
                          }
                      } else {

                          if ($syncingMaster === true) {
                              echo "\t\t\tMaster node is syncing. Doing nothing..\n";
                
                              $Tmsg = $nodeName.": Warning! Consensus slave reached the threshold, but master node is syncing. No healthy servers online!";
                              echo "\t\t\t".$Tmsg."\n";
                              sendMessage($Tmsg);

                          } else {
                              // Consensus is sufficient on master. Going to check syncing of masternode with 100% consensus
                              echo "\t\t\tConsensus on master is sufficient.\n";

                              if ($heightMaster < ($heightExplorer - 101)) {
                                  echo "\t\t\tBut seems master node is syncing. Doing nothing..\n";

                                  $Tmsg = $nodeName.": Warning! Consensus slave reached the threshold, but seems master node is syncing. No healthy servers online!";
                                  echo "\t\t\t".$Tmsg."\n";
                                  sendMessage($Tmsg);

                              } else {
                                  echo "\t\t\tMaster node is synced!\n";
                                  echo "\t\t\tEnabling forging on master for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                                  enableForging($mainnode.":".$mainport, $secret);

                                  echo "\t\t\tDisabling forging on slave..\n";
                                  echo "\t\t\tDisabling forging on slave for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                                  disableForging($backupnode.":".$backupport, $secret);
                              }
                          }
                      }
                  } else {
                      echo "\t\t\tConsensus on slave is sufficient. Doing nothing..\n\n";
                  }
              } else {
                  if ($forging == "error") {
                      echo "\t\t\tChecking of Slave's forging got an error!\n";

                  } else {
                      echo "\t\t\tSlave is not forging as well!\n";
                  }
                  // Slave is also not forging! Compare consensus on both nodes and enable forging on node with highest consensus an height..
                  $Tmsg = $nodeName.": Master and Slave are both not forging! Going to enable forging on the best node.";
                  sendMessage($Tmsg);

                  echo "\t\t\tLet's compare consensus and enable forging on best node..\n";

                  if ($consensusMaster > $consensusSlave && $heightMaster > ($heightSlave - 10)) {
                      echo "\t\t\tEnabling forging on master for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                      enableForging($mainnode.":".$mainport, $secret);

                  } else {
                      echo "\t\t\tEnabling forging on slave for secret: ".current($sec_array)." - ".end($sec_array)."\n\n";
                      enableForging($backupnode.":".$backupport, $secret);

                  } // END: COMPARE CONSENSUS
              } // END: SLAVE FORGING IS FALSE
          } // END: MASTER FORGING IS FALSE
      } // END: WE ARE THE MASTER
  } else {
      echo "disabled (or no secret)\n\n";
      
  } // END: ENABLED CONSENSUS CHECK?
