#!/bin/sh
echo "This script installs the dependencies to be able to run Peer2Product."
sudo apt install php
sudo a2enmod rewrite
sudo service apache2 restart
echo
echo "All done! Have fun!"
