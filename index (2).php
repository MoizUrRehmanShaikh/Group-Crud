<?php
include "db.php";

/* ---------- CREATE (group + 5 members) ---------- */
if (isset($_POST['create'])) {
    $group_name = trim($_POST['group_name'] ?? "");
    $names = $_POST['member_name'] ?? [];
    $roles = $_POST['member_role'] ?? [];

    $valid = ($group_name !== "");
    for ($i = 0; $i < 5; $i++) {
        if (!isset($names[$i]) || trim($names[$i]) === "") $valid = false;
        if (!isset($roles[$i]) || trim($roles[$i]) === "") $valid = false;
    }

    if (!$valid) {
        $error = "Group name required and all 5 members must be filled.";
    } else {
        $stmt = $conn->prepare("INSERT INTO student_groups (group_name) VALUES (?)");
        $stmt->bind_param("s", $group_name);
        $stmt->execute();
        $group_id = $stmt->insert_id;
        $stmt->close();

        $stmtM = $conn->prepare("INSERT INTO members (group_id, member_name, member_role) VALUES (?,?,?)");
        for ($i = 0; $i < 5; $i++) {
            $n = trim($names[$i]);
            $r = trim($roles[$i]);
            $stmtM->bind_param("iss", $group_id, $n, $r);
            $stmtM->execute();
        }
        $stmtM->close();

        header("Location: index.php");
        exit;
    }
}

/* ---------- DELETE group ---------- */
if (isset($_GET['delete'])) {
    $gid = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM student_groups WHERE id=?");
    $stmt->bind_param("i", $gid);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}

/* ---------- UPDATE (group + 5 members) ---------- */
if (isset($_POST['update'])) {
    $gid = (int)($_POST['group_id'] ?? 0);
    $group_name = trim($_POST['group_name'] ?? "");
    $names = $_POST['member_name'] ?? [];
    $roles = $_POST['member_role'] ?? [];

    $valid = ($gid > 0 && $group_name !== "");
    for ($i = 0; $i < 5; $i++) {
        if (!isset($names[$i]) || trim($names[$i]) === "") $valid = false;
        if (!isset($roles[$i]) || trim($roles[$i]) === "") $valid = false;
    }

    if (!$valid) {
        $error = "Update failed: group name + all 5 members are required.";
    } else {
        // update group name
        $stmt = $conn->prepare("UPDATE student_groups SET group_name=? WHERE id=?");
        $stmt->bind_param("si", $group_name, $gid);
        $stmt->execute();
        $stmt->close();

        // easiest strict update: delete old members then insert 5 again
        $stmtD = $conn->prepare("DELETE FROM members WHERE group_id=?");
        $stmtD->bind_param("i", $gid);
        $stmtD->execute();
        $stmtD->close();

        $stmtM = $conn->prepare("INSERT INTO members (group_id, member_name, member_role) VALUES (?,?,?)");
        for ($i = 0; $i < 5; $i++) {
            $n = trim($names[$i]);
            $r = trim($roles[$i]);
            $stmtM->bind_param("iss", $gid, $n, $r);
            $stmtM->execute();
        }
        $stmtM->close();

        header("Location: index.php");
        exit;
    }
}

/* ---------- READ for list ---------- */
$groups = [];
$sql = "SELECT g.id AS gid, g.group_name, m.id AS mid, m.member_name, m.member_role
        FROM student_groups g
        LEFT JOIN members m ON g.id = m.group_id
        ORDER BY g.id, m.id";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $gid = (int)$row['gid'];
    if (!isset($groups[$gid])) {
        $groups[$gid] = ['group_name' => $row['group_name'], 'members' => []];
    }
    if (!empty($row['mid'])) {
        $groups[$gid]['members'][] = ['name' => $row['member_name'], 'role' => $row['member_role']];
    }
}

/* ---------- READ for edit form ---------- */
$editMode = false;
$editGroupId = 0;
$editGroupName = "";
$editMembers = array_fill(0, 5, ['name' => '', 'role' => '']);

if (isset($_GET['edit'])) {
    $editMode = true;
    $editGroupId = (int) $_GET['edit'];

    $stmt = $conn->prepare("SELECT group_name FROM student_groups WHERE id=?");
    $stmt->bind_param("i", $editGroupId);
    $stmt->execute();
    $stmt->bind_result($editGroupName);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT member_name, member_role FROM members WHERE group_id=? ORDER BY id ASC LIMIT 5");
    $stmt->bind_param("i", $editGroupId);
    $stmt->execute();
    $result = $stmt->get_result();

    $i = 0;
    while ($m = $result->fetch_assoc()) {
        if ($i < 5) {
            $editMembers[$i] = ['name' => $m['member_name'], 'role' => $m['member_role']];
            $i++;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Group CRUD (5 Members)</title>
  <style>
    body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0}
    .wrap{max-width:1050px;margin:22px auto;background:#fff;padding:18px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.08)}
    h1{margin:0 0 12px;text-align:center}
    .error{background:#ffe7ea;color:#8a0012;padding:10px;border-radius:8px;margin:10px 0}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .card{border:1px solid #e6e6e6;border-radius:10px;padding:14px}
    label{display:block;font-weight:bold;margin-top:10px}
    input{width:100%;padding:9px;border:1px solid #d7d7d7;border-radius:8px;margin-top:6px}
    .row{display:flex;gap:10px;margin-top:10px}
    .row input{flex:1}
    button,a.btn{display:inline-block;margin-top:12px;padding:10px 12px;border:0;border-radius:8px;text-decoration:none;cursor:pointer}
    button{background:#1f7a4f;color:#fff}
    a.btn{background:#666;color:#fff}
    table{width:100%;border-collapse:collapse;margin-top:16px}
    th,td{border:1px solid #ddd;padding:10px;vertical-align:top}
    th{background:#f0f0f0}
    a.action{padding:7px 10px;border-radius:8px;color:#fff;text-decoration:none;display:inline-block;margin-right:6px}
    a.edit{background:#2563eb}
    a.del{background:#dc2626}
    ul{margin:0;padding-left:18px}
    @media(max-width:900px){.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="wrap">
  <h1>Group CRUD App (Exactly 5 Members)</h1>

  <?php if (!empty($error)): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <div class="grid">
    <div class="card">
      <h2><?php echo $editMode ? "Edit Group" : "Create Group"; ?></h2>

      <form method="post">
        <?php if ($editMode): ?>
          <input type="hidden" name="group_id" value="<?php echo (int)$editGroupId; ?>">
        <?php endif; ?>

        <label>Group Name</label>
        <input type="text" name="group_name" required
               value="<?php echo htmlspecialchars($editMode ? $editGroupName : ""); ?>">

        <label>Members (5 required)</label>
        <?php for ($i=0; $i<5; $i++): ?>
          <div class="row">
            <input type="text" name="member_name[]" required
                   placeholder="Member <?php echo $i+1; ?> Name"
                   value="<?php echo htmlspecialchars($editMembers[$i]['name'] ?? ''); ?>">
            <input type="text" name="member_role[]" required
                   placeholder="Member <?php echo $i+1; ?> Role"
                   value="<?php echo htmlspecialchars($editMembers[$i]['role'] ?? ''); ?>">
          </div>
        <?php endfor; ?>

        <?php if ($editMode): ?>
          <button type="submit" name="update">Update Group</button>
          <a class="btn" href="index.php">Cancel</a>
        <?php else: ?>
          <button type="submit" name="create">Create Group</button>
        <?php endif; ?>
      </form>
    </div>

    <div class="card">
      <h2>How it works</h2>
      <ul>
        <li>You must fill group name + all 5 member fields.</li>
        <li>Edit replaces the 5 members (delete old + insert new).</li>
        <li>Delete removes group and members (CASCADE).</li>
      </ul>
    </div>
  </div>

  <h2 style="margin-top:18px;">All Groups</h2>
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
      <tr><td colspan="4">No groups yet.</td></tr>
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
            <a class="action edit" href="index.php?edit=<?php echo (int)$gid; ?>">Edit</a>
            <a class="action del" href="index.php?delete=<?php echo (int)$gid; ?>"
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
