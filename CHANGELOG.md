# Changelog

## 2.2.2 (2020-12-24)

- Add command line options to create and restore a snapshot

## 2.2.1 (2020-12-23)

- Rewrite **shift-snapsot** module code from bash to php
- Add async for creating snapshots. Now the script does not block its work indefinitely when creating snapshots
- Beautify Telegram messages

## 2.2.0 (2020-12-17)

- Add test for a config to check the settings before the first run of the script
- Add shift-lisk client update on fork
- Add command line options to manage shift-lisk node

## 2.1.9 (2020-12-09)

- Security Update: script no longer send the passphrase over the network
- Now the script enables and disables forging only on its local nodes
- Improve of nodes switching
- Improve of node status check

## 2.1.8 (2020-12-07)

- Fix removing of old snapshots
- Fix recovery from a local snapshot
- Fix sending a message with just a local node data
- Rename "main" and "backup" nodes to "local" and "remote"
- A lot of code refactor

## 2.1.7 (2020-12-02)

- Add compressing for snapshots
- Add node height check before snapshot creation
- Beutify Telegram messages. Add info about blockchain height and snapshot file size
- Change folder for snapshots
- Rename checkdelegate.php to run.php
- Rename shift-snapshot.sh to snap.sh
- A lot of code refactor and dry

## 2.1.6 (2020-12-01)

- Fix restore at node restart, due to the inability to get data via API
- Fix rebuild with shift_manager
- Change a template of a node address in the config
- Raname "restoreEnable" to "recoveryEnabled" variable in the config
- Refactor of code. Add new functions

## 2.1.5 (2020-11-18)

- Embed **shift-snapshot** script
- Small fixes

## 2.1.4 (2020-11-13)

- Friday the 13th big fix 🎃

## 2.1.3 (2020-11-13)

- Improvement of 'consensus' module stability. Add check if **shift-checker** script is disabled on a Main node
- Improvement of 'recovery' module stability. Add check for a corrupt snapshot
- Readme corrections

## 2.1.2 (2020-04-17)

- Database switch from SQLite3 to a JSON file
- Improve of 'syncing' module stability
- Names of nodes variables. There are no masters no slaves anymore %)
- 'syncing' module rename to 'recovery'
- Config usability improvement
- Changelog file added
- Readme corrections

## 2.1.1 (2018-07-01)

- Some stuff for update with git
- Small fixes

## 2.1.0 (2018-05-18)

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


<!-- TODO

- Снова использовать SQLite базу данных. 
- На данный момент происходят коллизии записи в файл db.json если обращаться к нему из разных скриптов: run.php и snap.php

- Сделать оповещение в модуле "рекавери", о том, что трастед нода отстаёт от локальной, при отставании в 10 блоков

- сделать команды для shift-checker: create_snapshot, restore_snapshot

- При форке проверять на рестор фром снапшот. Добавить возможность ребилда через менеджер
- Добавить в бд строку форк = фалс

- Update screenshots


+ Add installation guide
+ Попробовать сделать псевдо-таблицу при отправке сообщения с данными нод в ТГ
+ Забьютифаить сообщения для ТГ
+ сделать команды для shift-checker: reload, rebuild, stop, start, update_manager, update_client, update_wallet
+ ! Пофиксить пинг, если ссылка без порта
+ При форке делать апдейт клиента через ./shift_manager.bash update_client
+ При форке проверять на коррупт снапшот
+ Проверять на мейне, запущен ли скрипт на бекапе
+ ! Не дисаблить форджинг на бекапе просто так
+ Посмотреть почему $dataTmsg не присылается в ТГ, если выключен свитчинг
+ Разделить проверку на форк и создание снапшотов.
+ В конфиге, вместо мейн и бекап, сделать локал и ремоут ноды, чтобы удобно было переключаться, да и вообще, меньше заморочек
+ Заренеймить checkdelegate.php в run.php
+ Пофиксить вывод размера снапшота
+ Отправлять размер созданного снапшота в телеграм
+ SyncingMessage заменить на recoveryMessage
+ При создании снапшотов проверять хейт ноды
+ окмсг, не проверяет на то запущен шифт или нет. Сделать паузу после перезапуска в 20 секунд.

--
+ Доразбираться с модулем "статус".
+ Шифт менеджер всегда выдаёт "√ SHIFT is running.", даже если он только что остановлен.
+ Сделать проверку по доступности апи, если недоступно, значит нода выключена и надо включить.
+ Перенести весь треш с проверкой статуса в функцию shiftManager
--

END TODO -->