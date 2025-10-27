<?php
/*********************************************
 * FOOD INVENTORY MANAGEMENT SYSTEM
 * Single File | Professional Design | CRUD
 * Author: ChatGPT GPT-5
 *********************************************/

// DATABASE CONFIGURATION
$host = "localhost";
$dbname = "food_inventory";
$username = "root";
$password = "";

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("<div style='padding:20px;color:red;'>❌ Database connection failed: " . $e->getMessage() . "</div>");
}

// AUTO UPDATE STATUS (Expired / Low Stock)
$today = date('Y-m-d');
$pdo->query("UPDATE foods SET status='Expired' WHERE expiry_date < '$today'");
$pdo->query("UPDATE foods SET status='Low Stock' WHERE quantity < 5 AND expiry_date >= '$today'");
$pdo->query("UPDATE foods SET status='Available' WHERE quantity >= 5 AND expiry_date >= '$today'");

// HANDLE CRUD OPERATIONS
$action = $_GET['action'] ?? '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare("INSERT INTO foods (name, category, quantity, unit, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->execute([$_POST['name'], $_POST['category'], $_POST['quantity'], $_POST['unit'], $_POST['expiry_date'], $_POST['status']]);
  header("Location: index.php?success=added");
  exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare("UPDATE foods SET name=?, category=?, quantity=?, unit=?, expiry_date=?, status=? WHERE id=?");
  $stmt->execute([$_POST['name'], $_POST['category'], $_POST['quantity'], $_POST['unit'], $_POST['expiry_date'], $_POST['status'], $_GET['id']]);
  header("Location: index.php?success=updated");
  exit;
}

if ($action === 'delete') {
  $stmt = $pdo->prepare("DELETE FROM foods WHERE id=?");
  $stmt->execute([$_GET['id']]);
  header("Location: index.php?success=deleted");
  exit;
}

$foods = $pdo->query("SELECT * FROM foods ORDER BY expiry_date ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Food Inventory System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f6f8fa; font-family: 'Segoe UI', sans-serif; }
    .navbar { background: #0d6efd; color: white; }
    .navbar-brand { color: white; font-weight: bold; }
    .card { box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; }
    .btn-primary { background-color: #0d6efd; }
    footer { margin-top: 50px; text-align: center; font-size: 14px; color: #666; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg mb-4">
  <div class="container-fluid">
    <span class="navbar-brand">🍱 Food Inventory Management</span>
  </div>
</nav>

<div class="container">

<?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    ✅ Item successfully <?= htmlspecialchars($_GET['success']) ?>!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if ($action === 'create' || $action === 'update'): ?>
  <?php
    $food = ['name'=>'','category'=>'','quantity'=>'','unit'=>'','expiry_date'=>'','status'=>'Available'];
    if ($action === 'update') {
      $stmt = $pdo->prepare("SELECT * FROM foods WHERE id=?");
      $stmt->execute([$_GET['id']]);
      $food = $stmt->fetch(PDO::FETCH_ASSOC);
    }
  ?>
  <div class="card p-4">
    <h4><?= $action === 'create' ? '➕ Add New Food Item' : '✏️ Edit Food Item' ?></h4>
    <form method="POST" class="mt-3">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($food['name']) ?>" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Category</label>
          <input type="text" name="category" value="<?= htmlspecialchars($food['category']) ?>" class="form-control" required>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">Quantity</label>
          <input type="number" name="quantity" value="<?= htmlspecialchars($food['quantity']) ?>" class="form-control" required>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Unit</label>
          <input type="text" name="unit" value="<?= htmlspecialchars($food['unit']) ?>" class="form-control" placeholder="kg, pcs" required>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Expiry Date</label>
          <input type="date" name="expiry_date" value="<?= htmlspecialchars($food['expiry_date']) ?>" class="form-control" required>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <?php foreach(['Available','Low Stock','Expired'] as $st): ?>
              <option value="<?= $st ?>" <?= $food['status']==$st?'selected':'' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="d-flex justify-content-between">
        <button class="btn btn-success" type="submit"><?= $action==='create'?'Add Item':'Save Changes' ?></button>
        <a href="index.php" class="btn btn-secondary">Back</a>
      </div>
    </form>
  </div>

<?php else: ?>

  <div class="d-flex justify-content-between mb-3">
    <h4>📦 Inventory List</h4>
    <a href="?action=create" class="btn btn-primary">Add Food Item</a>
  </div>

  <table class="table table-hover table-bordered align-middle">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Category</th>
        <th>Quantity</th>
        <th>Unit</th>
        <th>Expiry</th>
        <th>Status</th>
        <th width="160">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($foods): foreach($foods as $food): ?>
        <tr>
          <td><?= $food['id'] ?></td>
          <td><?= htmlspecialchars($food['name']) ?></td>
          <td><?= htmlspecialchars($food['category']) ?></td>
          <td><?= htmlspecialchars($food['quantity']) ?></td>
          <td><?= htmlspecialchars($food['unit']) ?></td>
          <td><?= htmlspecialchars($food['expiry_date']) ?></td>
          <td>
            <?php
              $badge = [
                'Available'=>'success',
                'Low Stock'=>'warning text-dark',
                'Expired'=>'danger'
              ];
            ?>
            <span class="badge bg-<?= $badge[$food['status']] ?? 'secondary' ?>">
              <?= htmlspecialchars($food['status']) ?>
            </span>
          </td>
          <td>
            <a href="?action=update&id=<?= $food['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <a href="?action=delete&id=<?= $food['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="8" class="text-center">No food items found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>

<footer class="mt-4 mb-3">
  <p>© <?= date('Y') ?> Food Inventory Management </p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
