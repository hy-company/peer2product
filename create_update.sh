#!/bin/sh

rm -rf UPDATE

mkdir -p UPDATE/data/orders
mkdir -p UPDATE/data/products
mkdir -p UPDATE/data/users
mkdir -p UPDATE/data/vendors
mkdir -p UPDATE/data/settlements

cp .htaccess UPDATE/
cp index.php UPDATE/
cp -rpv ui UPDATE/
cp -rpv lib UPDATE/
cp -rpv admin UPDATE/
cp -rpv data/themes UPDATE/data/
cp -rpv data/gateways UPDATE/data/
cp -rpv data/users/0admin UPDATE/data/users/
cp data/*.json UPDATE/data
cp data/*.map UPDATE/data
cp data/*.def UPDATE/data
cp data/*.png UPDATE/data

# Do not overwrite essential json files...
rm UPDATE/data/settings.json
rm UPDATE/data/categories.json
rm UPDATE/data/countries.json
rm UPDATE/data/reporting.json
rm UPDATE/data/transportmath.json
rm UPDATE/data/modifiersmath.json

# Del or Nix the gateway
#echo '{"gateway":"Pay.nl","gateway-name":"Pay.nl","gateway-description":"Euro, Bank, Creditcard","gateway-active":1,"Account_ID":"","Program_ID":"","API_Token":"","Notification_E-mail":""}' > UPDATE/data/gateways/pay.nl/gateway.json
rm UPDATE/data/gateways/pay.nl/gateway.json

echo 'All done.'
