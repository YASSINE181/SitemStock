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
$user = $req->fetch();
// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter':
                $stmt = $db->prepare("INSERT INTO produit (reference, nom, description, categorie_id, fournisseur_id, prix_achat, prix_vente, quantite_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['reference'],
                    $_POST['nom'],
                    $_POST['description'],
                    $_POST['categorie_id'],
                    $_POST['fournisseur_id'],
                    $_POST['prix_achat'],
                    $_POST['prix_vente'],
                    $_POST['quantite_stock']
                ]);
                break;
            case 'modifier':
                $stmt = $db->prepare("UPDATE produit SET reference = ?, nom = ?, description = ?, categorie_id = ?, fournisseur_id = ?, prix_achat = ?, prix_vente = ?, quantite_stock = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['reference'],
                    $_POST['nom'],
                    $_POST['description'],
                    $_POST['categorie_id'],
                    $_POST['fournisseur_id'],
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
        header("Location: gestionstock.php");
        exit;
    }
}
// Récupérer les produits
$produits = $db->query("SELECT p.*, c.nom as categorie_nom, f.nom as fournisseur_nom FROM produit p 
                        LEFT JOIN categorie c ON p.categorie_id = c.id 
                        LEFT JOIN fournisseur f ON p.fournisseur_id = f.id 
                        ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer catégories et fournisseurs pour les formulaires
$categories = $db->query("SELECT * FROM categorie ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$fournisseurs = $db->query("SELECT * FROM fournisseur ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du Stock - SitemStock</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="utilisateur.css">
    <link rel="stylesheet" href="gestionstock.css">
</head>
<body>
    <!-- Inclusion de la sidebar -->
    <?php include 'sidebar.php'; ?>
    <!-- Contenu Principal -->
    <div class="main-content">
        <!-- Barre de Navigation Supérieure -->
        <div class="top-navbar">
            <div class="user-profile">
                <div class="notification-bell">
                    <i class="fas fa-bell fa-lg"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?php echo !empty($user['nom']) ? strtoupper(substr($user['nom'], 0, 1)) : 'U'; ?>
                        </div>
                        <div class="user-info ms-2">
                            <h5><?php echo !empty($user['nom']) ? htmlspecialchars($user['nom']) : 'Utilisateur'; ?></h5>
                            <p>Administrateur</p>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?action=logout"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- En-tête de la page -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-boxes me-2"></i> Gestion du Stock</h2>
                    <p class="mb-0">Gérez l'ensemble des produits, leur disponibilité et leurs informations.</p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterModal">
                        <i class="fas fa-plus me-1"></i> Ajouter un produit
                    </button>
                </div>
            </div>
        </div>
        <!-- Tableau des produits (sans cadre) -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Liste des produits (<?php echo count($produits); ?> produits)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Référence</th>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th>Fournisseur</th>
                            <th class="text-center">Stock</th>
                            <th class="text-end">Prix Achat</th>
                            <th class="text-end">Prix Vente</th>
                            <th class="text-end">Marge</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="produitsTable">
                        <?php foreach ($produits as $produit): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($produit['id']); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($produit['reference']); ?></strong></td>
                            <td>
                                <div><strong><?php echo htmlspecialchars($produit['nom']); ?></strong></div>
                                <small class="text-muted"><?php echo htmlspecialchars(substr($produit['description'], 0, 50)) . '...'; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($produit['categorie_nom'] ?? 'Non classé'); ?></td>
                            <td><?php echo htmlspecialchars($produit['fournisseur_nom'] ?? 'Non défini'); ?></td>
                            <td class="text-center">
                                <?php 
                                $stock = $produit['quantite_stock'];
                                if ($stock <= 0) {
                                    echo '<span class="badge bg-danger badge-stock">Rupture</span>';
                                } elseif ($stock <= 5) {
                                    echo '<span class="badge bg-warning text-dark badge-stock">'.$stock.'</span>';
                                } else {
                                    echo '<span class="badge bg-success badge-stock">'.$stock.'</span>';
                                }
                                ?>
                            </td>
                            <td class="text-end"><?php echo number_format($produit['prix_achat'], 2, ',', ' '); ?> €</td>
                            <td class="text-end"><?php echo number_format($produit['prix_vente'], 2, ',', ' '); ?> €</td>
                            <td class="text-end profit">
                                <?php 
                                $marge = $produit['prix_vente'] - $produit['prix_achat'];
                                echo number_format($marge, 2, ',', ' ') . ' €';
                                ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary btn-action" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modifierModal"
                                        onclick="chargerProduit(<?php echo htmlspecialchars(json_encode($produit)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-action"
                                        onclick="if(confirm('Voulez-vous vraiment supprimer ce produit ?')) { document.getElementById('form-supprimer-<?php echo $produit['id']; ?>').submit(); }">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <form id="form-supprimer-<?php echo $produit['id']; ?>" method="POST" style="display: none;">
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="id" value="<?php echo $produit['id']; ?>">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($produits)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-box-open fa-2x mb-2"></i><br>
                                Aucun produit enregistré
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Modal Ajouter Produit -->
    <div class="modal fade" id="ajouterModal" tabindex="-1" aria-labelledby="ajouterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ajouterModalLabel">
                            <i class="fas fa-plus-circle me-2"></i>Ajouter un nouveau produit
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Référence *</label>
                                <input type="text" class="form-control" name="reference" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom du produit *</label>
                                <input type="text" class="form-control" name="nom" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Catégorie</label>
                                <select class="form-select" name="categorie_id">
                                    <option value="">Sélectionner...</option>
                                    <?php foreach ($categories as $categorie): ?>
                                    <option value="<?php echo $categorie['id']; ?>"><?php echo htmlspecialchars($categorie['nom']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fournisseur</label>
                                <select class="form-select" name="fournisseur_id">
                                    <option value="">Sélectionner...</option>
                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                    <option value="<?php echo $fournisseur['id']; ?>"><?php echo htmlspecialchars($fournisseur['nom']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Prix d'achat (€) *</label>
                                <input type="number" class="form-control" name="prix_achat" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Prix de vente (€) *</label>
                                <input type="number" class="form-control" name="prix_vente" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Quantité en stock *</label>
                                <input type="number" class="form-control" name="quantite_stock" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le produit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Modifier Produit -->
    <div class="modal fade" id="modifierModal"  aria-labelledby="modifierModalLabel" >
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modifierModalLabel">
                            <i class="fas fa-edit me-2"></i>Modifier le produit
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Référence *</label>
                                <input type="text" class="form-control" name="reference" id="edit_reference" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom du produit *</label>
                                <input type="text" class="form-control" name="nom" id="edit_nom" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Catégorie</label>
                                <select class="form-select" name="categorie_id" id="edit_categorie_id">
                                    <option value="">Sélectionner...</option>
                                    <?php foreach ($categories as $categorie): ?>
                                    <option value="<?php echo $categorie['id']; ?>"><?php echo htmlspecialchars($categorie['nom']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fournisseur</label>
                                <select class="form-select" name="fournisseur_id" id="edit_fournisseur_id">
                                    <option value="">Sélectionner...</option>
                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                    <option value="<?php echo $fournisseur['id']; ?>"><?php echo htmlspecialchars($fournisseur['nom']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Prix d'achat (€) *</label>
                                <input type="number" class="form-control" name="prix_achat" id="edit_prix_achat" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Prix de vente (€) *</label>
                                <input type="number" class="form-control" name="prix_vente" id="edit_prix_vente" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Quantité en stock *</label>
                                <input type="number" class="form-control" name="quantite_stock" id="edit_quantite_stock" min="0" required>
                            </div>
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
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour charger les données dans le modal de modification
        function chargerProduit(produit) {
            document.getElementById('edit_id').value = produit.id;
            document.getElementById('edit_reference').value = produit.reference;
            document.getElementById('edit_nom').value = produit.nom;
            document.getElementById('edit_description').value = produit.description;
            document.getElementById('edit_categorie_id').value = produit.categorie_id;
            document.getElementById('edit_fournisseur_id').value = produit.fournisseur_id;
            document.getElementById('edit_prix_achat').value = produit.prix_achat;
            document.getElementById('edit_prix_vente').value = produit.prix_vente;
            document.getElementById('edit_quantite_stock').value = produit.quantite_stock;
        }
    </script>
</body>
</html>