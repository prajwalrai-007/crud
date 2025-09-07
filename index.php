<?php
// index.php
require __DIR__ . '/db.php';

// Simple router via ?action=
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// CSRF token (very simple)
session_start();
if (empty($_SESSION['token'])) {
  $_SESSION['token'] = bin2hex(random_bytes(32));
}
function checkToken() {
  if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
    http_response_code(400);
    exit('Invalid CSRF token');
  }
}

// CREATE
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  checkToken();
  $name  = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');

  // basic validation
  if ($name === '' || $email === '') {
    $error = 'Name and Email are required.';
  } else {
    $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone) VALUES (:name, :email, :phone)");
    $stmt->execute([':name'=>$name, ':email'=>$email, ':phone'=>$phone]);
    header('Location: index.php?msg=created'); exit;
  }
}

// UPDATE
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  checkToken();
  $name  = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $id    = (int)($_POST['id'] ?? 0);

  if ($id <= 0) { exit('Bad ID'); }
  if ($name === '' || $email === '') {
    $error = 'Name and Email are required.';
  } else {
    $stmt = $pdo->prepare("UPDATE contacts SET name=:name, email=:email, phone=:phone WHERE id=:id");
    $stmt->execute([':name'=>$name, ':email'=>$email, ':phone'=>$phone, ':id'=>$id]);
    header('Location: index.php?msg=updated'); exit;
  }
}

// DELETE
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  checkToken();
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) { exit('Bad ID'); }
  $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = :id");
  $stmt->execute([':id'=>$id]);
  header('Location: index.php?msg=deleted'); exit;
}

// Fetch record if editing
$editRow = null;
if ($action === 'edit' && $id) {
  $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = :id");
  $stmt->execute([':id'=>$id]);
  $editRow = $stmt->fetch();
  if (!$editRow) { exit('Record not found'); }
}

// Read (list)
$stmt = $pdo->query("SELECT * FROM contacts ORDER BY id DESC");
$rows = $stmt->fetchAll();

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>PHP CRUD (PDO + MySQL)</title>
  <style>
    body{font-family:system-ui,Arial;padding:24px;max-width:900px;margin:auto}
    table{border-collapse:collapse;width:100%}
    th,td{border:1px solid #ddd;padding:8px}
    th{background:#f3f3f3;text-align:left}
    form{margin:0}
    .row{display:flex;gap:12px;flex-wrap:wrap}
    .card{border:1px solid #ddd;border-radius:8px;padding:16px;margin:16px 0}
    .btn{padding:6px 10px;border:1px solid #888;border-radius:6px;background:#fafafa;cursor:pointer}
    .btn-danger{border-color:#d33;background:#fee}
    .msg{padding:10px;border:1px solid #8bc34a;background:#f1fff1;margin-bottom:16px;border-radius:6px}
    .error{padding:10px;border:1px solid #e53935;background:#fff2f2;margin-bottom:16px;border-radius:6px}
    input[type=text],input[type=email]{padding:8px;width:100%;max-width:300px}
  </style>
</head>
<body>
  <h1>Contacts (CRUD)</h1>

  <?php if (!empty($_GET['msg'])): ?>
    <div class="msg"><?= h($_GET['msg']) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="error"><?= h($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <h2><?= $editRow ? 'Edit Contact' : 'Add New Contact' ?></h2>
    <form method="post" action="index.php?action=<?= $editRow ? 'update' : 'create' ?>">
      <input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>">
      <?php if ($editRow): ?>
        <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
      <?php endif; ?>
      <div class="row">
        <label>
          Name<br>
          <input type="text" name="name" value="<?= h($editRow['name'] ?? '') ?>" required>
        </label>
        <label>
          Email<br>
          <input type="email" name="email" value="<?= h($editRow['email'] ?? '') ?>" required>
        </label>
        <label>
          Phone<br>
          <input type="text" name="phone" value="<?= h($editRow['phone'] ?? '') ?>">
        </label>
      </div>
      <p>
        <button class="btn" type="submit"><?= $editRow ? 'Update' : 'Create' ?></button>
        <?php if ($editRow): ?>
          <a class="btn" href="index.php">Cancel</a>
        <?php endif; ?>
      </p>
    </form>
  </div>

  <h2>All Contacts</h2>
  <table>
    <thead>
      <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Created</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= h($r['name']) ?></td>
          <td><?= h($r['email']) ?></td>
          <td><?= h($r['phone']) ?></td>
          <td><?= h($r['created_at']) ?></td>
          <td>
            <a class="btn" href="index.php?action=edit&id=<?= (int)$r['id'] ?>">Edit</a>
            <form style="display:inline" method="post" action="index.php?action=delete" onsubmit="return confirm('Delete this contact?')">
              <input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="6">No contacts yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
