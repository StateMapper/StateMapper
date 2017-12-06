<p align="center" id="top">
	<a href="https://github.com/StateMapper/StateMapper" title="Go to the project's homepage"><img src="../../app/assets/images/logo/logo-black-big.png" /></a>
</p>
<p align="center">
	<strong>INSTALLATION GUIDE</strong>
</p>

*[&larr; homepage](https://github.com/StateMapper/StateMapper#top)*

-----

**Index:** [Requirements](#requirements) / [Installation](#installation) / [Daemon commands](#daemon-commands)


## Requirements

StateMapper might work just fine on any [Debian-based](https://www.debian.org/derivatives/) Linux operating system, with the following software requirements:

* [PHP4+](http://php.net/) *(best PHP7+)*
* [MariaDB](https://mariadb.com/) with its [TokuDB plugin](https://mariadb.com/kb/en/library/tokudb/) *(though MySQL may be enough for local development)*
* [Apache](https://httpd.apache.org/) 2.2+ with [mod_rewrite](http://httpd.apache.org/docs/current/mod/mod_rewrite.html) enabled
* [cURL](http://php.net/manual/en/intro.curl.php)
* [pdftotext](https://poppler.freedesktop.org/) *(from Poppler)*

* [IPFS](https://ipfs.io/ "InterPlanetary File System") *(optional)*
* [TOR](https://www.torproject.org/ "The TOR Project") *(optional)*


## Installation

1. Install dependencies: *(if MySQL is installed, it will be replaced by MariaDB)*
   ```bash
   sudo apt-get install php7.0 apache2 libapache2-mod-php mariadb-plugin-tokudb php-mcrypt php-mysql curl poppler-utils
   ```

2. Install TokuDB following [these instructions](https://mariadb.com/kb/en/library/enabling-tokudb/). 

3. OPTIONAL: Install IPFS following [these instructions](https://ipfs.io/docs/install/). Then enter:

   ```bash
   ipfs init
   ipfs daemon& 			# wait 3 seconds and press Ctrl+L to clear the screen
   ipfs cat /ipns/...... 		# shoud print something if IPFS is well configured
   ```

4. OPTIONAL: Install TOR following [these instructions](https://www.torproject.org/docs/debian.html.en).  
   
   Then edit ```/etc/tor/torrc```. Uncomment ```ControlPort 9051```, uncomment ```CookieAuthentication 0``` and set it to 1 (```CookieAuthentication 1```). Save and close. Then enter:  

   ```bash
   sudo service tor restart	 		# make sure TOR is running
   curl ifconfig.me/ip				# should print your real IP
   torify curl ifconfig.me/ip			# should print another IP
   print 'AUTHENTICATE ""\r\nsignal NEWNYM\r\nQUIT' | nc 127.0.0.1 9051
   torify curl ifconfig.me/ip 			# should print yet another IP
   ```

5. Clone this repository to a dedicated folder in your Apache working directory: (most probably ```/var/www```)

   ```
   mkdir /var/www/statemapper
   cd /var/www/statemapper
   git clone https://github.com/StateMapper/StateMapper
   ```

6. Edit ```config.php``` and change the constants according to your needs (follow the instructions in comments).

7. OPTIONAL: Create an ```smap``` alias to access the CLI API easily from anywhere. Enter:

   ```bash 
   echo 'alias smap="/var/www/statemapper/scripts/statemapper "' >> ~/.bashrc
   source ~/.bashrc		# read ~/.bashrc again
   smap				# should print the CLI help
   ```
   
   *Disclaimer: all ```smap``` calls require root login because PHP requires to be executed with the same user as the Apache server (most likely ```www-data```) to be able to read-write files correctly.*


8. Restart the web server and visit the Web GUI:

   ```bash
   sudo a2enmod rewrite		# enable Apache's mod_rewrite
   sudo service apache2 restart 	# make sure Apache is running
   sudo service mysql restart 	# make sure MySQL is running
   ```
   Then open a browser and navigate to http://localhost/statemapper/app/


## Daemon commands:

If you want the spiders to be able to start, it is required to start the daemon with ```smap daemon start```. Here are the available daemon commands:

```bash
smap daemon [start] 		# start the daemon in the background
smap daemon -d 			# start it in debug mode (do not daemonize)
smap daemon stop 		# stop it smoothly (wait for the workers)
smap daemon kill 		# kill it (for emergencies only)
```

-----

*[&larr; homepage](https://github.com/StateMapper/StateMapper#top) / StateMapper &copy; 2017 [StateMapper.net](https://statemapper.net) & [Ingoberlab](https://hacklab.ingobernable.net) / Licensed under [GNU GPLv3](../../COPYING).*
