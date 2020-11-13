# shift-checker
*Current version: 2.1.4*

### Screenshot
![VPS logs](https://github.com/MxShift/shift-checker/blob/master/logs/screenshot.png)

### How to update to v 2.1.4

The better way is: 
* save data from your old **config.php** file
* delete old folder `cd && sudo rm -rf shift-checker`
* download the new one `git clone https://github.com/MxShift/shift-checker.git`
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

## Explorers

For **mainnet** explorer could be used: `https://explorer.shiftnrg.org`

For **testnet** explorer could be used: `https://explorer.testnet.shiftnrg.org`

### Forked and updated by [Mx](https://www.shiftproject.com/explorer/delegate/4446910057799968777S)

### Donations

- [x] Please consider voting for [Mx](https://explorer.shiftnrg.org/delegate/4446910057799968777S)

Thank you :tada:

### In the next updates:
- [ ] Requesting status of nodes from Telegram bot;
- [ ] Merge with [shift-monitor](https://github.com/MxShift/shift-monitor);

## Contacts
* Telegram: [Mx](https://t.me/voteformx)
* Discord: [Mx](https://discordapp.com/invite/fgzxABX)
      
---  
## Main description

This script checks:

1. The status of your Shift Project Delegate

2. If your node is forked or not
    * When forked, it will stop Shift then restore to the previous snapshot and start Shift again

3. Nodes consensus and switch forging to your backup node
    * When both nodes have a bad consensus, it will restart Shift and notify you by sending a Telegram message


**IMPORTANT TO MENTION**

If you want to use the consensus inspector, you want to add your secret passphrase to **config.php** and remove it from your Shift **config.json**.

Also, you have to give access to the forging API calls for both nodes. Like this (1.2.3.4 is the extra IP):
```
    "forging": {
        "force": false,
        "secret": [],
        "access": {
            "whiteList": [
                "127.0.0.1", "1.2.3.4"
            ]
        }
    },
```

## Prerequisites

```
sudo apt install php php-cli php-mbstring
```
**shift-snapshot**:

```
git clone https://github.com/MxShift/shift-snapshot
cd shift-snapshot
chmod +x shift-snapshot.sh
```

Be sure that your **php.ini** allows *passthru()*. Usually this is allowed by default, but you could check it with this command:
```
php -r 'passthru("echo working");'
```

The bash terminal should show a row `working`.


## Installation
You could clone this into any directory of your choice. 
Usually it's located simply in the home folder:
```
git clone https://github.com/MxShift/shift-checker.git
```
* Rename `config.example.php` file to `config.php` and change settings inside it to match your needs
* Edit your cron jobs with the example below

## Cron

To start script running open your crontabs:

```
crontab -e
```

and add this lines to run script every minute:

```
# mainnet shift-checker 
* * * * * php ~/shift-checker/checkdelegate.php >> ~/shift-checker/logs/checkdelegate.log 2>&1
```

## Logs

You could view logs in real time using:

```
tail -f ~/shift-checker/logs/checkdelegate.log
```

## Telegram bot
**shift-checker** comes with Telegram functionality which will allow **shfit-checker** to send you a message if anything requires your attention. 

It's very easy to set up: 

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
* Edit the telegram toggle to **true**
* Start a conversation with your bot (username) to enable communication between you two

## Contributors
[Jan](https://t.me/@jeeweevee) - first version of the script. For donations 1GbAWBiGyuybXJcjtyTvtH6hB5iezXNVdP

[Mrgr](https://github.com/mrgrshift) - shift-snapshot creator

[Seatrips](https://twitter.com/seatrips) - create snapshot when status is okay
