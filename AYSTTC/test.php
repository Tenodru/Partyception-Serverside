<?php
    header('Access-Control-Allow-Origin: *');
    //GET INFO FROM APP
    //$function = "retrieve";
    
    if ($function == "update")
    {
        $lobbyNumber = $_REQUEST["lobbyNumber"];
        $playerName = $_REQUEST["playerName"];
        $newStatus = $_REQUEST["newStatus"];
        
        $txtfilePath = "lobbies/"."$lobbyNumber"."/playerStatus/".$playerName."Status".".txt";
        $txtfile = fopen($txtfilePath, "w");
        fwrite($playerStatusFile, $playerName.":".$newStatus);
        fclose($txtfile);
        
        echo "successfully updated player status";
    }
    else if ($function == "retrieve")
    {
        $lobbyNumber = "B0L0L";
        
        foreach(glob("lobbies/".$lobbyNumber."/playerStatus".'/*') as $file)
        {
            $line = file_get_contents($file);
            $pieces = explode(":", $line);
            if ($pieces[1] == "answering"){
                echo "someone is not ready";
                return;
            }
            
        }
        
        echo "everyone is ready";
    }
    else if ($function == "answerQuestion")
    {
        $lobbyNumber = $_REQUEST["lobbyNumber"];
        $playerName = $_REQUEST["playerName"];
        $outcome = $_REQUEST["outcome"];
        
        $txtfilePath = "lobbies/"."$lobbyNumber"."/playerStatus/".$playerName."Status".".txt";
        $txtfile = fopen($txtfilePath, "w");
        fwrite($playerStatusFile, $playerName.":".$outcome);
        fclose($txtfile);
        
        echo "player successfully answered";
    }
?>