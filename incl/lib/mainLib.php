<?php

    /*1.9 GDPS Core: this server was written by Lila2048. Original source: https://github.com/Lila2048/1.9-gdps-core .Please do not remove this watermark. If you wish to use this software, please credit Lila2048 for the original server.*/
    class MainLib {
        public function logAction($type, $value1 = 0, $value2 = 0, $value3 = 0, $value4 = 0) {
            $ip = $_SERVER['REMOTE_ADDR'];
            require __DIR__ . "/connection.php";
            $sql = $conn->prepare("INSERT INTO actions (type, value1, value2, value3, value4, ip, timestamp) VALUES (:type, :value1, :value2, :value3, :value4, :ip, UNIX_TIMESTAMP())");
            $sql->execute([':type' => $type, ':value1' => $value1, ':value2' => $value2, ':value3' => $value3, ':value4' => $value4, ':ip' => $ip]);
            return 0;
        }

        public function checkPerms($permLevel, $udid) {
            require __DIR__ . "/connection.php";
            $sql = $conn->prepare("SELECT permLevel FROM users WHERE udid = :udid");
            $sql->execute([':udid' => $udid]);

            $result = $sql->fetchColumn();

            if($result >= $permLevel) {
                return 1;
            } else {
                return 0;
            }
        }
        public function rateLevel($levelID, $stars, $featured = 0) {

            require __DIR__ . "/DiscordWebhook.php";
            require __DIR__ . "/../../config/webhooks.php";
            require __DIR__ . "/../../config/main.php";
            $dw = new DiscordWebhook($levelRateWebhook);

            $diff = 0;
            $auto = 0;
            $demon = 0;
            $face = 0;
            require __DIR__ . "/connection.php";
            switch($stars) {
                case 1:
                    $auto = 1;
                    $diff = 50;
                    $face = 1;
                    break;
                case 2:
                    $diff = 10;
                    $face = 2;
                    break;
                case 3:
                    $diff = 20;
                    $face = 3;
                    break;
                case 4:
                case 5:
                    $diff = 30;
                    $face = 4;
                    break;
                case 6:
                case 7:
                    $diff = 40;
                    $face = 5;
                    break;
                case 8:
                case 9:
                    $diff = 50;
                    $face = 6;
                    break;
                case 10:
                    $diff = 50;
                    $demon = 1;
                    $face = 7;
                    break;
            }

            $addedCp = 0;

            # creator points update

            $levelInfo = $this->getLevelInfo($levelID);

            if($levelInfo['stars'] != 0) {
                die(-1);
            }

            if($stars != 0) {
                $addedCp += 1;
            }

            if($featured != 0) {
                $addedCp += 1;
            }

            $userInfo = $this->getUserStats($levelInfo['userID']);

            $sql = $conn->prepare("UPDATE users SET creatorPoints = :creatorPoints WHERE userID = :userID");
            $sql->execute([':userID' => $levelInfo['userID'], ':creatorPoints' => $userInfo['creatorPoints'] + $addedCp]);

            # rate

            $sql = $conn->prepare("UPDATE levels SET difficulty = :diff, stars = :stars, featureScore = :featured, auto = :auto, demon = :demon, rateDate = UNIX_TIMESTAMP() WHERE levelID = :levelID");
            $sql->execute([':levelID' => $levelID, ':diff' => $diff, ':stars' => $stars, ':featured' => $featured, ':auto' => $auto, ':demon' => $demon]);

            # webhook

            $thumbnail = $diffFacesUrl;

            # thumbnail calculating

            if($featured > 0) {
                $thumbnail .= "featured/";
            } else {
                $thumbnail .= "stars/";
            }

            switch($face) {
                case 1:
                    $thumbnail .= "auto.png";
                    break;
                case 2:
                    $thumbnail .= "easy.png";
                    break;
                case 3:
                    $thumbnail .= "normal.png";
                    break;
                case 4:
                    $thumbnail .= "hard.png";
                    break;
                case 5:
                    $thumbnail .= "harder.png";
                    break;
                case 6:
                    $thumbnail .= "insane.png";
                    break;
                case 7:
                    $thumbnail .= "demon-hard.png";
                    break;
            }

            $rateWebhook = $dw
                ->setTitle("New rated level!")
                ->setDescription("**Level ID:** " . $levelID . "\n**Name: **" . $levelInfo['levelName'] . "\n **Stars:** " . $stars)
                ->setColor("#7f03fc")
                ->setThumbnail($thumbnail)
                ->send();
        }

        public function unrateLevel($levelID) {
            require __DIR__ . "/connection.php";

            $removedCp = 0;

            # creator points update

            $levelInfo = $this->getLevelInfo($levelID);

            if($levelInfo['stars'] != 0) {
                $removedCp += 1;
            }

            if($levelInfo['featureScore'] != 0) {
                $removedCp += 1;
            }

            $userInfo = $this->getUserStats($levelInfo['userID']);

            $sql = $conn->prepare("UPDATE users SET creatorPoints = :creatorPoints WHERE userID = :userID");
            $sql->execute([':userID' => $levelInfo['userID'], ':creatorPoints' => $userInfo['creatorPoints'] - $removedCp]);

            # unfeature

            $sql = $conn->prepare("UPDATE levels SET stars = 0, featureScore = 0, auto = 0, demon = 0, rateDate = 0 WHERE levelID = :levelID");
            $sql->execute([':levelID' => $levelID]);
        }

        public function featureLevel($levelID) {
            require __DIR__ . "/connection.php";

            $addedCp = 0;

            # creator points update

            $levelInfo = $this->getLevelInfo($levelID);

            if($levelInfo['featureScore'] == 0) {
                $addedCp += 1;
            }

            $userInfo = $this->getUserStats($levelInfo['userID']);

            $sql = $conn->prepare("UPDATE users SET creatorPoints = :creatorPoints WHERE userID = :userID");
            $sql->execute([':userID' => $levelInfo['userID'], ':creatorPoints' => $userInfo['creatorPoints'] + $addedCp]);

            # feature

            $sql = $conn->prepare("UPDATE levels SET featureScore = 1 WHERE levelID = :levelID");
            $sql->execute([':levelID' => $levelID]);
        }
        public function unfeatureLevel($levelID) {
            require __DIR__ . "/connection.php";

            $removedCp = 0;

            # creator points update

            $levelInfo = $this->getLevelInfo($levelID);

            if($levelInfo['featureScore'] != 0) {
                $removedCp += 1;
            }

            $userInfo = $this->getUserStats($levelInfo['userID']);

            $sql = $conn->prepare("UPDATE users SET creatorPoints = :creatorPoints WHERE userID = :userID");
            $sql->execute([':userID' => $levelInfo['userID'], ':creatorPoints' => $userInfo['creatorPoints'] - $removedCp]);

            # unfeature

            $sql = $conn->prepare("UPDATE levels SET featureScore = 0 WHERE levelID = :levelID");
            $sql->execute([':levelID' => $levelID]);
        }
        public function deleteLevel($levelID) {
            require __DIR__ . "/connection.php";

            $removedCp = 0;

            # creator points update

            $levelInfo = $this->getLevelInfo($levelID);

            if($levelInfo['stars'] != 0) {
                $removedCp += 1;
            }

            if($levelInfo['featureScore'] != 0) {
                $removedCp += 1;
            }

            $userInfo = $this->getUserStats($levelInfo['userID']);

            $sql = $conn->prepare("UPDATE users SET creatorPoints = :creatorPoints WHERE userID = :userID");
            $sql->execute([':userID' => $levelInfo['userID'], ':creatorPoints' => $userInfo['creatorPoints'] - $removedCp]);

            # deletion

            $sql = $conn->prepare("DELETE FROM levels WHERE levelID = :levelID");
            $sql->execute([':levelID' => $levelID]);
        }
        public function setLevelDiff($levelID, $difficulty) {
            $diff = 0;
            switch($difficulty) {
                case 1:
                    $diff = 10;
                    break;
                case 2:
                    $diff = 10;
                    break;
                case 3:
                    $diff = 20;
                    break;
                case 4:
                case 5:
                    $diff = 30;
                    break;
                case 6:
                case 7:
                    $diff = 40;
                    break;
                case 8:
                case 9:
                    $diff = 50;
                    break;
                case 10:
                    $diff = 50;
                    break;
            }
            require __DIR__ . "/connection.php";
            $sql = $conn->prepare("UPDATE levels SET difficulty = :diff WHERE levelID = :levelID");
            $sql->execute([':levelID' => $levelID, ':diff' => $diff]);
        }

        public function sendLevel($levelID, $stars, $feature, $udid) {

            # Discord webhook stuff
            require __DIR__ . "/../../config/webhooks.php";
            include __DIR__ . "/DiscordWebhook.php";
            $dw = new DiscordWebhook($modSendWebhook);
            $webhook = $dw
                ->newMessage()
                ->setTitle("Level requested!")
                ->setDescription("ID: $levelID \n Stars: $stars \n Feature: $feature")
                ->setColor("#eb9834")
                ->send();

            # query

            require __DIR__ . "/connection.php";
            $ip = $_SERVER['REMOTE_ADDR'];
            $sql = $conn->prepare("INSERT INTO sends (levelID, udid, stars, feature, ip, timestamp) VALUES (:levelID, :udid, :stars, :feature, :ip, UNIX_TIMESTAMP())");
            $sql->execute([':levelID' => $levelID, ':stars' => $stars, ':feature' => $feature, 'ip' => $ip, ':udid' => $udid]);
        }

        public function getLevelInfo($levelID) {
            require __DIR__ . "/connection.php";
            $sql = $conn->prepare("SELECT * FROM levels WHERE levelID = :levelID");
            $sql->execute(['levelID' => $levelID]);

            $result = $sql->fetch(PDO::FETCH_ASSOC);

            return $result;
        }

        public function getUserStats($userID) {
            require __DIR__ . "/connection.php";
            $sql = $conn->prepare("SELECT * FROM users WHERE userID = :userID");
            $sql->execute(['userID' => $userID]);

            $result = $sql->fetch(PDO::FETCH_ASSOC);

            return $result;
        }

        public function getSongInfo($songID) {
            require __DIR__ . "/connection.php";
            # check if song is already saved in the db

            $sql = $conn->prepare("SELECT COUNT(*) FROM songs WHERE id = :songID");
            $sql->execute([':songID' => $songID]);

            $result = $sql->fetchColumn();

            if($result == 0) {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://www.boomlings.com/database/getGJSongInfo.php');  
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('application/x-www-form-urlencoded'));
            
            # Set request info
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'secret=Wmfd2893gb7&songID=' . $songID . "&gameVersion=22&binaryVersion=45");
            
            $content = trim(curl_exec($ch));
            curl_close($ch);

            $songInfo = explode("|",$content);
            $songInfo = str_replace("~", "", $songInfo);

            $platform = -1;

            if($songInfo[1] >= 10000000) {
                $platform = 0;
            }

            switch($songInfo[17]) {
                case 0:
                    break;
                case 1:
                    $platform = 1;
            }

            $download = 0;

            $sql = $conn->prepare("INSERT INTO songs (id, name, authorID, authorName, size, download, platform) VALUES (:id, :name, :authorID, :authorName, :size, :download, :platform)");
            $sql->execute([':id' => $songInfo[1], ':name' => $songInfo[3], ':authorID' => $songInfo[6], ':authorName' => $songInfo[7], ':size' => $songInfo[9], ':download'=> $songInfo[13], ':platform' => $platform]);
            }
                $sql = $conn->prepare("SELECT * FROM songs WHERE id = :songID");
                $sql->execute([':songID' => $songID]);

                $result = $sql->fetch(PDO::FETCH_ASSOC);

                if($result['isBanned'] == 1) {
                    echo(-2);
                    die();
                }

                switch($result['platform']) {
                    case -1:
                        break;
                    case 0:
                        $result['name'] = "(lib) " . $result['name'];
                        break;
                    case 1:
                        $result['name'] = "(NCS) " . $result['name'];
                        break;
                }

                if($result['download'] == "CUSTOMURL") {
                    $download = "https://geometrydashfiles.b-cdn.net/music/" . $result['id'] . ".ogg";
                } else {
                    $download = $result['download'];
                }

                return("1~|~". $result['id'] . "~|~2~|~" . $result['name'] . "~|~3~|~" . $result['authorID'] . "~|~4~|~" . $result['authorName'] . "~|~5~|~" . $result['size'] . "~|~10~|~" . $download);
        }

        public function checkAuthentication($username, $password) {
            require __DIR__ . "/connection.php";
            include_once __DIR__ . "/gjp.php";
            $gjpTools = new gjpTools();
            $password = $gjpTools->MakeGJP($password);
            $sql = $conn->prepare("SELECT COUNT(*) FROM accounts WHERE username = :username AND password = :password");
            $sql->execute([':username' => $username, ':password' => $password]);

            $result = $sql->fetchColumn();

            if($result == 1) {
                return 1;
            } else {
                return 0;
            }
        }

        public function getUDIDFromAccountID($accountID) {
            require __DIR__ . "/connection.php";
            $sql = $conn->prepare("SELECT udid FROM accounts WHERE id = :id");
            $sql->execute([':id' => $accountID]);

            $result = $sql->fetchColumn();

            return $result;
        }

        public function getAccountID($username, $password = 0) {
            require __DIR__ . "/connection.php";
            include_once __DIR__ . "/gjp.php";
            $gjpTools = new gjpTools();
            $password = $gjpTools->MakeGJP($password);
            $sql = $conn->prepare("SELECT id FROM accounts WHERE username = :username");
            $sql->execute([':username' => $username]);

            $result = $sql->fetchColumn();

            return $result;
        }

        public function changePassword($username, $password, $newPassword) {
            require __DIR__ . "/connection.php";
            include_once __DIR__ . "/gjp.php";
            $gjpTools = new gjpTools();
            $authState = $this->checkAuthentication($username, $password);
            if($authState == 1) {
                # change password
                $newPassword = $gjpTools->MakeGJP($newPassword);
                $accID = $this->getAccountID($username, $password);
                $sql = $conn->prepare("UPDATE accounts SET password = :newPassword WHERE id = :id");
                $sql->execute([':id'=> $accID, ':newPassword' => $newPassword]);
                return 1;
            } else {
                return 0;
            }
        }

        public function changeUsername($username, $password, $newUsername) {
            require __DIR__ . "/connection.php";
            include_once __DIR__ . "/gjp.php";
            $authState = $this->checkAuthentication($username, $password);
            if($authState == 1) {
                # change username
                $accID = $this->getAccountID($username, $password);
                $sql = $conn->prepare("UPDATE accounts SET username = :newUsername WHERE id = :id");
                $sql->execute([':id'=> $accID, ':newUsername' => $newUsername]);
                return 1;
            } else {
                return 0;
            }
        }

        public function changeEmail($username, $password, $newEmail) {
            require __DIR__ . "/connection.php";
            include_once __DIR__ . "/gjp.php";
            $authState = $this->checkAuthentication($username, $password);
            if($authState == 1) {
                # change username
                $accID = $this->getAccountID($username, $password);
                $sql = $conn->prepare("UPDATE accounts SET email = :newEmail WHERE id = :id");
                $sql->execute([':id'=> $accID, ':newEmail' => $newEmail]);
                return 1;
            } else {
                return 0;
            }
        }

        public function checkBanState($userID, $banType) {
            require __DIR__ . "/connection.php";
            $sql = $conn->prepare("SELECT COUNT(*) FROM bans WHERE user = :userID AND banType = :banType AND expires > UNIX_TIMESTAMP() ORDER BY timestamp LIMIT 1");
            $sql->execute(['userID' => $userID, ':banType' => $banType]);
            $result = $sql->fetchColumn();
            if($result == 0) {
                return 0;
            } else {
                return 1;
            }
        }

        public function getUserID($udid) {
            require __DIR__ . "/connection.php";
            $sql = $conn->prepare("SELECT userID FROM users WHERE udid = :udid");
            $sql->execute([':udid' => $udid]);
            $result = $sql->fetchColumn();
            return $result;
        }

        public function getCommaSeparatedBans($type) {
            require __DIR__ . "/connection.php";
            $bans = "";
            $sql = $conn->prepare("SELECT user FROM bans WHERE banType = :banType");
            $sql->execute([':banType' => $type]);
            $result = $sql->fetchAll(PDO::FETCH_COLUMN, 0);
        
            if (!empty($result)) {
                $bans = implode(",", $result);
            }
        
            return $bans;
        }

        public function getBansCount($type) {
            require __DIR__ . "/connection.php";
            $sql = $conn->prepare("SELECT COUNT(*) FROM bans WHERE banType = :banType");
            $sql->execute([':banType' => $type]);
            $result = $sql->fetchColumn();
            return $result;
        }
        public static function base64url_encode($data) {

        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

        }



        public static function base64url_decode($data) {

        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));

        }

        # unfinished, extremely taxing function that downloads the entire music lib. don't use
        public function updateMusicLib() {
            $index = 0;
            include __DIR__ . "/connection.php";
            $currentVer = file_get_contents("https://geometrydashfiles.b-cdn.net/music/musiclibrary_version_02.txt");
            $savedVer = file_get_contents(__DIR__ . "/../../data/songs/libVersion.txt");
            if($currentVer != $savedVer) {
            file_put_contents(__DIR__ . "/../../data/songs/libVersion.txt", $currentVer);
            $lib = zlib_decode(self::base64url_decode(file_get_contents("https://geometrydashfiles.b-cdn.net/music/musiclibrary_02.dat")));
            $lib = explode("|", $lib);
            $artists = explode(";", $lib[1]);
            $songs = explode(";", $lib[2]);
            foreach($songs as $song) {
                $song = explode(",", $song);
                $download = "https://geometrydashfiles.b-cdn.net/music/" . $song[0] . ".mp3";
                $sql = $conn->prepare("INSERT INTO songs (id, name, authorID, authorName, size, download, platform) VALUES (:id, :name, :authorID, 'Library', :size, :download, :plat)");
                $sql->execute([':id' => $song[0], ':name' => $song[1], ':authorID' => $song[2], ':size' => $song[3], ':plat' => $song[6], ':download' => $download]);
                $index++;
            }
            }
        }

        public function unbanUser($banID) {
            include __DIR__ . "/connection.php";
            # check if ban exists
            $sql = $conn->prepare("SELECT COUNT(*) FROM bans WHERE id = :banID");
            $sql->execute([':banID' => $banID]);
            $count = $sql->fetchColumn();
            if($count != 1) {
                return 2;
            } else {
            $sql = $conn->prepare("DELETE FROM bans WHERE id = :banID");
            $sql->execute([':banID' => $banID]);
            return 1;
            }
        }

        public function doesLevelExist($levelID) {
            include __DIR__ . "/connection.php";
            $sql = $conn->prepare("SELECT COUNT(*) FROM levels WHERE levelID = :levelID");
            $sql->execute([':levelID' => $levelID]);
            if($sql->fetchColumn() == 1) {
                return 1;
            } else {
                return 0;
            }
        }

        public function forceChangeUsername($oldUsername, $newUsername) {
            include __DIR__ . "/connection.php";
            $oldUsernameStats = $this->doesAccountExist($oldUsername);
            if($oldUsernameStats != 1) {
                return 0;
            }
            $newUsernameStats = $this->doesAccountExist($newUsername);
            if($newUsernameStats == 1) {
                return 0;
            }
            # accounts table
            $sql = $conn->prepare("UPDATE accounts SET username = :newUsername WHERE username = :oldUsername");
            $sql->execute([':newUsername' => $newUsername, ':oldUsername' => $oldUsername]);
            # users table
            $sql = $conn->prepare("UPDATE users SET userName = :newUsername WHERE userName = :oldUsername");
            $sql->execute([':newUsername' => $newUsername, ':oldUsername' => $oldUsername]);
            return 1;
        }

        public function doesAccountExist($username) {
            include __DIR__ . "/connection.php";
            $sql = $conn->prepare("SELECT COUNT(*) FROM accounts WHERE username = :username");
            $sql->bindParam(":username", $username);
            $sql->execute();

            $result = $sql->fetchColumn();

            if($result != 0) {
                return 1;
            } else {
                return 0;
            }
        }

        public function forceChangePassword($username, $newPassword) {
            include __DIR__ . "/connection.php";
            include_once __DIR__ . "/gjp.php";
            $state = $this->doesAccountExist($username);
            if($state != 1) {
                return 0;
            }
            $gjpTools = new gjpTools();
            $gjp = $gjpTools->MakeGJP($newPassword);
            $sql = $conn->prepare("UPDATE accounts SET password = :password WHERE username = :username");
            $sql->execute([':password' => $gjp, ':username' => $username]);
            return 1;
        }
    }
?>