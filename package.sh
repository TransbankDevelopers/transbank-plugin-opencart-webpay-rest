#!/usr/bin/env bash

set -Eeuo pipefail

#Script for create the plugin artifact
TAG="${TAG:-}"

if [ "$TAG" = "" ]
then
   TAG='1.0.0'
fi

echo "Plugin tag: $TAG"

SRC_DIR="src"
FILE1="upload/system/library/transbank/utils/TransbankSdkWebpay.php"
FILE2="install.xml"

sed -i.bkp "s/PLUGIN_VERSION = '1.0.0';/PLUGIN_VERSION = '${TAG}';/g" "$SRC_DIR/$FILE1"
sed -i.bkp "s/<version>1.0.0/<version>${TAG}/g" "$SRC_DIR/$FILE2"

PLUGIN_FILE="plugin-transbank-webpay-rest-opencart3-$TAG.ocmod.zip"

cp CHANGELOG.md $SRC_DIR
cp LICENSE $SRC_DIR
cd $SRC_DIR
zip -FSr ../$PLUGIN_FILE . -x *.git/\* .DS_Store* *.zip "$FILE1.bkp" "$FILE2.bkp"
cd ..

rm "$SRC_DIR/CHANGELOG.md"
rm "$SRC_DIR/LICENSE"
cp "$SRC_DIR/$FILE1.bkp" "$SRC_DIR/$FILE1"
cp "$SRC_DIR/$FILE2.bkp" "$SRC_DIR/$FILE2"
rm "$SRC_DIR/$FILE1.bkp"
rm "$SRC_DIR/$FILE2.bkp"

echo "Plugin version: $TAG"
echo "Plugin file: $PLUGIN_FILE"
