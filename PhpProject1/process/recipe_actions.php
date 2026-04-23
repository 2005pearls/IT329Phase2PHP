<?php
session_start();
require_once("../config/db.php");
 
if (!isset($_SESSION['userID'])) {
    header("Location: index.php");
    exit();
}
 
$recipeID = intval($_POST['recipeID'] ?? 0);
$userID   = $_SESSION['userID'];
$action   = $_POST['action'] ?? '';
 
if ($recipeID === 0) {
    header("Location: ../index.php");
    exit();
}
 
// ── Add Comment 
if ($action === 'comment') {
    $comment = trim($_POST['comment'] ?? '');
    if ($comment !== '') {
        $stmt = mysqli_prepare($conn, "INSERT INTO comment (recipeID, userID, comment, date) VALUES (?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "iis", $recipeID, $userID, $comment);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
 
// ── Add Like 
elseif ($action === 'like') {
    $check = mysqli_prepare($conn, "SELECT 1 FROM likes WHERE userID = ? AND recipeID = ?");
    mysqli_stmt_bind_param($check, "ii", $userID, $recipeID);
    mysqli_stmt_execute($check);
    $exists = mysqli_num_rows(mysqli_stmt_get_result($check)) > 0;
    mysqli_stmt_close($check);
 
    if (!$exists) {
        $stmt = mysqli_prepare($conn, "INSERT INTO likes (userID, recipeID) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $userID, $recipeID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
 
// ── Add Favourite 
elseif ($action === 'favourite') {
    $check = mysqli_prepare($conn, "SELECT 1 FROM favourites WHERE userID = ? AND recipeID = ?");
    mysqli_stmt_bind_param($check, "ii", $userID, $recipeID);
    mysqli_stmt_execute($check);
    $exists = mysqli_num_rows(mysqli_stmt_get_result($check)) > 0;
    mysqli_stmt_close($check);
 
    if (!$exists) {
        $stmt = mysqli_prepare($conn, "INSERT INTO favourites (userID, recipeID) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $userID, $recipeID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
 
// ── Add Report
elseif ($action === 'report') {
    $check = mysqli_prepare($conn, "SELECT 1 FROM report WHERE userID = ? AND recipeID = ?");
    mysqli_stmt_bind_param($check, "ii", $userID, $recipeID);
    mysqli_stmt_execute($check);
    $exists = mysqli_num_rows(mysqli_stmt_get_result($check)) > 0;
    mysqli_stmt_close($check);
 
    if (!$exists) {
        $stmt = mysqli_prepare($conn, "INSERT INTO report (userID, recipeID) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $userID, $recipeID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
 
mysqli_close($conn);
ob_start();
header("Location: ../ViewRecipe.php?id=" . $recipeID);
ob_end_flush();
exit();
?>
