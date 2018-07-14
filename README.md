# shift-checker
*Current version: 2.1.1*

### Changelog:

1. **More stable switching between Master and Slave;**

2. **Syncing module added;**
   * Restore from the last snapshot if a node's height is lower then (blockchain's height - 10), and a node is not syncing;
   * Restore from the last snapshot if a node is syncing, but node's height is lower then the last snapshot's height;
   * Rebuilding from shift_manager if restoring from the last snapshot failed;
      * For blockchain's height it uses explorer's height, but if explorer is unreachable it uses height of a parallel node;

3. **Better view in logs;**
   * Added useful information about nodes;
   * Removed secret key from logs, now it shows only **first - last** words from the secret;

4. **config.php updated;**
   * Removed a "feature" for multiple forging on one node;

5. **Fixed some little bugs in functions;**

6. **Telegram messages split into two modules;**
   * The first is still annoying..
   * The second one is used messaging only for syncing and restoring with a system information;
      * It has a counter for preventing flood and double messaging;
### Screenshot
![VPS logs](https://github.com/MxShift/shift-checker/blob/master/logs/screenshot.png)

### Forked and updated by [Mx](https://www.shiftproject.com/explorer/delegate/4446910057799968777S)

### Donations

- [x] Please consider voting for [Mx](https://explorer.shiftnrg.org/delegate/4446910057799968777S)

I'll also be happy to recieve some tips.

* **SHIFT: 4446910057799968777S**
* ETH: 0x6d5ebaddaa116e01d9c29c695b9ad8c9f634fa04

Thank you :tada:

### In the next updates:
- [ ] Requesting status of nodes from Telegram bot;
- [ ] Merge with [shift-monitor](https://github.com/MxShift/shift-monitor);

## Contacts
* Telegram: [Mx](https://t.me/voteformx)
* Ryver: [Mx](https://shiftnrg.ryver.com)

### How to update to v 2.1.1

The better way is: 
* to save data from your old **config.php** file;
* then delete old folder `cd && sudo rm -rf shift-checker`;
* and download the new one `git clone https://github.com/MxShift/shift-checker.git`;
* then change settings inside new **config.php**;

For **testnet** explorer can be used `https://explorer.testnet.shiftnrg.org`
      
---  

This script checks the status of your Shiftnrg Delegate by using PHP.<br>
Feel free to rewrite in Python or Bash. 
 
This script will also check whether your node has forked or not.<br>
When forked, it will stop Shift, restore to previous snapshot, and start Shift again.
  
This script will also check your consensus and switch forging to your backup node.<br>
When both nodes have a bad consensus, it will restart Shift and let you know by sending a Telegram message.

**IMPORTANT TO MENTION**
If you want to use the consensus controller, you need to add your secret(s) to config.php and remove them from your Shift config.json.
Also, you have to give access to the forging API calls for both nodes. Like this (1.2.3.4 is the extra IP):
```
    "forging": {
        "force": false,
        "secret": [],
        "access": {
            "whiteList": [
                "127.0.0.1","1.2.3.4",
            ]
        }
    },
```

There are some echo lines in this file.

When you redirect output to a log file in your crontab, these lines will show up.

See section Example crontab for more information.

Be sure to run this script after:
* You have installed shift-snapshot
* You have created a snapshot with shift-snapshot

## Prerequisites
Be sure that your php.ini allows passthru(). It's default that it does though, so just check if this script is not working.
```
sudo apt install php php-cli php-mbstring php-sqlite3
```
* shift-snapshot.sh: https://github.com/mrgrshift/shift-snapshot

## Installation
You can clone this into every directory of your choosing. I usually just clone it into my home folder.
```
git clone https://github.com/MxShift/shift-checker.git
```
* Rename `config.example.php` file to `config.php` and change settings inside it to match your needs
* Edit your crontab with the example below

## Example crontab
```
* * * * * php ~/shift-checker/checkdelegate.php >> ~/shift-checker/logs/checkdelegate.log 2>&1
```

## Telegram bot
Shift-Checker comes with Telegram functionality which will allow shfit-checker to send you a message if anything requires your attention. It's very easy to set up: 
* Open Telegram and start a conversation with: **[userinfobot](https://t.me/userinfobot)**
* Put your ID inside variable $telegramId. 
```
$telegramId = "12345678";
```
* Start a conversation with: **[BotFather](https://t.me/BotFather)**
* Say: /newbot
* Tell botfather your bot's name
* Tell botfather your bot's username
* Botfather will say "Congratulations!" and give you a token
* Put your token inside variable $telegramApiKey. 
```
$telegramApiKey   = "1122334455:AAEhBOweik6ad9r_QXMENQjcrGbqCr4K-bs";
```
* Edit the telegram toggle (true/false)
* Start a conversation with your bot (username) to enable communication between you two

## Contact 
* Telegram: [Jan](https://t.me/@jeeweevee) 

## Donations to Jan
Do you like/use my script(s)? Please consider donating the amount of a cup of coffee :-)

SHIFT: 7970982857025266480S

BTC: 1GbAWBiGyuybXJcjtyTvtH6hB5iezXNVdP

## Contributors
Seatrips (create snapshot when status is okay)
* Twitter: [@seatrips](https://twitter.com/seatrips)

Mrgr (Shift snapshots)
* Github: https://github.com/mrgrshift
