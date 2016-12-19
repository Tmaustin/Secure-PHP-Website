# Secure-PHP-Website
Server

  # Update the repositories;
  
  echo "Update the repositories";
  
  sudo apt-get update;
  
  # Installing Apache and PHP;
  
  echo "Installing Apache and PHP";
  
  sudo apt-get -y install apache2 php libapache2-mod-php php-mcrypt php-mysql;
  
  # Install MySQL and setting the root password;
  
  echo "Install MySQL and setting the root password";
  
  sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password 4TdD0AxKOZ3S7f53';
  
  sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password 4TdD0AxKOZ3S7f53';
  
  sudo apt-get -y install mysql-server;
  
  # Go to website location;
  
  echo "Go to website location";
  
  cd /var/www/html;
  
  # Delete all files in HTML folder for git purposes;
  
  echo "Delete all files in HTML folder for git purposes";
  
  rm -rf /var/www/html/*;


Transfer the contents of the Secure PHP Website folder to /var/www/html/



Server Part 2
  
  echo "Importing old information from file onto server";
  
  mysql -u root -p4TdD0AxKOZ3S7f53 < /var/www/html/comp424.sql;
  
  #Rewrite the apache2.conf in order for the .htacces files to work;
  
  echo "Rewriting the apache2.conf in order for the .htacces files to work";
  
  cp -f /var/www/html/apache2.conf /etc/apache2/;
  
  #Deleting files that were used to update MySQL and Apache;
  
  echo "elete files that were used to update MySQL and Apache";
  
  rm /var/www/html/comp424.sql;
  
  rm /var/www/html/apache2.conf;
  
  #remove .git folder;
  
  echo "Removing .git folder";
  
  rm -rf /var/www/html/.git/;
  
  #Restarting services;
  
  echo "Restarting services";
  
  service apache2 restart && service mysql restart > /dev/null;
  
  echo "DONE";
