# Partyception-Serverside
The repository for Partyception's serverside code. 

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

### Retrieving Players
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

### Kicking Players
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
