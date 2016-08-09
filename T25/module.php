<?

require_once('simple_html_dom.php');

class T25 extends IPSModule
{
        
    public function Create() {
        parent::Create();
        
        // Public properties
        $this->RegisterPropertyString("T25IP", "");
        $this->RegisterPropertyInteger("T25Port", 80);
        $this->RegisterPropertyString("T25Username", "");
        $this->RegisterPropertyString("T25Password", "");
        $this->RegisterPropertyString("T25Protocol", "http");
        $this->RegisterPropertyBoolean("T25LogMode", false);
        $this->RegisterPropertyString("T25HookUsername", "t25");
		$this->RegisterPropertyString("T25HookPassword", $this->GeneratePassphrase(18));
        $this->RegisterPropertyString("T25CameraPictureFolderName", "");
        $this->RegisterPropertyInteger("T25CameraPictureAmount", 5);

        // Private properties
        $this->RegisterPropertyString("T25LastEventJSON", "");
    }
    
    public function ApplyChanges() {
        parent::ApplyChanges();
        
        // register hook
        $sid = $this->RegisterScript("Hook", "Hook", "<? //Do not delete or modify.\ninclude(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\ninclude(\"../modules/SymconT25/T25/module.php\");\n(new T25(".$this->InstanceID."))->ProcessHookData();");
        $this->RegisterHook("/hook/t25", $sid);
        IPS_SetHidden($sid, true);

        // create profiles
        $this->RegisterProfileIntegerEx("T25.DoorOpener", "LockOpen", "", "",       Array(  
                                                                                            Array(0, "Öffnen", "", -1)
                                                                                    ));

        // create stream objects
        $cameraStream = @$this->GetIDForIdent("CameraStream");
        if($cameraStream == false) {
            $cameraStream = IPS_CreateMedia(3);
            IPS_SetIdent($cameraStream, "CameraStream");
            IPS_SetName($cameraStream, "Kamera Stream");
            IPS_SetIcon($cameraStream, "Camera");
            IPS_SetParent($cameraStream, $this->InstanceID);
        }

        if(strlen(IPS_GetProperty($this->InstanceID, "T25IP")) > 0 && strlen(IPS_GetProperty($this->InstanceID, "T25Port")) > 0) {
            $url = $this->GetConnectionString()."/cgi-bin/faststream.jpg?stream=full";

            IPS_SetMediaFile($cameraStream, $url, false);
        }

        // create popups
        $cameraEventListPopUp = @$this->GetIDForIdent("CameraEventListPopUp");
        if(!$cameraEventListPopUp) {
            $cameraEventListPopUp = IPS_CreateInstance("{5EA439B8-FB5C-4B81-AA35-1D14F4EA9821}");
            IPS_SetName($cameraEventListPopUp, "Gespeicherte Kamerabilder anzeigen");
            IPS_SetIdent($cameraEventListPopUp, "CameraEventListPopUp");
            IPS_SetIcon($cameraEventListPopUp, "Camera");
            IPS_SetParent($cameraEventListPopUp, $this->InstanceID);
        }
        
        // create variables
        $cameraEventListHTML = @IPS_GetObjectIDByIdent("CameraEventListHTML", $cameraEventListPopUp);
        if(!$cameraEventListHTML) {
            $cameraEventListHTML = IPS_CreateVariable(3);
            IPS_SetIdent($cameraEventListHTML, "CameraEventListHTML");
            IPS_SetVariableCustomProfile($cameraEventListHTML, "~HTMLBox");
            IPS_SetParent($cameraEventListHTML, $cameraEventListPopUp);
        }
        IPS_SetName($cameraEventListHTML, "die letzten ".IPS_GetProperty($this->InstanceID, "T25CameraPictureAmount")." Ereignisse");
        
        

        $lastEvent = $this->RegisterVariableString("lastEvent", "letzte Aktivität");
        IPS_SetIcon($lastEvent, "Alert");

        $doorOpener = $this->RegisterVariableInteger("DoorOpener", "Türsummer", "T25.DoorOpener");
        $this->EnableAction("DoorOpener");

        // Setting timers
        $this->RegisterTimer('timer_updatedata', 300000, 'T25_UpdateData($_IPS[\'TARGET\']);'); 
    }

    public function RequestAction($Ident, $Value) { 
        switch ($Ident) 
        { 
            case "DoorOpener":
                $this->OpenDoor();
                SetValue($this->GetIDForIdent($Ident), 0);
            break; 
        } 
    }
    

    // PUBLIC ACCESSIBLE FUNCTIONS
    public function Test() {
        $this->UpdateData();
    }

    public function UpdateData() {
        $this->LoadCameraInfo();
        $this->LoadCameraEventPictures();
    }

    public function OpenDoor() {
        $url = $this->GetConnectionString()."/control/rcontrol?action=customfunction&action=sigout&profile=~Door";
        
        file_get_contents($url);
    }
    
    public function Hangup() {
        $url = $this->GetConnectionString()."/control/rcontrol?action=voiphangup";
        
        file_get_contents($url);
    }
    
    public function LEDsOn() {
        $url = $this->GetConnectionString()."/control/rcontrol?action=ledson";

        file_get_contents($url);
    }
    
    public function LEDsOff() {
        $url = $this->GetConnectionString()."/control/rcontrol?action=ledsoff";
        
        file_get_contents($url);
    }

    public function GetLastEvent() {
        return json_decode(IPS_GetProperty($this->InstanceID, "T25LastEventJSON"));
    }

    public function ProcessHookData() {
        if($_IPS['SENDER'] == "Execute") {
            echo "This script cannot be used this way.";
            return;
        } else {
            if((IPS_GetProperty($this->InstanceID, "T25HookUsername") != "") || (IPS_GetProperty($this->InstanceID, "T25HookPassword") != "")) {
				if(!isset($_SERVER['PHP_AUTH_USER']))
					$_SERVER['PHP_AUTH_USER'] = "";
				if(!isset($_SERVER['PHP_AUTH_PW']))
					$_SERVER['PHP_AUTH_PW'] = "";
					
				if(($_SERVER['PHP_AUTH_USER'] != IPS_GetProperty($this->InstanceID, "T25HookUsername")) || ($_SERVER['PHP_AUTH_PW'] != IPS_GetProperty($this->InstanceID, "T25HookPassword"))) {
					header('WWW-Authenticate: Basic Realm="Geofency WebHook"');
					header('HTTP/1.0 401 Unauthorized');
					echo "Authorization required";
					return;
				}
			}
            
            if(isset($_GET) && isset($_GET['event'])) {
                $timestamp = time();

                $identReplaceChars = array(' ', ',', '-', '.', ':', ';', '+', '*', '~', '!', '?', '/', '\\', '[', ']', '{', '}', '&', '%', '$', '§', '\"', '\'', '=', '´', '`', '<', '>', '|', '#');
                
                $eventID = @$this->GetIDForIdent("event_".str_replace($identReplaceChars, '_', $_GET['event']));
                if($eventID == false) {
                    $eventID = $this->RegisterVariableString("event_".str_replace($identReplaceChars, '_', $_GET['event']), "Ereignis: ".$_GET['event']);
                    IPS_SetIcon($eventID, "Hourglass");
                    IPS_SetHidden($eventID, true);
                }
                SetValue($eventID, $timestamp);

                SetValue($this->GetIDForIdent("lastEvent"), $_GET['event']." am ".date("d.m.Y H:i:s", $timestamp));

                $data = array("event" => $_GET['event'], "timestamp" => date("d.m.Y H:i:s", $timestamp), "unix_timestamp" => $timestamp);
                @IPS_SetProperty($this->InstanceID, "T25LastEventJSON", json_encode($data));

                if(IPS_GetProperty($this->InstanceID, "T25LogMode") == true)
                    IPS_LogMessage(IPS_GetObject($this->InstanceID)['ObjectName'], "Ereignis ausgelöst: ".$_GET['event']);
            }
        }
    }
    
    public function GenerateNewHookPassword() {
        $password = $this->GeneratePassphrase(18);
        IPS_SetProperty($this->InstanceID, "T25HookPassword", $password);
    }

    public function LoadCameraEventPictures() {
        $amount = IPS_GetProperty($this->InstanceID, "T25CameraPictureAmount");
        
        $path = IPS_GetKernelDir();
        if(PHP_OS == "WINNT") {
            $path .= "\\webfront\\user\\";
        }
        else {
            $path .= "/webfront/user/";
        }
        $itemList = $this->GetCameraPictureList($path.IPS_GetProperty($this->InstanceID, "T25CameraPictureFolderName"), $amount);
        SetValue(IPS_GetObjectIDByIdent("CameraEventListHTML", IPS_GetObjectIDByIdent("CameraEventListPopUp", $this->InstanceID)), $this->BuildCameraEventOverview($itemList));
    }


    // HELPER FUNCTIONS
    private function GetConnectionString() {
        $conn = null;
        
        if(strlen(IPS_GetProperty($this->InstanceID, "T25Username")) > 0 && strlen(IPS_GetProperty($this->InstanceID, "T25Password")) > 0) {
            $conn = IPS_GetProperty($this->InstanceID, "T25Protocol")."://".IPS_GetProperty($this->InstanceID, "T25Username").":".IPS_GetProperty($this->InstanceID, "T25Password")."@".IPS_GetProperty($this->InstanceID, "T25IP").":".IPS_GetProperty($this->InstanceID, "T25Port");
        } else {
            $conn = IPS_GetProperty($this->InstanceID, "T25Protocol")."://".IPS_GetProperty($this->InstanceID, "T25IP").":".IPS_GetProperty($this->InstanceID, "T25Port");
        }
        
        return $conn;
    }
    
    private function RegisterHook($Hook, $TargetID) {
        $ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
        if(sizeof($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
            $found = false;
            foreach($hooks as $index => $hook) {
                if($hook['Hook'] == $Hook) {
                    if($hook['TargetID'] == $TargetID)
                        return;
                    $hooks[$index]['TargetID'] = $TargetID;
                    $found = true;
                }
            }
            if(!$found) {
                $hooks[] = Array("Hook" => $Hook, "TargetID" => $TargetID);
            }
            IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

    

    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        
    }

    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }

    protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 0);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 0)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);  
    }
    
    protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }

    protected function GetParent() {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }
    
    protected function GeneratePassphrase($length) {
        $passphrase = "";
            $chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '!', '$', '#', '-', '_');
            $charLastIndex = 0;
            for($i=0; $i < $length; $i++) {
               $randIndex = rand(0, (count($chars)-1));
               while (abs($randIndex - $charLastIndex) < 10) {
                   $randIndex = rand(0, (count($chars)-1));
               }
               $charLastIndex = $randIndex;
               $passphrase .= $chars[$randIndex];
            }
        
        return $passphrase;
    }

    protected function BuildCameraEventOverview($list) {
        $HTML = "";

        if(is_array($list) && count($list) > 0) {
            foreach ($list as $listItem) {
                if(count($listItem) == 1) { // is dir
                    $HTML .= $this->BuildCameraEventOverview($listItem);
                }
                else if(count($listItem) > 1) { // is filelist
                    

                    foreach ($listItem as $path) {
                        foreach ($path as $key => $value) { // key = path to file; value = array(filename)
                            foreach($value as $image) {
                                if(is_array($image))
                                    continue;
                                    
                                if(PHP_OS == "WINNT") {
                                    $webfrontPath = "\\webfront";
                                }
                                else {
                                    $webfrontPath = "/webfront";
                                }

                                $key = str_replace(IPS_GetKernelDir().$webfrontPath,"",$key);
                                $HTML .= "<img style=\"width: 100%; height: auto;\" src=\"".$key."/".$image."?p=".time()."\">";
                            }
                        }
                    }
                }    
            }
        }

        
        return $HTML;
    }

    protected function GetCameraPictureList($path, $maxDepth=5) {
        $items = array();

        $excludeList = array(".", "..", "INFO.jpg", "log.txt", "Thumbs.db");

        $files = preg_grep('/^([^.])/', scandir($path, SCANDIR_SORT_DESCENDING)); // open $path excluding hidden files

        $i = 0;
        foreach ($files as $file) {
            if (!in_array($file, $excludeList)) {
                if(is_dir($path."/".$file)) {
                    if($i == $maxDepth)
                         break;
                    
                    $items[$path][] = $this->GetCameraPictureList($path."/".$file, $maxDepth);
                    $i++;
                }
                else {
                    if($file == "E00000.jpg")
                        $items[$path][] = $file;
                }
            }
        }

       return $items;
    }

    protected function LoadCameraInfo() {
        $url = $this->GetConnectionString()."/control/camerainfo";
        
        $dom = file_get_html($url);
        
        if($dom !== FALSE) {
            foreach($dom->find('#sensors') as $tbody) {
                foreach($tbody->find('tr') as $tr) {
                    $replace = array('&deg;C', '&deg;F', 'lux', '(High)');

                    $key = trim($tr->children(0)->innertext);
                    $value = trim($tr->children(1)->innertext);

                    if(strpos($value, '(') !== FALSE) {
                        $ex = explode('(', $value);
                        $value = trim($ex[0]);
                    }

                    if(strpos($value, "&deg;C") !== FALSE) { // temperature celsius
                        $profile = "~Temperature"; 
                        $type = 2; // float
                    } else if(strpos($value, "lux") !== FALSE) { // illumination lux
                        $profile = "~Illumination.F"; 
                        $type = 2; // float
                    } else {
                        $profile = "~String";
                        $type = 3;
                    }

                    $value = str_replace($replace, '', $value);
                    
                    $identReplaceChars = array(' ', ',', '-', '.', ':', ';', '+', '*', '~', '!', '?', '/', '\\', '[', ']', '{', '}', '&', '%', '$', '§', '\"', '\'', '=', '´', '`', '<', '>', '|', '#');
                    $Ident = "sensor_".strtolower(str_replace($identReplaceChars, "_", $key));

                    $Variable = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
                    if(!$Variable) {
                        $Variable = IPS_CreateVariable($type);
                        IPS_SetIdent($Variable, $Ident);
                        IPS_SetName($Variable, $key);
                        IPS_SetVariableCustomProfile($Variable, $profile);
                        IPS_SetParent($Variable, $this->InstanceID);
                    }

                    SetValue($Variable, $value);
                }
            }
            
        }
    }
}

?>
