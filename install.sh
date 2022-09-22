#!/bin/sh
echo "This script installs the dependencies to be able to run Peer2Product."
sudo apt install php php-pear
echo " - Configuring Apache2..."
sudo a2enmod rewrite
sudo service apache2 restart
echo " - Installing Pear Mail..."
sudo pear install Mail
echo
echo "All done! Have fun!"
