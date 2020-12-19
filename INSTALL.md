# Instalation Guide


## Prerequisites

```
sudo apt install php php-cli php-mbstring
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
* Rename `config.example.php` file to `config.php`
* Open `config.php` file and rename your node with `$nodeName` row inside double brackets
* Choose settings for a [single node](#single-node-settings) or for a [cluster](#cluster-settings) below


## Single node settings

**Best `config.php` settings :**

```
$switchingEnabled   = false;
$thisMain           = true;
$secret             = "your secret";  // but it's not nessesary here
$recoveryEnabled    = true;
$createSnapshots    = true;
$trustedNode        = "link to a trusted node wallet";
$recoveryMessages   = true;
$telegramId         = "your Telegram ID";  // see Telegram section below
$telegramApiKey     = "your Telegram API key";  // see Telegram section below
```

Check your node API, it should be enabled:

```
cd shift-lisk
nano config.json
```

Find and set it *true*:
```
    "api": {
        "enabled": true,
```

To check if all settings are correct:

```
cd
cd shift-checker
php test.php --main --mainnet
```
If all is okay:

* Just start script runnin by adding a **cron** job with the [example below](#cron)


## Cluster settings

If you want script to switches the forging between your main and backup nodes, choosing the best terms. Just follow instruction below:

**Best `config.php` settings :**

```
$switchingEnabled   = true;
$thisMain           = true;  // or false if it's your backup node
$secret             = "your secret here";
$recoveryEnabled    = true;
$createSnapshots    = true;
$trustedNode        = "link to a trusted node wallet";
$recoveryMessages   = true;
$telegramId         = "your Telegram ID";  // see Telegram section below
$telegramApiKey     = "your Telegram API key";  // see Telegram section below
```

If you setting up a main node add a backup node URL here:

And opposite, if you setting up a backup node add a main node URL here:
```
$remoteNode         = "URL to a node wallet";
```

Usually it looks like `http://your-node-IP-address:9305` or with `9405` port for testnet.
To check it just open your URL in a web browser. The API is disabled by default, so we'll need to enable it:

```
cd shift-lisk
nano config.json
```

or open `config.json` with your FTP manager

Then find and set both *true*:
```
    "api": {
        "enabled": true,
        "access": {
            "public": true,

```

Find your secret and remove it from `config.json`:

```
    "forging": {
        "force": false,
        "secret": [],
```

Also, you have to give access to the forging API calls on both nodes for forging check. Add your nodes IP to whitelist like this:
```
    "forging": {
        "force": false,
        "secret": [],
        "access": {
            "whiteList": [
                "127.0.0.1", "1.2.3.4", "2.3.4.5"
            ]
        }
    },
```

To check if all settings are correct run on both nodes:

```
cd
cd shift-checker
php test.php
```

If all is okay:

* Just start script runnin by adding a **cron** job on both nodes with the [example below](#cron)


## Trusted nodes

For **mainnet** trusted node could be used: `https://wallet.shiftnrg.org`

For **testnet** trusted node could be used: `https://wallet.testnet.shiftnrg.org`


## Logs

You could view logs in real time using:

```
tail -f ~/shift-checker/logs/run.log
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
* Edit the `$recoveryMessages` toggle to **true**
* Start a conversation with your bot (username) to enable communication between you two


## Cron

To start script running open your crontabs:

```
crontab -e
```

add this lines to run script every minute:

```
# mainnet shift-checker 
* * * * * php ~/shift-checker/run.php >> ~/shift-checker/logs/run.log 2>&1
```

:tada: Enjoy!