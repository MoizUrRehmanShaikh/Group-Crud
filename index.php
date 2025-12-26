<?php
include 'db.php';

/* CREATE group + exactly 5 members */
if (isset($_POST['create_group'])) {
    $group_name = trim($_POST['group_name']);
    $member_names = $_POST['member_name'] ?? [];
    $member_roles = $_POST['member_role'] ?? [];

    $ok = ($group_name !== "");
    for ($i = 0; $i < 5; $i++) {
        if (!isset($member_names[$i]) || trim($member_names[$i]) === "") $ok = false;
        if (!isset($member_roles[$i]) || trim($member_roles[$i]) === "") $ok = false;
    }

    if ($ok) {
        // Insert group
        $stmt = $conn->prepare("INSERT INTO student_groups (group_name) VALUES (?)");
        $stmt->bind_param("s", $group_name);
        $stmt->execute();
        $group_id = $stmt->insert_id;
        $stmt->close();

        // Insert 5 members
        $stmtM = $conn->prepare("INSERT INTO members (group_id, member_name, member_role) VALUES (?,?,?)");
        for ($i = 0; $i < 5; $i++) {
            $name = trim($member_names[$i]);
            $role = trim($member_roles[$i]);
            $stmtM->bind_param("iss", $group_id, $name, $role);
            $stmtM->execute();
        }
        $stmtM->close();

        header("Location: index.php");
        exit;
    } else {
        $error = "Group name required and all 5 members must be filled (no empty fields).";
    }
}

/* DELETE group (members auto-delete because of ON DELETE CASCADE) */
if (isset($_GET['delete_group'])) {
    $gid = (int) $_GET['delete_group'];
    $stmt = $conn->prepare("DELETE FROM student_groups WHERE id=?");
    $stmt->bind_param("i", $gid);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}

/* READ groups + members */
$groups = [];
$sql = "SELECT g.id AS gid, g.group_name, m.id AS mid, m.member_name, m.member_role
        FROM student_groups g
        LEFT JOIN members m ON g.id = m.group_id
        ORDER BY g.id, m.id";
$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    $gid = $row['gid'];
    if (!isset($groups[$gid])) {
        $groups[$gid] = ['group_name' => $row['group_name'], 'members' => []];
    }
    if (!empty($row['mid'])) {
        $groups[$gid]['members'][] = ['name' => $row['member_name'], 'role' => $row['member_role']];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Group CRUD App</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f5f5f5; }
    .container { width: 92%; max-width: 1000px; margin:20px auto; background:#fff;
                 padding:20px; border-radius:8px; box-shadow:0 0 6px rgba(0,0,0,0.1); }
    h1 { text-align:center; margin:0 0 10px; }
    .error { color:#b00020; margin:10px 0; }
    .member-row { display:flex; gap:10px; margin-bottom:8px; }
    .member-row input { flex:1; padding:8px; }
    input[type=text] { width:100%; padding:8px; margin:6px 0 12px; }
    button { padding:10px 14px; border:0; border-radius:6px; cursor:pointer; background:#1f7a4f; color:#fff; }
    table { width:100%; border-collapse:collapse; margin-top:18px; }
    th, td { border:1px solid #ddd; padding:10px; vertical-align:top; }
    th { background:#f0f0f0; }
    a.del { background:#c0392b; color:#fff; padding:8px 10px; border-radius:6px; text-decoration:none; display:inline-block; }
    ul { margin:0; padding-left:18px; }
  </style>
</head>
<body>
<div class="container">
  <h1>University Groups (CRUD)</h1>

  <?php if (!empty($error)): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <h2>Create Group (exactly 5 members)</h2>
  <form method="post">
    <label>Group Name</label>
    <input type="text" name="group_name" required>

    <h3>Members</h3>
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <div class="member-row">
        <input type="text" name="member_name[]" placeholder="Member <?php echo $i; ?> Name" required>
        <input type="text" name="member_role[]" placeholder="Member <?php echo $i; ?> Role" required>
      </div>
    <?php endfor; ?>

    <button type="submit" name="create_group">Create Group</button>
  </form>

  <h2>All Groups</h2>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Group Name</th>
        <th>Members</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php if (count($groups) === 0): ?>
      <tr><td colspan="4">No groups found.</td></tr>
    <?php else: ?>
      <?php foreach ($groups as $gid => $g): ?>
        <tr>
          <td><?php echo (int)$gid; ?></td>
          <td><?php echo htmlspecialchars($g['group_name']); ?></td>
          <td>
            <ul>
              <?php foreach ($g['members'] as $m): ?>
                <li><?php echo htmlspecialchars($m['name']); ?> (<?php echo htmlspecialchars($m['role']); ?>)</li>
              <?php endforeach; ?>
            </ul>
          </td>
          <td>
            <a class="del" href="index.php?delete_group=<?php echo (int)$gid; ?>"
               onclick="return confirm('Delete this group?');">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
