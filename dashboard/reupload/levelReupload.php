<?php

include __DIR__ . "/../../incl/lib/connection.php";
include __DIR__ . "/../../config/dashboard.php";
include __DIR__ . "/../../incl/lib/mainLib.php";
include __DIR__ . "/../../incl/lib/dashboardLib.php";

session_start();

$dl = new DashboardLib();
$ml = new MainLib();

$dl->printStyle();
$dl->printHeader();

$dl->printMessageBox3("Level Reupload", "This page is unfinished!");

?>