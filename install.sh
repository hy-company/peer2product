#!/bin/sh
echo "This script installs the dependencies to be able to run Peer2Product."
sudo apt install php
echo " - Configuring Apache2..."
sudo a2enmod rewrite
sudo service apache2 restart
echo "All done! Have fun!"
