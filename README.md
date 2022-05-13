# Partyception-Serverside
The repository for Partyception's serverside code. 

The repository for Partyception's game/clientside code can be found here: https://github.com/Tenodru/Partyception

## Overview
This repository hosts all of the code and scripts located in the Partyception web database.
These scripts are primarily written in PHP, and handle all of the game's serverside functionality.
This code was developed by Alex Kong and Victor Do. Below are the main contributions each of us made to the repository, and a breakdown of how the code works to provide the serverside functionality needed for the game to function.

## File Overview
### Scripts
- `lobby.php` : This script primarily handles lobby functions, like creating, joining, and leaving lobbies.
- `question.php` : This script primarily handles question functions, like getting and sending questions to players.
- `updatePlayerStatus.php` : This script primarily handles player-specific functions, like determining if a player is answering, or is eliminated.
- `test.php` : Deprecated script initially used to test code.
### Directories
- `lobbies` : Stores Lobby folders.
### Other
- `error_log` : A text file used to store PHP errors. Used for development and testing.
- `test.txt` : A deprecated text file initially used to test code.

## Contributions and Breakdown - Alex
`lobby.php`

#### Retrieving Players
My work in this script began when we began tracking player statuses and developing dynamic lobbies. We needed a way to tell how many players were eliminated at any point in the game (primarily used at the end of a game), so I wrote a few functions that handle this.
```php
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
```
These functions grab the total number of players and the number of non-eliminated players, respectively. In the latter, because we don't have a specific "not eliminated" function, we simply grab all players and ignore those that have been marked as "eliminated". 
Since we purely use text files for tracking statuses and other data, we make extensive use of the `strpos()` function to determine whether certain keywords (like the keywords used for statuses) are located within specific lines or overall files.

```php
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
```
The first function creates a list of eliminated players and is called the first time a player is eliminated. The second function grabs this list, looking for players that were eliminated in the specified round. It then returns these players, or returns "" if no players were eliminated this round.
These functions are intended to make tracking and grabbing eliminated players easier, and were part of our move to shift calculations to serverside instead of clientside.


`updatePlayerStatus.php`

#### Kicking Players
I also wrote functions for `updatePlayerStatus.php` near the tail end of development, when we needed functionality for specific use cases. In particular, we needed to be able to "kick" players who were marked as disconnected - players whose clients don't send an end-of-round update to the server within a certain time limit, as determined by the game's clientside code.
```php
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
```
This function checks for players who are still marked as "answering" or even "prestart" at the end of a round. By default, the game client will send either "correct", or "eliminated" at once a round ends - if a player is still marked as "answering" or "prestart", then their client was paused, stopped, or otherwise lost connection to the server, so we will eliminate them.

### Other Contributions
I also made edits and tweaks to various other functions and code as needed during the development process of the game. 



## Contributions and Breakdown - Victor
My contribution on the server-side involved setting up the foundation of the system we would use to handle the server side of Partyception. After setting up the foundation, Alex and I worked together to add the neccessary functions we discovered that we needed as we developed our server side. Here is a compilation of most of my contribution that should also give an overview at how the website database works.

`lobby.php`

The following function creates the lobbies that streamers use to play games with their audience. Lobbies are created as folders that contain the data necessary to conduct the game, such as status files of each player, a lobby status file, and more. The function sets the player who creates the lobby as the leader of the lobby.

#### Creating Lobbies
```php
//CREATING LOBBY FUNCTION
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
 ```
 
 The following function is used by players to join an existing lobby. The function adds the player's name to a text file that tracks the number of players in a game, so that a player status file can be created once the game starts.
 
 #### Join Game Function
 ```php
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
 ```
 
 The following function fully creates the lobby when the host decides to start the game for their players and edit various settings. A player status folder is created with individual player status text files for each player, a minigame status file is created, and the lobby status changes to "start".
 
 #### Start Game Function
 ```php
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
 ```
 
 `question.php`
 
 The following function is run when a round is completed on the host's game, which updates the lobby status appropriately and eliminates player who timed out or got the question wrong.
 
 #### Complete Round Function
 
 ```php
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
 ```
 
 `updatePlayerStatus.php`
 
Since player status files are important for the host to understand what is going on with each player, player status files have to be updated whenever a player's current game status changes. The update status function is used on the players' games to edit their player status files in the web database, while the retrieve function is used by the host to retrieve their players' current status.
 
 #### Updating and Retrieving Player Status
 
 ```php
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
 ```

This function is run whenever a player chooses an answer in their game. Depending on whether the answer is correct or wrong, their player status will be changed as such and can possibly be eliminated when the round completes.
 
 #### Resolving Outcomes for Player Answers
 
 ```php
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
```

#### Other Contributions

The above snippets of code are not a comprehensive list of everything I contributed for Partyception, however it should provide a look into the foundation of how Partyception works on the server side and what Alex and I continued the build upon through the development of Partyception.
