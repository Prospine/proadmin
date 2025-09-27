<?php

declare(strict_types=1);

// Always start the session to access and destroy it
session_start();

// Check if the user is actually logged in before trying to log them out
if (isset($_SESSION['uid'])) {

    // It's a good idea to have our tools ready before we start
    require_once '../../common/db.php';
    require_once '../../common/logger.php'; // Our trusty logger!

    try {
        // Log the logout event before we destroy the session data
        // This is our one chance to grab the user info before it's gone
        log_activity(
            $pdo,
            $_SESSION['uid'],
            $_SESSION['username'] ?? 'unknown', // Use 'unknown' as a fallback
            $_SESSION['branch_id'],
            'LOGOUT' // The action type is 'LOGOUT'
        );
    } catch (Exception $e) {
        // Even if logging fails, we must proceed with the logout.
        // In a production environment, you might log this error to a file.
        // For now, we'll just ignore it to ensure the user is logged out regardless.
    }
}

// --- The Grand Session Obliteration ---

// Step 1: Unset all of the session variables.
$_SESSION = [];

// Step 2: If you're using session cookies (which you are), this is the best way
// to kill the cookie. It's like telling the browser, "Forget this ever happened."
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '', // Set the value to nothing
        time() - 42000, // Set the expiration date to the distant past
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Step 3: Finally, destroy the session itself. The party's over!
session_destroy();

// --- Redirect to Safety ---

// After all that, send the user back to the login page.
// No lingering, no confusion. Just a clean exit.
header('Location: ../../login.php');
exit(); // Always call exit() after a header redirect.