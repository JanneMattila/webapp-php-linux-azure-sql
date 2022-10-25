#!/bin/bash

echo "Installing jq..."
apt-get install jq -y

echo "Installing SQL Server drivers..."
download=$(curl -sL https://api.github.com/repos/Microsoft/msphpsql/releases/latest | jq -r '.assets[].browser_download_url' | grep Debian11-8.1.tar)
wget $download -O msphpsql.tar
mkdir -p wwwroot/bin
tar -xf msphpsql.tar -C wwwroot/bin --strip-components 1
rm *.tar

cp /home/site/default /etc/nginx/sites-available/default

service nginx reload
