registerController('ReconController', ['$api', '$scope', '$interval', '$timeout', function($api, $scope, $interval, $timeout) {

    $scope.error = false;
    $scope.error2 = false;
    $scope.accessPoints = [];
    $scope.unassociatedClients = [];
    $scope.interfaceMacs = [];

    $scope.scanDuration = '120';
    $scope.running = false;
    $scope.continuous = false;
    $scope.percent = 0;
    $scope.scanType = '0';
    $scope.configurationData = '';
    $scope.loading = false;
    $scope.cached = false;
    $scope.intruder = false;
    $scope.threshold = 0;

    if (window.recon_cache !== undefined) {
        $scope.cached = true;
        $scope.accessPoints = window.recon_cache[0];
        $scope.unassociatedClients = window.recon_cache[1];
        $scope.interfaceMacs = window.recon_cache[2];
    }

    $scope.startScan = function() {
        if ($scope.running) {
            return;
        }
        sendScanRequest();
    };

    $scope.getConfigurationData = (function(time, message) {
        $api.request({
            module: 'ReconPlus',
            action: 'getHostsData',
            delay: time,
            pmessage: message
        }, function(response) {
            $scope.configurationData = response.configurationData;
        });
    });
    $scope.getConfigurationData(0,"");

    $scope.deleteData = (function() {
        $api.request({
            module: 'ReconPlus',
            action: 'deleteData',
        }, function() {
            $scope.getConfigurationData(0,"Data deleted");
        });
    });

    $scope.deletePData = (function() {
        $api.request({
            module: 'ReconPlus',
            action: 'deletePData',
        }, function() {
            $scope.getConfigurationData(0,"");
        });
    });


    function checkScanStatus(id) {
        $scope.checkScanInterval = $interval(function() {
            $api.request({
                module: 'ReconPlus',
                action: 'scanStatus',
                scanID: id,
                intruder: $scope.intruder,
                threshold: $scope.threshold,
                percent: $scope.percent

            }, function(response) {

                if (response.error) {
                    //alert('error');
                    $scope.percent = 0;
                    $scope.stopScan(true);
                    $scope.error2 = true;                    
                }
                else if (response.completed === true) {
                    //alert('completed');
                    $scope.percent = 100;
                    parseScanResults(response);
                    $scope.stopScan(true);
                } else {
                    //alert('updating');
                    var percentage = Math.ceil(100 / ((($scope.scanDuration*1) + 2) / 5));
                    if ($scope.percent + percentage > 100) {
                        $scope.percent = 100;
                    } else {
                        $scope.percent += percentage;
                    }
                }
            });
        }, 5000);
    }

    function sendScanRequest() {
        $scope.loading = true;
        $api.request({
            module: 'ReconPlus',
            action: 'startScan',
            scanType: $scope.scanType,
            scanDuration: $scope.scanDuration
        }, function(response) {
            $scope.loading = false;
            if (response.success) {
                $scope.error = false;
                $scope.error2 = false;
                $scope.running = true;
                checkScanStatus(response.scanID);
            } else {
                $scope.error = true;
            }
        });
    }

    function parseScanResults(results) {
        $scope.cached = false;
        $scope.accessPoints = results.results.ap_list;
        $scope.unassociatedClients = results.results.unassociated_clients;
        $scope.interfaceMacs = results.interfaceMacs;
        window.recon_cache = [$scope.accessPoints, $scope.unassociatedClients, $scope.interfaceMacs];
    }

    $scope.stopScan = function(scripted) {
        $interval.cancel($scope.checkScanInterval);
        $scope.percent = 0;
        $scope.getConfigurationData(0,"Please wait while we parse the results...");
        $scope.getConfigurationData(8,'');
        if (scripted === undefined) {
            $scope.running = false;
        } else if ($scope.continuous) {
            sendScanRequest();
        } else {
            $scope.running = false;
        }
    };

    $scope.addAllSSIDS = function() {
        var ssidList = [];
        if ($scope.accessPoints.length != 0) {
            angular.forEach($scope.accessPoints, function(value, key) {
                if (value.ssid != "") {
                    ssidList.push(value.ssid);
                }
            });
            $api.request({
                module: "PineAP",
                action: "addSSIDs",
                ssids: ssidList
            }, function(response) {
                if (response.success) {
                    $scope.dropdownMessage = "All SSIDs added to Pool.";
                    $timeout(function() {
                        $scope.dropdownMessage = "";
                    }, 3000);
                }
            });
        }
    };

    $scope.$on('$destroy', function() {
        $interval.cancel($scope.checkScanInterval);
    });
}]);
