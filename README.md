# setstorm-mgmt
The setstorm Management Server Script - Host your own Storm Server!

## Installation
### Linux
#### Requirements
To host a storm server, you will have to have apache2, php5 or above and ffmpeg installed. To install these dependencies on Ubuntu, run the following commands:
```
sudo apt update
sudo apt install apache2 php7.1 libapache2-mod-php7.1 php-mcrypt ffmpeg
a2enmod php7.1
service apache2 restart
```
#### Storm Server Setup
To set up the Storm server, clone this repository into your server's root webspace directory. Once complete, open the `mgmt.php` file and **change the secret to a random character string**, not longer than 255 characters. You will have to enter the secret on setstorm, so it's recommended to temporarily copy & paste it into a text file.
Once complete, open the setstorm upload page and select "Add custom Storm server" from the storm selection dropdown. Now enter the URL of the cloned repo's directory as your Base URL, and target the mgmt.php script in the Management URL field. Don't forget to also paste your configured secret into the secret input field. Examples:
```
Base URL: https://ctection.com/media/
Management URL: https://ctection.com/media/mgmt.php
Secret: ***************************
```
Setstorm will automatically check your input and will alert you if some parameters are incorrect or if a successful connection has been established.
