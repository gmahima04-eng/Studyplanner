<?php
session_start();

// If user not logged in → redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Study Planner</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .box {
            background: white;
            padding: 20px;
            width: 400px;
            margin: auto;
            margin-top: 60px;
            box-shadow: 0px 0px 10px #aaa;
        }
        button {
            padding: 10px 20px;
            background: #d9534f;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Welcome, <?php echo $_SESSION['email']; ?> 👋</h2>

    <p>You are successfully logged in.</p>

    <form action="api/logout.php" method="POST">
        <button type="submit">Logout</button>
    </form>
</div>

</body>
</html>
