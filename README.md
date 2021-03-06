# shift-checker
*Current version: 2.2.2*

### Screenshot
![VPS logs](https://github.com/MxShift/shift-checker/blob/master/logs/screenshot.png)

## Description

This script:

- Turns on shift-lisk node if it's suddenly turned off
- Checks if a node is forked
- Recovers a node from a snapshot or with shift_manager if something went wrong
- Switches the forging between your main and backup nodes, improving the reliability of your delegate
- Manages shift-lisk node via [command line options](#command-line-options)
- Notifies in Telegram if something goes wrong

### How to update to v 2.2.2

The better way is: 
* save data from your old **config.php** file
* delete the old folder `cd && sudo rm -rf shift-checker`
* download the new version of the script `git clone https://github.com/MxShift/shift-checker.git`
* change settings inside **config.example.php** file
* rename **config.example.php** file to **config.php**

Or you could just open the **shift-checker** directory and run **git fetch**:
```
cd shift-checker
git fetch
git reset --hard origin/master
```

Then change settings inside **config.example.php** and rename it to **config.php**.

Also delete an old **db.json** file.

To check if all settings are correct run:

```
php test.php
```


## Installation

### :page_with_curl: [Instalation Guide](https://github.com/MxShift/shift-checker/blob/master/INSTALL.md)

## Command line options

You could manage your shift-lisk node with shift-checker:

```
cd shift-checker
php run.php status              to check status of a shift-lisk node
php run.php stop                to stop a shift-lisk node
php run.php start               to start a shift-lisk node
php run.php reload              to reload a shift-lisk node
php run.php rebuild             to rebuild a shift-lisk node from an official snapshot
php run.php update              to run full update of a shift-lisk node
php run.php update_manager      to update a shift-lisk node manager
php run.php update_client       to update a shift-lisk node
php run.php update_wallet       to update a shift-lisk node wallet
php run.php create              to create a snapshot of the blockchain
php run.php restore             to restore height of a shift-lisk node from the last created snapshot
```

## In the next updates:
- [ ] Requesting status of nodes from Telegram bot;
- [ ] Merge with [shift-monitor](https://github.com/MxShift/shift-monitor);

## Donations

- [x] Please consider voting for [Mx](https://explorer.shiftnrg.org/delegate/4446910057799968777S)

Thank you :tada:

## Contacts
* Telegram: [Mx](https://t.me/voteformx)
* Discord: [Mx](https://discordapp.com/invite/fgzxABX)

## Contributors
[Jan](https://t.me/@jeeweevee) - first version of the script `BTC:1GbAWBiGyuybXJcjtyTvtH6hB5iezXNVdP`<br>
[Seatrips](https://twitter.com/seatrips) - helps to Jan<br>
[Mrgr](https://github.com/mrgrshift) - first version of shift-snapshot<br>