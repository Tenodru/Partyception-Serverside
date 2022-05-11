<?
    header('Access-Control-Allow-Origin: *');
    //GET INFO FROM APP
    $function = $_REQUEST["function"];
    $lobbyNumber = $_REQUEST["lobbyNumber"];
    
    //START QUESTION FUNCTION
    if ($function == "startQuestion"){
        $questionID = $_REQUEST["questionID"];
        
        //STATUS TRACKING FILE
        $txtfilePath = "lobbies/".$lobbyNumber."/"."questionStatus".".txt";
        $txtfile = fopen($txtfilePath, "a");
        fwrite($txtfile, $questionID);
        fclose($txtfile);
        
        //SET ALL PLAYER STATUS TO ANSWERING
        foreach(glob("lobbies/"."$lobbyNumber"."/playerStatus".'/*') as $file)
        {
            $line = file_get_contents($file);
            if (strpos($line, "eliminated")){
                continue;
            }
            
            $playerStatus = explode(":", $line);
            $playerName = $playerStatus[0];
            
            $playerStatusPath = $file;
            $playerStatusFile = fopen($playerStatusPath, "w");
            fwrite($playerStatusFile, $playerName.":answering");
            fclose($playerStatusFile);
        }
        
        //UPDATE LOBBY STATUS
        $lobbyStatusPath = "lobbies/".$lobbyNumber."/"."lobbyStatus".".txt";
        $lobbyTxtFile = fopen($lobbyStatusPath, "w");
        fwrite($lobbyTxtFile, "questioning");
        fclose($lobbyTxtFile);
        
        echo "successfully started question";
    }
    
    //GET QUESTION FUNCTION
    else if ($function == "getQuestion")
    {
        $txtfilePath = "lobbies/".$lobbyNumber."/"."questionStatus".".txt";
        $txtfile = fopen($txtfilePath, "r");
        echo file_get_contents($txtfilePath);
        fclose($txtfile);
    }
    
    //COMPLETE ROUND FUNCTION
    else if ($function == "completeRound"){
        $roundNumber = $_REQUEST["roundNumber"];
        
        //UPDATE LOBBY STATUS
        $lobbyStatusPath = "lobbies/".$lobbyNumber."/"."lobbyStatus".".txt";
        $lobbyTxtFile = fopen($lobbyStatusPath, "w");
        fwrite($lobbyTxtFile, "completing");
        fclose($lobbyTxtFile);
        
        //ELIMINATE PLAYERS
        foreach(glob("lobbies/"."$lobbyNumber"."/playerStatus".'/*') as $file)
        {
            $line = file_get_contents($file);
            
            //SKIP IF PLAYER HAS ALREADY BEEN ELIMINATED
            if (strpos($line, "eliminated")){
                continue;
            }
            
            $playerStatus = explode(":", $line);
            $playerName = $playerStatus[0];
            $outcome = $playerStatus[1];
            
            if ($outcome == "correct")
            {
                $playerStatusPath = $file;
                $playerStatusFile = fopen($playerStatusPath, "w");
                fwrite($playerStatusFile, $playerName.":awaiting");
                fclose($playerStatusFile);
            }
            /*else if ($outcome == "eliminated")
            {
                $playerStatusPath = $file;
                $playerStatusFile = fopen($playerStatusPath, "w");
                fwrite($playerStatusFile, $playerName.":eliminated".".".$roundNumber);
                fclose($playerStatusFile);
            }*/
        }
        
        echo "successfully completed round";
    }
    
    //GET ELIMINATED PLAYERS
    else if ($function == "getEliminatedPlayers")
    {
        $roundNumber = $_REQUEST["roundNumber"];
        $eliminatedList = "";
        $playerCount = 0;
        
        foreach(glob("lobbies/"."$lobbyNumber"."/playerStatus".'/*') as $file)
        {
            $line = file_get_contents($file);
            $playerCount += 1;
            
            if (strpos($line, "eliminated.".$roundNumber) ){
                $playerStatus = explode(":", $line);
                $playerName = $playerStatus[0];
                $eliminatedList .= $playerName . "\n";
                error_log("Adding ".$playerName." to eliminatedList.", 0);
            }
            if (strpos($line, "eliminated.")) 
            {
                $playerCount -= 1;
            }
        }
        $final = $playerCount . "\n" . $eliminatedList;
        
        echo $final;
    }
?>