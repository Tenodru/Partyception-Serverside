<?
    header('Access-Control-Allow-Origin: *');
    //GET INFO FROM APP
    $function = $_REQUEST["function"];
    $lobbyNumber = $_REQUEST["lobbyNumber"];

    //GET LIST OF LOBBIES IN LOBBY FOLDER
    $lobbyFolder = scandir("lobbies");
    
    //RETURN LIST OF LOBBIES IN LOBBY FOLDER
    if ($_REQUEST["function"] == "getLobbyList") {
        echo file_get_contents("lobbies/lobbyList.txt");
    }

    //CREATE LOBBY FUNCTION
    if ($_REQUEST["function"] == "createLobby"){
        $playerName = $_REQUEST["playerName"];
        
        if (!in_array($lobbyNumber, $lobbyFolder))
        {
            //CREATING NEW LOBBY
            $newFolder = "lobbies/".$lobbyNumber;
            mkdir($newFolder);
            chmod($newFolder, 0777);
            
            //STATUS TRACKING FILE
            $txtfilePath = "lobbies/".$lobbyNumber."/"."lobbyStatus".".txt";
            $txtfile = fopen($txtfilePath, "w");
            $txt = "";
            fwrite($txtfile, $txt);
            fclose($txtfile);
            chmod($txtfilePath, 0777);
            
            //ADDING PLAYER AS LEADER
            $txtfilePath = "lobbies/".$lobbyNumber."/"."players".".txt";
            $txtfile = fopen($txtfilePath, "w");
            $txt = "";
            $txt .= "Leader: ".$playerName."\n";
            fwrite($txtfile, $txt);
            fclose($txtfile);
            chmod($txtfilePath, 0777);
            
            //ADD LOBBY TO LOBBYLIST.TXT
            $txtfilePath = "lobbies/lobbyList.txt";
            $txtfile = fopen($txtfilePath, "a");
            $txt = $lobbyNumber."/";
            fwrite($txtfile, $txt);
            fclose($txtfile);
            chmod($txtfilePath, 0777);
            
            if (!file_exists("lobbies/".$lobbyNumber."/"."eliminatedPlayerList".".txt")) 
            {
                $filePath = "lobbies/".$lobbyNumber."/"."eliminatedPlayerList".".txt";
                $file = fopen($filePath, "w");
                $txt = "";
                fwrite($file, $txt);
                fclose($file);
                chmod($filePath, 0777);
            }
            
            echo "lobby created";
        }
        else
        {
            echo "lobby already exists";
        }
    }
    
    //JOIN LOBBY FUNCTION
    if ($_REQUEST["function"] == "joinLobby")
    {
        $playerName = $_REQUEST["playerName"];
        
        //CHECK IF LOBBY EXISTS
        if (!in_array($lobbyNumber, $lobbyFolder))
        {
            echo "lobby does not exist";
        }
        else
        {
            //ADD PLAYER TO PLAYER TEXT FILE
            $txtfilePath = "lobbies/".$lobbyNumber."/"."players".".txt";
            $txtfile = fopen($txtfilePath, "a");
            $txt = "";
            $txt .= $playerName."\n";
            fwrite($txtfile, $txt);
            fclose($txtfile);
            echo "lobby successfully joined";
        }
    }
    
    //LEAVE LOBBY FUNCTION
    if ($_REQUEST["function"] == "leaveLobby")
    {
        $playerName = $_REQUEST["playerName"];
        
        //CHECK IF LOBBY EXISTS
        if (!in_array($lobbyNumber, $lobbyFolder))
        {
            echo "lobby does not exist";
        }
        else
        {
            //REMOVE PLAYER FROM PLAYER TEXT FILE
            $txtfilePath = "lobbies/".$lobbyNumber."/"."players".".txt";
            
            $lines = file_get_contents($txtfilePath);
            
            $search = $playerName."\n";
            
            //error_log(strpos($lines, "Leader: ".$playerName));
            if (strpos($lines, "Leader: ".$playerName) !== false){
                $isLeader = true;
                //error_log("leader");
            }
            else{
                $isLeader = false;
               // error_log("notleader");
            }
            
            if (strpos($lines, $search) != false){
                $lines = str_replace($search, "", $lines);
                file_put_contents($txtfilePath, $lines);
                echo "successfully removed player";
                
                if ($isLeader == true){
                    error_log("setting to abandon");
                    //CHANGE LOBBY STATUS TO ABANDON
                    $txtfilePath = "lobbies/".$lobbyNumber."/"."lobbyStatus".".txt";
                    $txtfile = fopen($txtfilePath, "w");
                    $txt = "abandon";
                    fwrite($txtfile, $txt);
                    fclose($txtfile);
                    chmod($txtfilePath, 0777);
                }
            }
            else
            {
                echo "could not find player";
            }
            
        }
    }
    
    //GET LIST OF PLAYERS FUNCTION
    if ($_REQUEST["function"] == "getPlayerList")
    {
        $txtfilePath = "lobbies/".$lobbyNumber."/"."players".".txt";
        $txtfile = fopen($txtfilePath, "r");
        echo file_get_contents($txtfilePath);
        fclose($txtfile);
    }
    
    //GET LIST OF NON-ELIMINATED PLAYERS FUNCTION
    if ($_REQUEST["function"] == "getRemainingPlayers")
    {
        $filePath = "lobbies/".$lobbyNumber."/"."playerStatus";
        $playerFiles = scandir($filePath);
        $playerCountTotal = count($playerFiles) - 2;
        $playerCount = 0;
        
        foreach(glob("lobbies/"."$lobbyNumber"."/playerStatus".'/*') as $file)
        {
            $line = file_get_contents($file);
            if (!strpos($line, "eliminated")){
                $playerCount += 1;
            }
        }
        
        echo $playerCount;
    }
    
    //CREATE LIST OF ELIMINATED PLAYERS
    if ($_REQUEST["function"] == "createEliminatedPlayersList")
    {
        $filePath = "lobbies/".$lobbyNumber."/"."playerStatus";
        $playerFiles = scandir($filePath);
        
        if (!file_exists("lobbies/".$lobbyNumber."/"."eliminatedPlayerList".".txt")) 
        {
            $filePath = "lobbies/".$lobbyNumber."/"."eliminatedPlayerList".".txt";
            $file = fopen($filePath, "w");
            $txt = "";
            fwrite($file, $txt);
            fclose($file);
            chmod($filePath, 0777);
            
            echo "success";
        }
        else {
            echo "file already exists";
        }
    }
    
    //GET LIST OF ELIMINATED PLAYERS THIS ROUND
    if ($_REQUEST["function"] == "getRoundEliminatedPlayers")
    {
        $roundNum = $_REQUEST["roundNum"];
        $playerList = "";
        $txt = "";
        
        $filePath = "lobbies/".$lobbyNumber."/"."eliminatedPlayerList".".txt";
        if ($roundNum != "1") {
            $txt = "\n";
        }
        
        foreach(glob("lobbies/"."$lobbyNumber"."/playerStatus".'/*') as $playerFile)
        {
            $line = file_get_contents($playerFile);
            if (strpos($line, "eliminated".".".$roundNum)){
                $txt += basename($playerFile, ".txt")."/";
            }
        }
        $txt = substr_replace($txt ,"", -1);
        file_put_contents($filePath, $txt,  FILE_APPEND | LOCK_EX);
        $playerList = str_replace("\n" ,"", $txt);
        
        echo $playerList;
    }
    
    //START GAME FUNCTION
    if ($_REQUEST["function"] == "startGame")
    {
        $playerName = $_REQUEST["playerName"];
        
        //CREATE PLAYER STATUS FOLDER
        $newFolder = "lobbies/".$lobbyNumber."/playerStatus";
        mkdir($newFolder);
        
        //CREATE INDIVIDUAL PLAYER STATUS TEXT FILES FOR EACH PLAYER IN FOLDER
        $playersPath = "lobbies/"."$lobbyNumber"."/"."players".".txt";
        $playersFile = fopen($playersPath, "r");
        if ($playersFile) {
            while (($line = fgets($playersFile)) !== false) {
                if (strpos($line, "Leader: ") !== false){
                    $playerStatusPath = "lobbies/"."$lobbyNumber"."/playerStatus/".$playerName."Status".".txt";
                    $playerStatusFile = fopen($playerStatusPath, "w");
                    $playerStatusTxt = "";
                    $playerStatusTxt .= $playerName.":awaiting";
                }
                else
                {
                    $playerStatusPath = "lobbies/"."$lobbyNumber"."/playerStatus/".trim($line)."Status".".txt";
                    $playerStatusFile = fopen($playerStatusPath, "w");
                    $playerStatusTxt = "";
                    $playerStatusTxt .= trim($line).":awaiting";
                }
                fwrite($playerStatusFile, $playerStatusTxt);
                fclose($playerStatusFile);
            }
            fclose($playersFile);
        } else {
            die("error opening file");
        }
        
        //CREATE MINIGAME STATUS TEXT FILE
        $txtfilePath = "lobbies/".$lobbyNumber."/"."questionStatus".".txt";
        $txtfile = fopen($txtfilePath, "w");
        $txt = "";
        fwrite($txtfile, $txt);
        fclose($txtfile);
        chmod($txtfilePath, 0777);
        
        //CHANGE LOBBY STATUS TO START
        $txtfilePath = "lobbies/".$lobbyNumber."/"."lobbyStatus".".txt";
        $txtfile = fopen($txtfilePath, "w");
        $txt = "start";
        fwrite($txtfile, $txt);
        fclose($txtfile);
        chmod($txtfilePath, 0777);
        
        echo "successfully started game";
    }
    
    //CHANGE LOBBY STATUS FUNCTION
    if ($_REQUEST["function"] == "changeStatus")
    {
        $newStatus = $_REQUEST["newStatus"];
        $txtfilePath = "lobbies/".$lobbyNumber."/"."lobbyStatus".".txt";
        $txtfile = fopen($txtfilePath, "w");
        fwrite($txtfile, $newStatus);
        fclose($txtfile);
        echo "successfully changed status";
    }
    
    //GET LOBBY STATUS FUNCTION
    if ($_REQUEST["function"] == "getStatus")
    {
        $txtfilePath = "lobbies/".$lobbyNumber."/"."lobbyStatus".".txt";
        echo file_get_contents($txtfilePath);
    }
    
    //DELETE LOBBY FOLDER AND ENTRY IN LOBBYLIST FUNCTION
    if ($_REQUEST["function"] == "deleteLobby")
    {
        // Delete lobby folder
        $dirPath = "lobbies/".$lobbyNumber;
        // Loop through lobby files one by one and delete
        foreach(glob($dirPath . '/*') as $file){
            if(is_dir($file)) 
            {
                foreach (glob($file.'/*') as $subfile) {
                    unlink($subfile);
                }
                rmdir($file);
            }
            else
                unlink($file);
        }
        rmdir($dirPath);
        
        // Delete lobby from lobbyList.txt
        $txtfilepath = "lobbies/lobbyList.txt";
        $txtfile = fopen($txtfilepath, "w");
        $contents = file_get_contents($txtfilepath);
        $removeString = str_replace($lobbyNumber."/", "", $contents);
        echo $removeString;
        fwrite($txtfile, $removeString);
        fclose($$txtfile);
    }
?>