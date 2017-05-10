<?php namespace pineapple;

class ReconPlus extends SystemModule
{
    private $clientInterface = "wlan1";
    private $scanID = null;
    public function route()
    {
        switch ($this->request->action) {
            case 'scanStatus':
                $this->getScanStatus();
                break;

            case 'startScan':
                $this->startScan();
                break;

            case 'getHostsData':
                $this->getHostsData();
                break;

            case 'deleteData':
                $this->deleteData();
                break;

            case 'deletePData':
                $this->deletePData();
                break;
        }
    }

    private function startScan()
    {
        $this->scanID = rand(0, getrandmax());
        if (isset($this->request->scanType)) {
            if ($this->request->scanType > 2 || $this->request->scanType < 0) {
                $this->request->scanType = 0;
            }
            if (is_numeric($this->request->scanDuration)) {
                if ($this->request->scanDuration < 15 || $this->request->scanDuration > 600) {
                    $this->request->scanDuration = 15;
                }
            } else {
                $this->request->scanDuration = 15;
            }
            $this->startMonitorMode();
            $success = $this->scan($this->request->scanDuration, $this->request->scanType);
            $this->response = array("success" => $success, "scanID" => $this->scanID);
        } else {
            $this->response = array("success" => false);
        }
    }

    private function scan($duration, $type)
    {
        $cmd = "tcpdump -i {$this->clientInterface}mon -e -s 256 type mgt subtype probe-req > /tmp/probe-{$this->scanID}";
        exec("echo '{$cmd}' | at now");
        sleep(1);

        $cmd = "pinesniffer {$this->clientInterface}mon {$duration} {$type} /tmp/recon-{$this->scanID}";
        exec("echo '{$cmd}' | at now");
        sleep(1);
        return $this->checkRunning($cmd);
    }

    private function startMonitorMode()
    {
        if (empty(exec("ifconfig | grep {$this->clientInterface}mon"))) {
            exec("airmon-ng start {$this->clientInterface}");
        }
    }

    private function getScanStatus()
    {
        if (isset($this->request->scanID)) {
            if (file_exists("/tmp/recon-{$this->request->scanID}")) {
                $this->response = array(
                    "completed" => true,
                    "results" => $this->getScanResults(),
                    "interfaceMacs" => array(
                        $this->getMacFromInterface("wlan0"),
                        $this->getMacFromInterface("wlan0-1")
                    )
                );
                if (empty(exec("ps | grep [r]econc"))) {
                    exec("cp /pineapple/modules/ReconPlus/log/clientlist.txt /pineapple/modules/ReconPlus/log/clientlist.bak");
                    exec("cp /pineapple/modules/ReconPlus/log/probelist.txt /pineapple/modules/ReconPlus/log/probelist.bak");                    
                    exec("cp /pineapple/modules/ReconPlus/log/reconlog /pineapple/modules/ReconPlus/log/reconlog.bak");

                    exec("killall tcpdump");

                    //$cmd = "python /pineapple/modules/ReconPlus/script/probecombine.py -i /tmp/probe-{$this->request->scanID} -o /pineapple/modules/ReconPlus/log/probelist.txt > /pineapple/modules/ReconPlus/log/reconlog";
                    //exec("echo '{$cmd}' | at now");

                    $cmd = "python /pineapple/modules/ReconPlus/script/reconcombine.py -i {$this->request->scanID} -o /pineapple/modules/ReconPlus/log/clientlist.txt > /pineapple/modules/ReconPlus/log/reconlog";
                    exec("echo '{$cmd}' | at now");
                    }
                return;
            } elseif (isset($this->request->percent) && $this->request->percent == 100) {
                $scanID = intval($this->request->scanID);
                if ($scanID >= 10) {
                    $pid = exec("ps | grep /tmp/recon-{$scanID} | grep -v grep | awk '{print $1}'");
                    exec("kill -SIGALRM {$pid}");
                    exec("echo 'error' > /tmp/reconerror");
                }
            }
        }
        $this->response = array("completed" => false);
    }

    private function getScanResults()
    {
        sleep(1);
        $results = json_decode($this->removeBOM(file_get_contents("/tmp/recon-{$this->request->scanID}")));
        return $results;
    }

    // http://stackoverflow.com/a/31594983
    private function removeBOM($data)
    {
        if (0 === strpos(bin2hex($data), 'efbbbf')) {
            return substr($data, 3);
        } else {
            return $data;
        }
    }

    private function getHostsData()
    {
    		try {
    		        sleep($this->request->delay);
    		        if ($this->request->pmessage != ''){
    		            $this->response = array("configurationData" => $this->request->pmessage);
    		            return;
    		        }
    		        $configurationData = file_get_contents('/pineapple/modules/ReconPlus/log/reconlog');
    		        if (empty($configurationData)){
    		            $this->response = array("configurationData" => "Please click on the refresh button");
    		        }
    		        else{
                        $this->response = array("configurationData" => $configurationData);
    		        }
    		    }
            catch(Exception $e) {
                $this->response = array("configurationData" => "random error");
            }
    }

    private function deleteData()
    {
        exec("rm /pineapple/modules/ReconPlus/log/reconlog");
        exec("rm /pineapple/modules/ReconPlus/log/clientlist.txt");
        exec("rm /pineapple/modules/ReconPlus/log/probelist.txt");        
    }

    private function deletePData()
    {
        exec("cp /pineapple/modules/ReconPlus/log/clientlist.bak /pineapple/modules/ReconPlus/log/clientlist.txt");
        exec("cp /pineapple/modules/ReconPlus/log/probelist.bak /pineapple/modules/ReconPlus/log/probelist.txt");        
        exec("cp /pineapple/modules/ReconPlus/log/reconlog.bak /pineapple/modules/ReconPlus/log/reconlog");
    }


}
