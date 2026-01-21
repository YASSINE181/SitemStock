<?php
session_start();

// Déconnexion
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Connexion DB
$db = new PDO("mysql:host=localhost;dbname=sitemstock;charset=utf8", 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupérer l'utilisateur
$req = $db->prepare("SELECT nom, email FROM utilisateur WHERE id = ?");
$req->execute([$_SESSION['user_id']]);
$user = $req->fetch();

// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter':
                $stmt = $db->prepare("INSERT INTO produit (nom, description , fournisseur_id, prix_achat, prix_vente, quantite_stock) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['nom'],
                    $_POST['description'],
                    $_POST['fournisseur_id'] ?: null,
                    $_POST['prix_achat'],
                    $_POST['prix_vente'],
                    $_POST['quantite_stock']
                ]);
                break;
                
            case 'modifier':
                $stmt = $db->prepare("UPDATE produit SET  nom = ?, description = ?, fournisseur_id = ?, prix_achat = ?, prix_vente = ?, quantite_stock = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['nom'],
                    $_POST['description'],
                    $_POST['fournisseur_id'] ?: null,
                    $_POST['prix_achat'],
                    $_POST['prix_vente'],
                    $_POST['quantite_stock'],
                    $_POST['id']
                ]);
                break;
                
            case 'supprimer':
                $stmt = $db->prepare("DELETE FROM produit WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                break;
        }
        header("Location: produit.php");
        exit;
    }
}

// Récupérer les produits
$produits = $db->query("SELECT * FROM produit ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fournisseurs pour formulaire
$fournisseurs = $db->query("SELECT * FROM fournisseur ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion du Stock - SitemStock</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="sidebar.css">
</head>
<body>
<div class="main-content">
<?php include 'sidebar.php'; ?>

<div class="welcome-section mb-3">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2><i class="fas fa-boxes me-2"></i> Gestion des produits</h2>
            <p class="mb-0">Gérez l'ensemble des produits, leur disponibilité et leurs informations.</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterModal"><i class="fas fa-plus me-1"></i> Ajouter un produit</button>
        </div>
    </div>
</div>
<div class="table-container p-3">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-dark">
                <tr >
                    <th>ID</th><th>Produit</th><th>Fournisseur_ID</th>
                    <th class="text-center">Stock</th><th class="text-end">Prix Achat</th><th class="text-end">Prix Vente</th>
                    <th class="text-end">Marge</th><th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($produits as $produit): ?>
                <tr>
                    <td><?= $produit['id'] ?></td>
                    <td><?= htmlspecialchars($produit['nom']) ?><br><small><?= htmlspecialchars(substr($produit['description'],0,50)) ?>...</small></td>
                    <td><?= htmlspecialchars($produit['fournisseur_id'] ?? 'Non défini') ?></td>
                    <td class="text-center">
                        <?php
                        $stock = $produit['quantite_stock'];
                        if($stock<=0) echo '<span class="badge bg-danger">Rupture</span>';
                        elseif($stock<=5) echo '<span class="badge bg-warning text-dark">'.$stock.'</span>';
                        else echo '<span class="badge bg-success">'.$stock.'</span>';
                        ?>
                    </td>
                    <td class="text-end"><?= number_format($produit['prix_achat'],2,',',' ') ?> DT</td>
                    <td class="text-end"><?= number_format($produit['prix_vente'],2,',',' ') ?> DT</td>
                    <td class="text-end"><?= number_format($produit['prix_vente']-$produit['prix_achat'],2,',',' ') ?> DT</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modifierModal" onclick='chargerProduit(<?= json_encode($produit, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#supprimerModal" onclick="document.getElementById('sup_id').value=<?= $produit['id']; ?>;"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($produits)): ?>
                <tr><td colspan="10" class="text-center">Aucun produit enregistré</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<!-- Modal Ajouter Produit -->
<div class="modal fade" id="ajouterModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<form method="POST" class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-plus me-1"></i> Ajouter un produit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <input type="hidden" name="action" value="ajouter">
        <div class="mb-3"><label>Nom *</label><input class="form-control" name="nom" required></div>
        <div class="mb-3"><label>Description</label><textarea class="form-control" name="description"></textarea></div>


        <!-- Fournisseur -->
        <div class="mb-3">
            <label>Fournisseur</label>
            <select class="form-control" name="fournisseur_id">
                <option value="">-- Sélectionnez --</option>
                <?php foreach($fournisseurs as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3"><label>Prix Achat *</label><input type="number" step="0.01" class="form-control" name="prix_achat" required></div>
        <div class="mb-3"><label>Prix Vente *</label><input type="number" step="0.01" class="form-control" name="prix_vente" required></div>
        <div class="mb-3"><label>Quantité Stock *</label><input type="number" class="form-control" name="quantite_stock" required></div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </div>
</form>
</div>
</div>

<!-- Modal Modifier Produit -->
<div class="modal fade" id="modifierModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<form method="POST" class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit me-1"></i> Modifier le produit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <input type="hidden" name="action" value="modifier">
        <input type="hidden" name="id" id="edit_id">
        <div class="mb-3"><label>Nom *</label><input class="form-control" name="nom" id="edit_nom" required></div>
        <div class="mb-3"><label>Description</label><textarea class="form-control" name="description" id="edit_description"></textarea></div>

       

        <!-- Fournisseur -->
        <div class="mb-3">
            <label>Fournisseur</label>
            <select class="form-control" name="fournisseur_id" id="edit_fournisseur_id">
                <option value="">-- Sélectionnez --</option>
                <?php foreach($fournisseurs as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3"><label>Prix Achat *</label><input type="number" step="0.01" class="form-control" name="prix_achat" id="edit_prix_achat" required></div>
        <div class="mb-3"><label>Prix Vente *</label><input type="number" step="0.01" class="form-control" name="prix_vente" id="edit_prix_vente" required></div>
        <div class="mb-3"><label>Quantité Stock *</label><input type="number" class="form-control" name="quantite_stock" id="edit_quantite_stock" required></div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </div>
</form>
</div>
</div>

<!-- Modal Supprimer Produit -->
<div class="modal fade" id="supprimerModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<form method="POST" class="modal-content">
    <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fas fa-trash me-1"></i> Supprimer le produit</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body text-center">
        <p>Voulez-vous vraiment supprimer ce produit ?</p>
        <input type="hidden" name="action" value="supprimer">
        <input type="hidden" name="id" id="sup_id">
    </div>
    <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-danger">Supprimer</button>
    </div>
</form>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function chargerProduit(produit){
    document.getElementById('edit_id').value = produit.id;
    document.getElementById('edit_nom').value = produit.nom;
    document.getElementById('edit_description').value = produit.description;
    document.getElementById('edit_fournisseur_id').value = produit.fournisseur_id;
    document.getElementById('edit_prix_achat').value = produit.prix_achat;
    document.getElementById('edit_prix_vente').value = produit.prix_vente;
    document.getElementById('edit_quantite_stock').value = produit.quantite_stock;
}
</script>
</body>
</html>