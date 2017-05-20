import json
import os
import sys
import getopt
import time
import datetime
from collections import OrderedDict

def main(argv):
	inputfile = ''
	outputfile = '/tmp/clientlist.txt'
	#mode == 0 represents intruder mode is turned off
	#mode == 1 represents intruder mode is turned on
	mode = '0'
	threshold = 0
	try:
		opts, args = getopt.getopt(argv,"hi:o:m:t:",["ifile=","ofile=","mode=","threshold="])
	except getopt.GetoptError:
		print 'test.py -i <inputfile> -o <outputfile> -m <intruder mode>, -t <threshold>'
		sys.exit(2)
	for opt, arg in opts:
		if opt == '-h':
			print 'test.py -i <Reconfile> -o <outputfile>'
			sys.exit()
		elif opt in ("-i", "--ifile"):
			inputfile = arg
		elif opt in ("-o", "--ofile"):
			outputfile = arg
		elif opt in ("-m", "--mode"):
			mode = arg			
		elif opt in ("-t", "--threshold"):
			threshold = arg					
	#print 'Recon Input file is ', inputfile
	#print 'Output file is located at', outputfile


	uniqueSSID = set()
	timestamp = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')

	with open('/tmp/probe-' + inputfile) as f:    

		for line in f:
			line = line.strip("\n")
			if line != '':
				ssid = line.split(' ')[19].strip("(").strip(")")
				if ssid !='':
					ssid = ssid + '\t' + timestamp
					uniqueSSID.add(ssid)
			

	clientfile = open('/pineapple/modules/ReconPlus/log/probelist.txt', 'a+')
	for i in uniqueSSID:
		txt = i + '\n'
		clientfile.write(txt)
	clientfile.close()			

	#read the clientlist file and find targets who appeared
	#at different timestamp
	clientlist = {}
	maclist = []
	uniqueMac = set()
	datapoint = set()
	suspectMac = set()
	safeMac = set()
	count = 0
	with open('/pineapple/modules/ReconPlus/log/probelist.txt') as f:
			for line in f:
					line = line.strip("\n")
					mac,time = line.split('\t')
					clientlist[mac] = time
					maclist.append(mac)
					uniqueMac.add(mac)
					datapoint.add(time)
					count = count + 1

	#for i in clientlist:
	#        print i, clientlist[i]
	if mode == '0':
		print("========================")
		print("=Comparison using SSID =")
		print("========================")
		for i in uniqueMac:
				#print str(maclist.count(i)) + "\n"
				if maclist.count(i) > 1:
						print str(maclist.count(i)) + " instant of SSID ; " + i
				suspectMac.add(i)

		print("\n SSID List: ")
		for i in suspectMac:
			print i

		print("\n Summary: ")
		print("Using data from " + str(len(datapoint)) + " distinct scan\n")


	with open('/tmp/recon-' + inputfile) as data_file:    
		data = json.load(data_file)

	clients = {}

	for y in data['ap_list']:
		if y['clients']:
			for x in y['clients']:	
				clients[x] = timestamp
				#print (x)
	for y in data['unassociated_clients']:
		#print (y)
		clients[y] = timestamp

	sortedClients = OrderedDict(sorted(clients.items(), key=lambda t: t[0]))
		
	clientfile = open(outputfile, 'a+')
	for y in sortedClients:
		#print sortedClients[y]
		txt = y + '\t' + sortedClients[y] + '\n'
		clientfile.write(txt)
	clientfile.close()

	#read the clientlist file and find targets who appeared
	#at different timestamp
	clientlist = {}
	maclist = []
	uniqueMac = set()
	datapoint = set()
	suspectMac = set()
	count = 0
	with open('/pineapple/modules/ReconPlus/log/clientlist.txt') as f:
			for line in f:
					line = line.strip("\n")
					mac,time = line.split('\t')
					clientlist[mac] = time
					maclist.append(mac)
					uniqueMac.add(mac)
					datapoint.add(time)
					count = count + 1

	#for i in clientlist:
	#        print i, clientlist[i]
	if mode == '0':	
		print("\n===============================")	
		print("=Comparison using MAC Address =")
		print("===============================")
		for i in uniqueMac:
				#print str(maclist.count(i)) + "\n"
				if maclist.count(i) > 1:
						print str(maclist.count(i)) + " instant of MAC address; " + i
				suspectMac.add(i)

		print("\n Suspect List: ")
		for i in suspectMac:
			print i

		print("\n Summary: ")
		print("\n" + str(count) + " MAC address scanned")
		print("Using data from " + str(len(datapoint)) + " distinct scan\n")

	if mode == '1':	
		print("MAC Addresses who appeared at least " + str(threshold) + " time(s) are considered safe")
		print("Suspicious MAC Addresses are listed below:\n")
		for i in uniqueMac:
				#print str(maclist.count(i)) + "\n"
				if maclist.count(i) < int(threshold):
					print str(maclist.count(i)) + " instant of MAC address; " + i
					suspectMac.add(i)
				else:
					safeMac.add(i)					

		print("\n Suspect List: ")
		for i in suspectMac:
			print i

		print("\n Safe List: ")
		for i in safeMac:
			print i			


if __name__ == "__main__":
   main(sys.argv[1:])
