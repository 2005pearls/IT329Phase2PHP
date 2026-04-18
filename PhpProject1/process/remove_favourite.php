<?php
session_start();
require_once("../config/db.php");

// make sure the user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: ../login.php");
    exit();
}

// make sure a recipe id was passed in the URL
if (!isset($_GET['recipeID']) || !is_numeric($_GET['recipeID'])) {
    header("Location: ../user.php");
    exit();
}

$userID   = $_SESSION['userID'];
$recipeID = (int)$_GET['recipeID'];

// delete the favourite row from the Favourites table
$sql  = "DELETE FROM Favourites WHERE userID = ? AND recipeID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $userID, $recipeID);
mysqli_stmt_execute($stmt);

// redirect back to the user's page
header("Location: ../user.php");
exit();
?>
