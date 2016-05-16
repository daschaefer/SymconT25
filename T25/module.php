<?

class T25 extends IPSModule
{
        
    public function Create()
    {
        parent::Create();
        
        // Public properties
        $this->RegisterPropertyString("T25IP", "");
        $this->RegisterPropertyInteger("T25Port", 80);
        $this->RegisterPropertyString("T25Protocol", "http");
        $this->RegisterPropertyBoolean("T25LogMode", false);

        // Private properties
        $this->RegisterPropertyString("T25LastEventJSON", "");
    }
    
    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // register hook
        $sid = $this->RegisterScript("Hook", "Hook", "<? //Do not delete or modify.\ninclude(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\ninclude(\"../modules/SymconT25/T25/module.php\");\n(new T25(".$this->InstanceID."))->ProcessHookData();");
        $this->RegisterHook("/hook/t25", $sid);
        IPS_SetHidden($sid, true);

        // create profiles
        $this->RegisterProfileIntegerEx("T25.DoorOpener", "LockOpen", "", "",       Array(  
                                                                                            Array(0, "Öffnen", "", -1)
                                                                                    ));

        // create media
        $cameraStream = @$this->GetIDForIdent("CameraStream");
        if($cameraStream == false) {
            $cameraStream = IPS_CreateMedia(3);
            IPS_SetIdent($cameraStream, "CameraStream");
            IPS_SetName($cameraStream, "T25 Kamera Stream");
            IPS_SetIcon($cameraStream, "Camera");
            IPS_SetParent($cameraStream, $this->InstanceID);
        }

        if(strlen(IPS_GetProperty($this->InstanceID, "T25IP")) > 0 && strlen(IPS_GetProperty($this->InstanceID, "T25Port")) > 0) {
            $url = IPS_GetProperty($this->InstanceID, "T25Protocol")."://".IPS_GetProperty($this->InstanceID, "T25IP").":".IPS_GetProperty($this->InstanceID, "T25Port")."/cgi-bin/faststream.jpg?stream=full";
            IPS_SetMediaFile($cameraStream, $url, false);
        }

        // create variables
        $lastEvent = $this->RegisterVariableString("lastEvent", "letzte Aktivität");
        IPS_SetIcon($lastEvent, "Alert");

        $doorOpener = $this->RegisterVariableInteger("DoorOpener", "Türsummer", "T25.DoorOpener");
        $this->EnableAction("DoorOpener");
    }

    public function RequestAction($Ident, $Value) 
    { 
        switch ($Ident) 
        { 
            case "DoorOpener":
                $this->OpenDoor();
                SetValue($this->GetIDForIdent($Ident), 0);
            break; 
        } 
    }

    

    // PUBLIC ACCESSIBLE FUNCTIONS
    public function OpenDoor()
    {
        $url = IPS_GetProperty($this->InstanceID, "T25Protocol")."://".IPS_GetProperty($this->InstanceID, "T25IP").":".IPS_GetProperty($this->InstanceID, "T25Port")."/control/rcontrol?action=customfunction&action=sigout&profile=~Door";
        file_get_contents($url);
    }

    public function GetLastEvent() {
        return json_decode(IPS_GetProperty($this->InstanceID, "T25LastEventJSON"));
    }

    public function ProcessHookData()
    {
        if($_IPS['SENDER'] == "Execute") {
            echo "This script cannot be used this way.";
            return;
        } else {
            if(isset($_GET) && isset($_GET['event'])) {
                $timestamp = time();

                $eventID = @$this->GetIDForIdent("event_".str_replace(array(' '), '_', $_GET['event']));
                if($eventID == false) {
                    $eventID = $this->RegisterVariableString("event_".str_replace(array(' '), '_', $_GET['event']), "Ereignis: ".$_GET['event']);
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


    // HELPER FUNCTIONS
    private function RegisterHook($Hook, $TargetID)
    {
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

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }
}

?>
