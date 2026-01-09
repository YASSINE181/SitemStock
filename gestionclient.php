<?php
session_start();
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Connexion DB
$db = new PDO("mysql:host=localhost;dbname=sitemstock", 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Récupérer l'utilisateur
$req = $db->prepare("SELECT nom, email FROM utilisateur WHERE id = ?");
$req->execute([$_SESSION['user_id']]);
$user = $req->fetch(PDO::FETCH_ASSOC);
// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'ajouter':
            $stmt = $db->prepare("INSERT INTO client (nom, prenom, telephone, email, adresse) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['nom'],
                $_POST['prenom'],
                $_POST['telephone'],
                $_POST['email'],
                $_POST['adresse']
            ]);
            break;

        case 'modifier':
            $stmt = $db->prepare("UPDATE client SET nom=?, prenom=?, telephone=?, email=?, adresse=? WHERE id=?");
            $stmt->execute([
                $_POST['nom'],
                $_POST['prenom'],
                $_POST['telephone'],
                $_POST['email'],
                $_POST['adresse'],
                $_POST['id']
            ]);
            break;

        case 'supprimer':
            $stmt = $db->prepare("DELETE FROM client WHERE id=?");
            $stmt->execute([$_POST['id']]);
            break;
    }
    header("Location: gestionclient.php");
    exit;
}

// Récupérer clients
$clients = $db->query("SELECT * FROM client ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des Clients - SitemStock</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="utilisateur.css">
<link rel="stylesheet" href="gestionstock.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <!-- TOP NAVBAR -->
    <div class="top-navbar">
        <div class="user-profile">
            <div class="notification-bell">
                <i class="fas fa-bell fa-lg"></i>
                <span class="notification-badge">3</span>
            </div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="user-avatar">
                        <?= !empty($user['nom']) ? strtoupper(substr($user['nom'],0,1)) : 'U'; ?>
                    </div>
                    <div class="user-info ms-2">
                        <h5><?= !empty($user['nom']) ? htmlspecialchars($user['nom']) : 'Utilisateur'; ?></h5>
                        <p>Administrateur</p>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?action=logout"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </div>
    <!-- WELCOME -->
    <div class="welcome-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="fas fa-users me-2"></i> Gestion des Clients</h2>
                <p class="mb-0">Gérez l'ensemble des clients et leurs informations.</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterModal">
                    <i class="fas fa-plus me-1"></i> Ajouter un client
                </button>
            </div>
        </div>
    </div>
    <!-- TABLE CLIENTS -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Liste des clients (<?= count($clients); ?>)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Adresse</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?= $client['id'] ?></td>
                        <td><?= htmlspecialchars($client['nom']) ?></td>
                        <td><?= htmlspecialchars($client['prenom']) ?></td>
                        <td><?= htmlspecialchars($client['telephone']) ?></td>
                        <td><?= htmlspecialchars($client['email']) ?></td>
                        <td><?= htmlspecialchars($client['adresse']) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modifierModal" onclick='chargerClient(<?= json_encode($client) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="if(confirm('Supprimer ce client ?')) document.getElementById('del<?= $client['id'] ?>').submit();">
                                <i class="fas fa-trash"></i>
                            </button>
                            <form id="del<?= $client['id'] ?>" method="POST" style="display:none;">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= $client['id'] ?>">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($clients)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fas fa-user fa-2x mb-2"></i><br>
                            Aucun client enregistré
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- MODAL AJOUTER CLIENT -->
<div class="modal fade" id="ajouterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i> Ajouter un client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="ajouter">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom *</label>
                            <input class="form-control" name="nom" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom *</label>
                            <input class="form-control" name="prenom" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input class="form-control" name="telephone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" type="email">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <input class="form-control" name="adresse">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- MODAL MODIFIER CLIENT -->
<div class="modal fade" id="modifierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Modifier le client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom *</label>
                            <input class="form-control" name="nom" id="edit_nom" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom *</label>
                            <input class="form-control" name="prenom" id="edit_prenom" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input class="form-control" name="telephone" id="edit_telephone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" type="email" id="edit_email">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <input class="form-control" name="adresse" id="edit_adresse">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function chargerClient(client){
    document.getElementById('edit_id').value = client.id;
    document.getElementById('edit_nom').value = client.nom;
    document.getElementById('edit_prenom').value = client.prenom;
    document.getElementById('edit_telephone').value = client.telephone;
    document.getElementById('edit_email').value = client.email;
    document.getElementById('edit_adresse').value = client.adresse;
}
</script>
</body>
</html>

