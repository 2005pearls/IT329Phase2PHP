<?php
session_start();
require_once("../config/db.php");
 
// ── Only admins ───────────────────────────────────────────────────────────────
if (!isset($_SESSION['userID']) || $_SESSION['userType'] != "admin") {
    header("Location: ../login.php?error=Access+denied.");
    exit();
}
 
// ── Collect POST data ─────────────────────────────────────────────────────────
$recipeID  = intval($_POST['recipeID']  ?? 0);
$creatorID = intval($_POST['creatorID'] ?? 0);
$reportID  = intval($_POST['reportID']  ?? 0);
$action    = $_POST['action']           ?? 'dismiss';
 
if ($recipeID === 0 || $reportID === 0) {
    header("Location: ../admin.php");
    exit();
}
 
// ── If action = block ─────────────────────────────────────────────────────────
if ($action === 'block' && $creatorID > 0) {
 
    // 1. Get creator info before deleting
    $stmt = mysqli_prepare($conn, "SELECT firstName, lastName, emailAddress FROM user WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $creatorID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $firstName, $lastName, $email);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
 
    // 2. Get all recipe IDs belonging to this user
    // recipe table: id, userID, name, photoFileName
    $recipeIDs = [];
    $r = mysqli_prepare($conn, "SELECT id FROM recipe WHERE userID = ?");
    mysqli_stmt_bind_param($r, "i", $creatorID);
    mysqli_stmt_execute($r);
    $res = mysqli_stmt_get_result($r);
    while ($row = mysqli_fetch_assoc($res)) {
        $recipeIDs[] = intval($row['id']);
    }
    mysqli_stmt_close($r);
 
    // 3. Delete all associated data for each recipe
    foreach ($recipeIDs as $rid) {
 
        // Delete recipe image file from server
        $imgStmt = mysqli_prepare($conn, "SELECT photoFileName FROM recipe WHERE id = ?");
        mysqli_stmt_bind_param($imgStmt, "i", $rid);
        mysqli_stmt_execute($imgStmt);
        mysqli_stmt_bind_result($imgStmt, $photoFile);
        mysqli_stmt_fetch($imgStmt);
        mysqli_stmt_close($imgStmt);
        if (!empty($photoFile) && $photoFile !== 'default-user.jpg'
            && file_exists("../images/" . $photoFile)) {
            unlink("../images/" . $photoFile);
        }
 
        // Delete from related tables: comment, likes, favourites, report, ingredients, instructions
        foreach (['comment', 'likes', 'favourites', 'report', 'ingredients', 'instructions'] as $table) {
            $del = mysqli_prepare($conn, "DELETE FROM $table WHERE recipeID = ?");
            mysqli_stmt_bind_param($del, "i", $rid);
            mysqli_stmt_execute($del);
            mysqli_stmt_close($del);
        }
 
        // Delete the recipe itself
        $delR = mysqli_prepare($conn, "DELETE FROM recipe WHERE id = ?");
        mysqli_stmt_bind_param($delR, "i", $rid);
        mysqli_stmt_execute($delR);
        mysqli_stmt_close($delR);
    }
 
    // 4. Add user to blockeduser table
    $ins = mysqli_prepare($conn,
        "INSERT INTO blockeduser (firstName, lastName, emailAddress) VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($ins, "sss", $firstName, $lastName, $email);
    mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);
 
    // 5. Delete the user from user table
    $delU = mysqli_prepare($conn, "DELETE FROM user WHERE id = ?");
    mysqli_stmt_bind_param($delU, "i", $creatorID);
    mysqli_stmt_execute($delU);
    mysqli_stmt_close($delU);
 
} else {
    // ── action = dismiss: just delete the report ──────────────────────────────
    $delRep = mysqli_prepare($conn, "DELETE FROM report WHERE id = ?");
    mysqli_stmt_bind_param($delRep, "i", $reportID);
    mysqli_stmt_execute($delRep);
    mysqli_stmt_close($delRep);
}
 
mysqli_close($conn);
 
// ── Redirect back to admin page ───────────────────────────────────────────────
header("Location: ../admin.php");
exit();
?>
