#!/bin/bash

echo "Installing jq..."
apt-get install jq -y

echo "Installing SQL Server drivers..."
download=$(curl -sL https://api.github.com/repos/Microsoft/msphpsql/releases/latest | jq -r '.assets[].browser_download_url' | grep Debian11-8.1.tar)
wget $download -O msphpsql.tar
mkdir -p /home/site/ini/bin
tar -xf msphpsql.tar -C /home/site/ini/bin --strip-components 1
rm *.tar

cp /home/site/nginx.conf /etc/nginx/sites-available/default

service nginx reload
