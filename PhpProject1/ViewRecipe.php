<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require_once(__DIR__ . "/config/db.php");
 
//check logged in
if (!isset($_SESSION['userID'])) {
    header("Location: index.php");
    exit();
}
 
// ── 10a. Check recipe ID from query string ────────────────────────────────────
$recipeID      = intval($_GET['id'] ?? 0);
$currentUserID = $_SESSION['userID'];
$userType      = $_SESSION['userType'];
 
if ($recipeID === 0) {
    header("Location: index.php");
    exit();
}
 
// ── 10b. Get recipe info + creator ────────────────────────────────────────────
$stmt = mysqli_prepare($conn, "
    SELECT 
        r.id, r.userID AS creatorID, r.name, r.description,
        r.photoFileName, r.videoFilePath,
        u.firstName, u.lastName, u.photoFileName AS creatorPhoto,
        rc.categoryName
    FROM recipe r
    JOIN user u ON r.userID = u.id
    LEFT JOIN recipecategory rc ON r.categoryID = rc.id
    WHERE r.id = ?
");
mysqli_stmt_bind_param($stmt, "i", $recipeID);
mysqli_stmt_execute($stmt);
$recipe = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
 
if (!$recipe) {
    header("Location: index.php");
    exit();
}

// ── Get ingredients, instructions, comments ───────────────────────────────────
$ingResult = mysqli_query($conn, "SELECT * FROM ingredients WHERE recipeID = $recipeID");
$insResult = mysqli_query($conn, "SELECT * FROM instructions WHERE recipeID = $recipeID ORDER BY id ASC");
$comResult = mysqli_query($conn, "
    SELECT c.comment, c.date, u.firstName, u.lastName
    FROM comment c JOIN user u ON c.userID = u.id
    WHERE c.recipeID = $recipeID ORDER BY c.date DESC
");
 
// ── Like count ────────────────────────────────────────────────────────────────
$likeCountRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM likes WHERE recipeID = $recipeID"));
$likeCount = $likeCountRow['cnt'];
 
// ── 10d. Check status for current user ───────────────────────────────────────
$isCreator = ($recipe['creatorID'] == $currentUserID);
$isAdmin   = ($userType == 'admin');

$alreadyLiked = false;
$alreadyFaved = false;
$alreadyReported = false;

if (!$isCreator && !$isAdmin) {

    $result = mysqli_query($conn, "SELECT * FROM likes WHERE userID=$currentUserID AND recipeID=$recipeID");
    if ($result && mysqli_num_rows($result) > 0) {
        $alreadyLiked = true;
    }

    $result = mysqli_query($conn, "SELECT * FROM favourites WHERE userID=$currentUserID AND recipeID=$recipeID");
    if ($result && mysqli_num_rows($result) > 0) {
        $alreadyFaved = true;
    }

    $result = mysqli_query($conn, "SELECT * FROM report WHERE userID=$currentUserID AND recipeID=$recipeID");
    if ($result && mysqli_num_rows($result) > 0) {
        $alreadyReported = true;
    }
}
 
$creatorName = $recipe['firstName'] . ' ' . $recipe['lastName'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Recipe | SweetCrumb</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
 
<body class="view-recipe-page">
 
<?php require_once 'includes/header.php'; ?>
    
 <nav class="breadcrumb">
    <div class="breadcrumb-container">
      <span class="breadcrumb-item"><a href="index.php">Home</a></span>
      <span class="breadcrumb-separator">›</span>
      <span class="breadcrumb-item"><a href="user.php">User Dashboard</a></span>
      <span class="breadcrumb-separator">›</span>
      <span class="breadcrumb-item active">view Recipe</span>
    </div>
  </nav>
<main class="recipe-card">
 
     <!-- 10d. Action Buttons — only shown if viewer is not creator and not admin -->
    <?php if (!$isCreator && !$isAdmin): ?>
    <div class="actions">
 
        <!-- Favourite -->
        <form method="POST" action="process/recipe_actions.php" style="display:inline;">
            <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
            <input type="hidden" name="action"   value="favourite">
            <button type="submit" class="outline-btn" <?php echo $alreadyFaved ? 'disabled style="background:#ccc; color:#666; border:2px solid #ccc; cursor:not-allowed;"' : ''; ?>>
                <?php echo $alreadyFaved ? 'Added ✓' : 'Add to favourites'; ?>
            </button>
        </form>
 
        <!-- Like -->
        <form method="POST" action="process/recipe_actions.php" style="display:inline;">
            <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
            <input type="hidden" name="action"   value="like">
            <button type="submit" class="pink-btn" <?php echo $alreadyLiked ? 'disabled style="background:#ccc; color:#666; border:2px solid #ccc; cursor:not-allowed;"' : ''; ?>>
                <?php echo $alreadyLiked ? 'Liked ✓' : 'Like'; ?> (<span id="likeCount"><?php echo $likeCount; ?></span>)
            </button>
        </form>
 
        <!-- Report -->
        <form method="POST" action="process/recipe_actions.php" style="display:inline;">
            <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
            <input type="hidden" name="action"   value="report">
            <button type="submit" class="dark-btn" <?php echo $alreadyReported ? 'disabled style="background:#ccc; color:#666; border:2px solid #ccc; cursor:not-allowed;"' : ''; ?>>
                <?php echo $alreadyReported ? 'Reported ✓' : 'Report'; ?>
            </button>
        </form>
 
    </div>
    <?php endif; ?>

    <!-- Title -->
    <h2><?php echo htmlspecialchars($recipe['name']); ?></h2>
 
    <!-- Image -->
    <img src="images/<?php echo htmlspecialchars($recipe['photoFileName']); ?>"
         alt="Recipe Photo" class="recipe-photo">
 
    <!-- Creator -->
    <div class="creator">
        <img src="images/<?php echo htmlspecialchars($recipe['creatorPhoto']); ?>" alt="Creator">
        <p><strong>Recipe Creator:</strong> <?php echo htmlspecialchars($creatorName); ?></p>
    </div>
 
    <!-- Details -->
    <section>
        <h3>Details</h3>
        <p><strong>Category:</strong> <?php echo htmlspecialchars($recipe['categoryName'] ?? 'N/A'); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($recipe['description']); ?></p>
    </section>
 
    <!-- Ingredients -->
    <section>
        <h3>Ingredients</h3>
        <ul>
        <?php if ($ingResult && mysqli_num_rows($ingResult) > 0):
            while ($ing = mysqli_fetch_assoc($ingResult)): ?>
<li><?php echo htmlspecialchars($ing['ingredientQuantity'] . ' ' . $ing['ingredientName']); ?></li>        <?php endwhile; else: ?>
            <li>No ingredients listed.</li>
        <?php endif; ?>
        </ul>
    </section>
 
    <!-- Instructions -->
    <section>
        <h3>Instructions</h3>
        <ol>
        <?php if ($insResult && mysqli_num_rows($insResult) > 0):
            while ($ins = mysqli_fetch_assoc($insResult)): ?>
<li><?php echo htmlspecialchars($ins['step']); ?></li>        <?php endwhile; else: ?>
            <li>No instructions listed.</li>
        <?php endif; ?>
        </ol>
    </section>
 
    <!-- Video -->
    <section>
        <h3>Video</h3>
        <?php if (!empty($recipe['videoFilePath'])): ?>
            <a href="<?php echo htmlspecialchars($recipe['videoFilePath']); ?>" target="_blank">Watch recipe video</a>
        <?php else: ?>
            <p>No video available.</p>
        <?php endif; ?>
    </section>
 
    <!-- 10c. Comments -->
    <section class="comments-section">
        <h3>Comments</h3>
 
        <form method="POST" action="process/recipe_actions.php">
            <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
            <input type="hidden" name="action"   value="comment">
            <textarea name="comment" id="commentInput" placeholder="Write a comment..." required></textarea>
            <button class="pink-btn" type="submit">Add Comment</button>
        </form>
 
        <div id="commentsBox">
        <?php if ($comResult && mysqli_num_rows($comResult) > 0):
            while ($com = mysqli_fetch_assoc($comResult)): ?>
            <div class="comment">
                <strong><?php echo htmlspecialchars($com['firstName'] . ' ' . $com['lastName']); ?>:</strong>
                <?php echo htmlspecialchars($com['comment']); ?>
                <div class="comment-time"><?php echo $com['date']; ?></div>
            </div>
        <?php endwhile; else: ?>
            <div class="comment"><p>No comments yet.</p></div>
        <?php endif; ?>
        </div>
    </section>
 
</main>
 

 
<?php require_once 'includes/footer.php'; ?>
 
</body>
</html>
<?php mysqli_close($conn); ?>
