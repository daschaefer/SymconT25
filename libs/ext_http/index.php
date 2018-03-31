<?php
    /*
     * Dieses Skript muss auf dem Webserver in dem Verzeichnis gespeichert werden wo auch die Ereignisbilder gespeichert werden 
     */

    $pictures = array();
    $excludeList = array(".", "..", "INFO.jpg", "log.txt", "Thumbs.db");

    $amount = 5;
    if(isset($_GET['amount']))
       $amount = $_GET['amount']; 

    $path = sprintf(
        "%s://%s%s",
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
        $_SERVER['SERVER_NAME'],
        dirname($_SERVER['PHP_SELF'])
    );

    $files = preg_grep('/^([^.])/', scandir(".", SCANDIR_SORT_DESCENDING));

    $i = 0;
    foreach ($files as $file) {
        if($i >= $amount)
            break;
        
        if (!in_array($file, $excludeList)) {
            $pictures[] = $path."/".$file."?p=".time();
        }

        $i++;
    }

    echo json_encode($pictures);
?>