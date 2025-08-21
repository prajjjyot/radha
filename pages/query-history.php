<?php
include('../config/functions.php');
$db = new db_functions();

if (!isset($_SESSION['logged_in_email'])) {
    header("Location: signin.php");
    exit;
}

$email = $_SESSION['logged_in_email'];
$history = $db->get_query_history($email);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Query History</title>
</head>
<body>

<h2>Your Generated Query History</h2>

<table border="1" cellpadding="5">
    <thead>
        <tr>
            <th>ID</th>
            <th>Task</th>
            <th>Table</th>
            <th>Columns</th>
            <th>Generated Query</th>
            <th>Date/Time</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($history): ?>
            <?php foreach ($history as $item): ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <td><?php echo $item['task']; ?></td>
                    <td><?php echo $item['table_name']; ?></td>
                    <td><?php echo $item['columns']; ?></td>
                    <td><pre><?php echo $item['generated_query']; ?></pre></td>
                    <td><?php echo $item['created_at']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No query history found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
