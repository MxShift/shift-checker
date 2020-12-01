# Changelog

## 2.1.6 (2020-12-01)

- Fix restore at node restart, due to the inability to get data via API
- Refactor of code. Add new functions
- Change a template of a node address in the config
- Raname "restoreEnable" to "recoveryEnabled" variable in the config

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

--

SyncingMessage заменить на recoveryMessage

--

Не дисаблить форджинг на бекапе просто так

--

При форке проверять на коррупт снапшот и рестор фром снапшот


--

Разделить проверку на форк и создание снапшотов.
Добавить в бд строку форк = фалс

--

! При создании снапшотов проверять хейт ноды

--

Найти возможность проверять целостность снапшота

--

сделать команды для shift-checker

reload, rebuild, stop, start, update_manager, update_client, update_wallet, create_snapshot, restore_snapshot

--

окмсг, не проверяет на то запущен шифт или нет
поменять на 
√ SHIFT is running.
чекнуть

Сделать паузу после перезапуска в 20 секунд.

--

Отдельное сообщение при неполучении хейта с ноды
+ сразу не триггериться, подождать следующей проверки

--

Разобраться с необходимостью приписывать http://

--

+ При сломаном снапшоте, добавить проверку на другие снапшоты.

- Add installation guide
- Update readme
- Update screenshots

END TODO -->