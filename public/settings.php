<?php
require_once __DIR__ . '/../src/helpers.php';
ensure_files();
$config = read_json('config.json', ['TEST_MODE'=>true,'APP_URL'=>'','API_KEY'=>'','API_SECRET'=>'','ACCESS_TOKEN'=>'']);
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $config['TEST_MODE'] = isset($_POST['TEST_MODE']) ? true : false;
  $config['APP_URL'] = $_POST['APP_URL'] ?? $config['APP_URL'];
  $config['API_KEY'] = $_POST['API_KEY'] ?? '';
  $config['API_SECRET'] = $_POST['API_SECRET'] ?? '';
  $config['ACCESS_TOKEN'] = $_POST['ACCESS_TOKEN'] ?? '';
  write_json('config.json',$config);
  header("Location: settings.php?saved=1"); exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Settings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php render_nav('settings'); ?>
  <h2 class="mb-3">Settings</h2>
  <?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
  <div class="card-dark">
    <form method="post">
      <div class="mb-2">
        <label class="form-label">APP_URL (auto)</label>
        <input class="form-control input-dark" name="APP_URL" value="<?=htmlspecialchars($config['APP_URL'] ?: app_url())?>">
        <div class="small mt-1">Used to build webhook URLs.</div>
      </div>
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="TEST_MODE" id="TEST_MODE" <?=$config['TEST_MODE']?'checked':''?>>
        <label class="form-check-label" for="TEST_MODE">Enable TEST_MODE (simulate prices & orders)</label>
      </div>
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label">Upstox API Key</label>
          <input class="form-control input-dark" name="API_KEY" value="<?=htmlspecialchars($config['API_KEY'])?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">API Secret</label>
          <input class="form-control input-dark" name="API_SECRET" value="<?=htmlspecialchars($config['API_SECRET'])?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Access Token</label>
          <input class="form-control input-dark" name="ACCESS_TOKEN" value="<?=htmlspecialchars($config['ACCESS_TOKEN'])?>">
        </div>
      </div>
      <div class="mt-3">
        <button class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
<?php render_nav_end(); ?>
</body>
</html>
