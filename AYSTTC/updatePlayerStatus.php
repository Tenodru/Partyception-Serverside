<?php
    header('Access-Control-Allow-Origin: *');
    //GET INFO FROM APP
    $function = $_REQUEST["function"];
    
    if ($function == "update")
    {
        $lobbyNumber = $_REQUEST["lobbyNumber"];
        $playerName = $_REQUEST["playerName"];
        $newStatus = $_REQUEST["newStatus"];
        
        $txtfilePath = "lobbies/"."$lobbyNumber"."/playerStatus/".$playerName."Status".".txt";
        
        if (strpos(file_get_contents($txtfilePath), "eliminated") !== false){
            return;
        }
        
        $txtfile = fopen($txtfilePath, "w");
        
        fwrite($txtfile, $playerName.":".$newStatus);
        fclose($txtfile);
        
        echo "successfully updated player status";
    }
    else if ($function == "retrieve")
    {
        $lobbyNumber = $_REQUEST["lobbyNumber"];
        
        foreach(glob("lobbies/".$lobbyNumber."/playerStatus".'/*') as $file)
        {
            $line = file_get_contents($file);
            $pieces = explode(":", $line);
            if ($pieces[1] == "answering" || $pieces[1] == "prestart"){
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
        
        if (strpos(file_get_contents($txtfilePath), "eliminated") !== false){
            return;
        }
        
        $txtfile = fopen($txtfilePath, "w");
        fwrite($txtfile, $playerName.":".$outcome);
        fclose($txtfile);
        
        echo "player successfully answered";
    }
    else if ($function == "kick")
    {
        $lobbyNumber = $_REQUEST["lobbyNumber"];
        $question = $_REQUEST["question"];
        
        foreach(glob("lobbies/".$lobbyNumber."/playerStatus".'/*') as $file)
        {
            $line = file_get_contents($file);
            $pieces = explode(":", $line);
            if ($pieces[1] == "answering" || $pieces[1] == "prestart"){
                echo "someone is not ready";
                $playerName = $pieces[0];
                $txtfilePath = "lobbies/"."$lobbyNumber"."/playerStatus/".$playerName."Status".".txt";
                $txtfile = fopen($txtfilePath, "w");
                fwrite($txtfile, $playerName.":"."eliminated".".".$question);
            }
        }
    }
    else if ($function == "checkIfEliminated")
    {
        $lobbyNumber = $_REQUEST["lobbyNumber"];
        $playerName = $_REQUEST["playerName"];
        
        $txtfilePath = "lobbies/"."$lobbyNumber"."/playerStatus/".$playerName."Status".".txt";
        
        if (strpos(file_get_contents($txtfilePath), "eliminated") !== false)
        {
            echo "player has been eliminated";
        }
        else
        {
            echo "player has not been eliminated";
        }
    }
?>