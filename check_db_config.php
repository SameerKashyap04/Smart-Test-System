<?php
echo "<h1>Database Config Check</h1>";

// Read the config file directly
$config_content = file_get_contents('config/db.php');

if (strpos($config_content, 'SmartTest@2025') !== false) {
    echo "<p style='color:green'>✅ The file on the server HAS the new password: <strong>SmartTest@2025</strong></p>";
} else {
    echo "<p style='color:red'>❌ The file on the server is STILL using the OLD password.</p>";
    echo "<p>Please run <code>git reset --hard origin/main</code> on the server.</p>";
}

echo "<h3>Debug Info:</h3>";
echo "Current File Content (Snippet):<br>";
// Show lines 10-20 where password is usually located
$lines = explode("\n", $config_content);
for ($i = 10; $i < 20; $i++) {
    if (isset($lines[$i])) {
        echo htmlspecialchars($lines[$i]) . "<br>";
    }
}
?>
