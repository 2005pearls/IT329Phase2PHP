<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once(__DIR__ . "/config/db.php");
 
// Check admin
if (!isset($_SESSION['userID']) || $_SESSION['userType'] != "admin") {
    header("Location: login.php?error=Access+denied.+Admins+only.");
    exit();
}
 
// Get admin info
$adminID = $_SESSION['userID'];
 
$stmt = mysqli_prepare($conn, "SELECT firstName, lastName, emailAddress FROM user WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $adminID);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $firstName, $lastName, $adminEmail);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
 
$adminName = $firstName ;
 
//  Get all recipe reports
$reportsResult = mysqli_query($conn, "
    SELECT 
        r.id                AS reportID,
        r.recipeID,
        rec.name            AS recipeTitle,
        rec.photoFileName   AS recipePhoto,
        u.id                AS creatorID,
        u.firstName,
        u.lastName,
        u.photoFileName     AS creatorPhoto
    FROM report r
    JOIN recipe rec ON r.recipeID = rec.id
    JOIN user u     ON rec.userID = u.id
    ORDER BY r.id DESC
");
 
// Get blocked users

$blockedResult = mysqli_query($conn, "
    SELECT firstName, lastName, emailAddress 
    FROM blockeduser 
    ORDER BY firstName
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SweetCrumb - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-page">
 
    <?php require_once 'includes/header.php'; ?>
 
    <nav class="breadcrumb">
        <div class="breadcrumb-container">
            <span class="breadcrumb-item"><a href="index.php">Home</a></span>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-item active">Admin Dashboard</span>
        </div>
    </nav>
 
    <main>
        <div class="container">
 
            <!-- Welcome + Sign out -->
            <div class="card" style="display:flex; justify-content:space-between; align-items:center;">
                <div class="welcome">Welcome <?php echo htmlspecialchars($adminName); ?>!</div>
                <a href="process/logout.php" class="link">Sign out</a>
            </div>
 
            <!-- 11b. Admin Information -->
            <div class="card">
                <div class="section-title">My Information</div>
                <div class="info-row">
                    <div class="info-label">Full Name:</div>
                    <div><?php echo htmlspecialchars($adminName); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div><?php echo htmlspecialchars($adminEmail); ?></div>
                </div>
            </div>
 
            <!--  Reported Recipes -->
            <div class="card admin-table">
                <div class="section-title">Reported Recipes</div>
 
                <?php if ($reportsResult && mysqli_num_rows($reportsResult) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Recipe Name</th>
                            <th>Recipe Creator</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($reportsResult)):
                        $creatorFullName = $row['firstName'] . ' ' . $row['lastName'];
                    ?>
                        <tr>
                            <td>
                                <a href="ViewRecipe.php?id=<?php echo $row['recipeID']; ?>">
                                    <?php echo htmlspecialchars($row['recipeTitle']); ?>
                                </a>
                                <?php if (!empty($row['recipePhoto'])): ?>
                                    <img src="images/<?php echo htmlspecialchars($row['recipePhoto']); ?>"
                                         class="recipe-photo" alt="recipe">
                                <?php endif; ?>
                            </td>
 
                            <td class="creator">
                                <?php if (!empty($row['creatorPhoto'])): ?>
                                    <img src="images/<?php echo htmlspecialchars($row['creatorPhoto']); ?>"
                                         class="creator-photo" alt="creator">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($creatorFullName); ?>
                            </td>
 
                            <td>
                                <form method="POST" action="process/handle_report.php">
                                    <input type="hidden" name="recipeID"  value="<?php echo $row['recipeID'];  ?>">
                                    <input type="hidden" name="creatorID" value="<?php echo $row['creatorID']; ?>">
                                    <input type="hidden" name="reportID"  value="<?php echo $row['reportID'];  ?>">
 
                                    <label style="display:flex; align-items:center; gap:10px;">
                                        <input type="radio" name="action" value="dismiss" checked>
                                        Dismiss Report
                                    </label>
                                    <br>
                                    <label style="display:flex; align-items:center; gap:10px;">
                                        <input type="radio" name="action" value="block">
                                        Block User
                                    </label>
                                    <br><br>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
 
                <?php else: ?>
                    <p>No reported recipes at the moment.</p>
                <?php endif; ?>
            </div>
 
            <!--  Blocked Users -->
            <div class="card">
                <div class="section-title">Blocked Users List</div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email Address</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($blockedResult && mysqli_num_rows($blockedResult) > 0):
                        while ($b = mysqli_fetch_assoc($blockedResult)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['firstName'] . ' ' . $b['lastName']); ?></td>
                            <td><?php echo htmlspecialchars($b['emailAddress']); ?></td>
                        </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="2">No blocked users.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
 
        </div>
    </main>
 
    <?php require_once 'includes/footer.php'; ?>
 
</body>
</html>
<?php mysqli_close($conn); ?>
