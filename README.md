# shift-checker
*Current version: 2.2.0*

### Screenshot
![VPS logs](https://github.com/MxShift/shift-checker/blob/master/logs/screenshot.png)

## Description

This script:

- Turns on shift-lisk node if it's suddenly turned off
- Checks if a node is forked
- Recovers a node from a snapshot or with shift_manager if something went wrong
- Switches the forging between your main and backup nodes, improving the reliability of your delegate
- Notifies in Telegram if something goes wrong

### How to update to v 2.2.0

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

## Installation

### :page_with_curl: [Instalation Guide](https://github.com/MxShift/shift-checker/blob/master/INSTALL.md)

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
[Jan](https://t.me/@jeeweevee) - first version of the script (BTC:1GbAWBiGyuybXJcjtyTvtH6hB5iezXNVdP)

[Seatrips](https://twitter.com/seatrips) - helps to Jan

[Mrgr](https://github.com/mrgrshift) - first version of shift-snapshot


