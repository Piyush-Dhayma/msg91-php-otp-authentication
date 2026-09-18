<?php

require_once __DIR__ . '/config.php';

require_once __DIR__ . '/db.php';


// =====================================================
// CHECK LOGIN
// =====================================================

if (!isset($_SESSION['user_id'])) {

    header('Location: index.php');

    exit;
}


// =====================================================
// GET USER
// =====================================================

$stmt = $pdo->prepare(
    "SELECT *
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$stmt->execute([
    $_SESSION['user_id']
]);

$user =
    $stmt->fetch();


if (!$user) {

    session_destroy();

    header('Location: index.php');

    exit;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>

<body>


<div class="container">

    <div class="card">

        <h2>
            Welcome 🎉
        </h2>


        <p>
            You are successfully logged in.
        </p>


        <p>

            Mobile:

            <strong>
                +<?= htmlspecialchars(
                    $user['phone']
                ) ?>
            </strong>

        </p>


        <p>

            Verification:

            <strong>

                <?=
                    $user['is_verified']
                    ? 'Verified'
                    : 'Not Verified'
                ?>

            </strong>

        </p>


        <a
            class="logout"
            href="logout.php"
        >
            Logout
        </a>

    </div>

</div>


</body>

</html>