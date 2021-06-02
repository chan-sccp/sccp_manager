## Welcome to Develop Chan_SCCP GUI Manager for FreePBX
| [English :gb:/:us:](README.md) | [Russian :ru:](README.ru.md) | [Previous Stable Releases](https://github.com/PhantomVl/sccp_manager/tree/master)

![Gif](https://github.com/chan-sccp/sccp_manager/raw/develop/.dok/image/Demo_1s5.gif)

  * [Installation](https://github.com/chan-sccp/sccp_manager#installation)
  * [Prerequisites](https://github.com/chan-sccp/sccp_manager#prerequisites)
  * [Links](https://github.com/chan-sccp/sccp_manager#link)
  * [Wiki](https://github.com/chan-sccp/sccp_manager/wiki)

## Link

[![Download Sccp-Mamager](https://img.shields.io/badge/SccpGUI-build-ff69b4.svg)](https://github.com/chan-sccp/sccp_manager/archive/tarball/develop)
[![Download Chan-SCCP channel driver for Asterisk](https://img.shields.io/sourceforge/dt/chan-sccp-b.svg)](https://github.com/chan-sccp/chan-sccp/releases/latest)
[![Chan-SCCP Documentation](https://img.shields.io/badge/docs-wiki-blue.svg)](https://github.com/chan-sccp/chan-sccp/wiki)

This module has been developed to help IT Staff with their Asterisk-Cisco infrastructure deployment,
providing easy provisioning and management of Cisco IP phones and extensions in a similar way to Cisco CallManager.
The idea of creating a module is borrowed from (https://github.com/Cynjut/SCCP_Manager), and was further developed and managed
by PhantomVl (https://github.com/PhantomVl/sccp_manager) who has been unavailable for some time. sccp_manager relies heavily
on chan-sccp, so having the same repository will help improve integration.

SCCP Manager is free software. Please see the file COPYING for details.

This module will suit you if you are planing the to migrate from CallManager to Asterisk (or have already done it). SCCP-Manager allows you to administer
SCCP extensions and a wide range of Cisco phone types (including IP Communicator).
You can control phone buttons (depending on the phone model) assigning multiple lines, speeddials and BLF’s.
And you can use the driver functions "sccp_chain" from the GUI module.

### Wiki
You can find more information and documentation on our [![SCCP Manager Wiki](https://img.shields.io/badge/Wiki-new-blue.svg)](https://github.com/chan-sccp/wiki)

## Prerequisites
Make sure you have the following installed on your system:
- gui:
  - freepbx >= 13.0.192
- a working version of [chan-sccp](https://github.com/chan-sccp/chan-sccp)
- PHPx.x-zip has to be installed (where x.x is the installed version of PHP).
  For example, on Debian, using PHP7.3
```
apt-get install PHP7.3-zip
```

### Requirements
- Chan_SCCP module 4.3.4 (or later) channel driver for Asterisk: [See our WIKI](https://github.com/chan-sccp/chan-sccp/wiki/Building-and-Installation-Guide)
  - sccp_manager expects these configure flags to be set during compilation:
    ```./configure  --enable-conference --enable-advanced-functions --enable-distributed-devicestate --enable-video```
  - Creating mysql DB from sorce
    ```mysql -u root asterisk < mysql-v5_enum.sql```


- TFTP Server running under (recommended) /tftpboot/ [See our WIKI] (https://github.com/chan-sccp/chan-sccp/wiki/setup-tftp-service)
  - You will need the phone settings templates. You can use the templates taken from the distribution "chan-sccp"
    ```cp /usr/src/chan-sccp/conf/tftp/\*.xml\* /tftpboot/templates/```

- configure DHCP server [See our WIKI](https://github.com/chan-sccp/chan-sccp/wiki/setup-dhcp-service)
 Important!


### Setup
- [Setting up a FreePBX system](http://wiki.freepbx.org/display/FOP/Install+FreePBX)
- [Setting up Chan-Sccp](https://github.com/chan-sccp/chan-sccp/wiki/How-to-setup-the-chan_sccp-Module)
- The sccp_manager module will automatically setup and configure asterisk realtime database for chan-sccp.
  For more information about realtime [See chan-sccp wiki](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration).

## Installation

How to install sccp_manager
-----

1. Creating mysql DB from source using
```
mysql -p asterisk < /usr/src/chan-sccp/conf/mysql-v5_enum.sql
```
2. Create a new tab and log in to FreePBX
3. Go to Admin -> Module Admin
4. Click Upload Modules.
5. Enter one of the following urls:

Stable
```
https://github.com/chan-sccp/sccp_manager/tarball/develop/chan-sccp-sccp_manager-legacy-1-g9ef59f0.tar
```
Develop. _This module can be updated through module admin as modifications are made, but may still have issues_
```
https://github.com/chan-sccp/sccp_manager/archive/refs/heads/develop.zip
```
6. Click Download From Web.
7. Click Manage Local Modules.
8. Find and click SCCP Manager. Check Install. Click Process button.
9. Confirm installation.
10. Close Status window.
11. Apply Config to FreePBX.
12. Continue to [Using-SCCP_Manager-to-Manage-chan-sccp](https://github.com/chan-sccp/chan-sccp/wiki/Using-SCCP_Manager-to-Manage-chan-sccp)

### Module update to latest state

If you installed the Develop branch, the module can be updated to the latest version via FreePBX. FreePBX -> Admin -> Module Admin.


### IMPORTANT NOTES:
- !!! If something stops working, use the develop branch [develop](https://github.com/chan-sccp/sccp_manager/tree/develop)
- This system assumes/requires that you are using the Asterisk realtime database. If you are not yet using the realtime database,
you will have to set it up for this module to work ([See](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration)).
- For the cisco phones to work correctly, they should be provisioned with the latest firmware (v8.1 or higher)
- You can use cisco language profiles (localization) to switch the phones to your locale.

### Chat
[![Gitter](https://badges.gitter.im/chan-sccp/chan-sccp.svg)](https://gitter.im/sccp_manager/community)
