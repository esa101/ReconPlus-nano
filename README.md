A WiFi Pineapple module that uses MAC address to help find out who is stalking you by correlating MAC addresses from the results of recon scans.
This module remembers previously scanned MAC address and highlights thoses that were found in multiple scans. Works best with 3 or more datapoint/distinct scan. 

Modified using the base RECON module. 



Installation instruction to SD card: 
Upload the whole folder to pineapple /sd/module

remember to create the softlink. 
ln -s /sd/modules/ReconPlus /pineapple/module/ReconPlus


New in version 3.0:
SSID tracking
New in version 3.1:
Fixed 100% recon stuck error

