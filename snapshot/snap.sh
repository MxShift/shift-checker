#!/bin/bash
VERSION="0.3.2"

export LC_ALL=en_US.UTF-8
export LANG=en_US.UTF-8
export LANGUAGE=en_US.UTF-8

# CONFIG
# SHIFT_DIRECTORY=~/shift-lisk

#============================================================
#= snapshot.sh v0.2 created by mrgr                         =
#= Please consider voting for delegate mrgr                 =
#============================================================

#============================================================
#= snapshot.sh v0.3.2 created by Mx                         =
#= Please consider voting for delegate 'mx'                 =
#============================================================

echo " "

if [ ! -f ${SHIFT_DIRECTORY}/app.js ]; then
  echo -e "Error: No shift-lisk installation detected in the directory ${SHIFT_DIRECTORY} \nPlease, change config: nano shift-snapshot.sh \nor install: https://github.com/ShiftNrg/shift-lisk"
  exit 1
fi

if [ "\$USER" == "root" ]; then
  echo "Error: shift-lisk should not be run be as root. Exiting."
  exit 1
fi

SHIFT_CONFIG=${SHIFT_DIRECTORY}/config.json
DB_NAME="$(grep "database" $SHIFT_CONFIG | cut -f 4 -d '"')"
DB_USER="$(grep "user" $SHIFT_CONFIG | cut -f 4 -d '"')"
DB_PASS="$(grep "password" $SHIFT_CONFIG | cut -f 4 -d '"' | head -1)"
SNAPSHOT_INIT=.init

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

if [ ! -f $SNAPSHOT_INIT ]; then
  sudo chmod +x snap.sh
  echo "true" > $SNAPSHOT_INIT
  sudo chown postgres:${USER:=$(/usr/bin/id -run)} "$DIR"
  sudo chmod -R 777 $DIR
fi
SNAPSHOT_DIRECTORY=$DIR"/"


NOW=$(date +"%d-%m-%Y - %T")
################################################################################

create_snapshot() {
  export PGPASSWORD=$DB_PASS
  echo " + Creating snapshot"
  echo "--------------------------------------------------"
  snapshotName="shift_db_$NOW.sql.gz"
  snapshotLocation="$SNAPSHOT_DIRECTORY'$snapshotName'"
  sudo su postgres -c "pg_dump -Fp -Z 9 $DB_NAME > $snapshotLocation"
  blockHeight=`psql -d $DB_NAME -U $DB_USER -h localhost -p 5432 -t -c "select height from blocks order by height desc limit 1;"`

  if [ $? != 0 ]; then
    echo "X Failed to create snapshot."
    sudo rm -f "$SNAPSHOT_DIRECTORY$snapshotName"
    exit 1
  else
    fileSize=$(du -h "$SNAPSHOT_DIRECTORY$snapshotName" | cut -f1)
    echo "$NOW -- OK snapshot created successfully at block$blockHeight ${fileSize}B"
  fi

}

restore_snapshot(){
  echo " + Restoring snapshot"
  echo "--------------------------------------------------"
  SNAPSHOT_FILE=`ls -t ${SNAPSHOT_DIRECTORY}shift_db_* | head  -1`
  if [ -z "$SNAPSHOT_FILE" ]; then
    echo "! No snapshot to restore, please consider create it first"
    echo " "
    exit 1
  fi
  echo "Snapshot to restore = $SNAPSHOT_FILE"

  #snapshot restoring
  export PGPASSWORD=$DB_PASS
  # drop db
  resp=$(sudo -u postgres dropdb --if-exists "$DB_NAME" 2> /dev/null)
  resp=$(sudo -u postgres createdb -O "$DB_USER" "$DB_NAME" 2> /dev/null)
  resp=$(sudo -u postgres psql -t -c "SELECT count(*) FROM pg_database where datname='$DB_NAME'" 2> /dev/null)

  if [[ $resp -eq 1 ]]; then
    echo "√ Database reset successfully."
  else
    echo "X Failed to create Postgresql database."
    exit 1
  fi

  # restore dump
  gunzip -fcq "$SNAPSHOT_FILE" | psql -d $DB_NAME -U $DB_USER -h localhost -q &> /dev/null

  if [ $? != 0 ]; then
    echo "X Failed to restore."
    exit 1
  else
    echo "OK snapshot restored successfully."
  fi

}

################################################################################

case $1 in
"create")
  create_snapshot
  ;;
"restore")
  restore_snapshot
  ;;
esac