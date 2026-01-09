<?php 
require "config.php";
$message = "";

/* ===== AJOUT FOURNISSEUR ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'ajouter') {
        $nom = trim($_POST['nom']);
        $nomLivreur = trim($_POST['nomLivreur']);
        $telephone = trim($_POST['telephone']);
        $email = trim($_POST['email']);
        $adresse = trim($_POST['adresse']);

        // Vérifier email déjà existant
        $check = $pdo->prepare("SELECT COUNT(*) FROM fournisseur WHERE email=?");
        $check->execute([$email]);

        if ($check->fetchColumn() > 0) {
            $message = "⚠️ Email déjà utilisé";
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO fournisseur (nom, nomLivreur, telephone, email, adresse, etat)
                 VALUES (?, ?, ?, ?, ?, 1)"
            );
            $stmt->execute([$nom, $nomLivreur, $telephone, $email, $adresse]);
            $message = "✅ Fournisseur ajouté";
        }
    }

    if ($_POST['action'] === 'modifier') {
        $stmt = $pdo->prepare(
            "UPDATE fournisseur SET nom=?, nomLivreur=?, telephone=?, email=?, adresse=? WHERE id=?"
        );
        $stmt->execute([
            $_POST['nom'],
            $_POST['nomLivreur'],
            $_POST['telephone'],
            $_POST['email'],
            $_POST['adresse'],
            $_POST['id']
        ]);
        $message = "✏️ Fournisseur modifié";
    }

    if ($_POST['action'] === 'supprimer') {
        $stmt = $pdo->prepare("UPDATE fournisseur SET etat=? WHERE id=?");
        $stmt->execute(['0', $_POST['id']]);
        $message = "🗑️ Fournisseur supprimé";
    }
}

/* ===== LISTE FOURNISSEURS ===== */
$sql = "SELECT id, nom, nomLivreur, telephone, email, adresse FROM fournisseur WHERE etat='1'";
$stmt = $pdo->query($sql);
$fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fournisseurs - SiteMStock</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="four.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h1>Nos fournisseurs</h1>

    <div class="text-center my-3">
<button type="button" class="btn-ajout" onclick="ouvrirAjouterModal()">
    + Ajouter fournisseur
</button>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Nom Livreur</th>
            <th>Téléphone</th>
            <th>Email</th>
            <th>Adresse</th>
            <th>Action</th>
        </tr>
        <?php foreach ($fournisseurs as $f): ?>
        <tr>
            <td><?= htmlspecialchars($f['id']) ?></td>
            <td><?= htmlspecialchars($f['nom']) ?></td>
            <td><?= htmlspecialchars($f['nomLivreur']) ?></td>
            <td><?= htmlspecialchars($f['telephone']) ?></td>
            <td><?= htmlspecialchars($f['email']) ?></td>
            <td><?= htmlspecialchars($f['adresse']) ?></td>
            <td>
                <button class="btn-modifier"
                    onclick="ouvrirModifierModal(
                        <?= $f['id'] ?>,
                        '<?= addslashes($f['nom']) ?>',
                        '<?= addslashes($f['nomLivreur']) ?>',
                        '<?= addslashes($f['telephone']) ?>',
                        '<?= addslashes($f['email']) ?>',
                        '<?= addslashes($f['adresse']) ?>'
                    )">Modifier</button>
                <button class="btn-supprimer" onclick="ouvrirSupprimerModal(<?= $f['id'] ?>)">Supprimer</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- MODAL AJOUT -->
<div id="modalAjouter" class="modal">
    <div class="modal-content">
        <span class="close" onclick="fermerAjouterModal()">&times;</span>
        <h3>Ajouter fournisseur</h3>
       <form method="post" action="fournisseur.php">
    <input type="hidden" name="action" value="ajouter">

    <input type="text" name="nom" placeholder="Nom" required>
    <input type="text" name="nomLivreur" placeholder="Nom Livreur" required>
    <input type="text" name="telephone" placeholder="Téléphone" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="adresse" placeholder="Adresse" required>

    <button type="submit" class="btn-ajout">Ajouter</button>
</form>

    </div>
</div>

<!-- MODAL MODIFIER -->
<div id="modalModifier" class="modal">
    <div class="modal-content">
        <span class="close" onclick="fermerModifierModal()">&times;</span>
        <h3>Modifier fournisseur</h3>
        <form method="post">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="id" id="edit-id">
            <input name="nom" id="edit-nom" placeholder="Nom" required>
            <input name="nomLivreur" id="edit-nomLivreur" placeholder="Nom Livreur" required>
            <input name="telephone" id="edit-telephone" placeholder="Téléphone" required>
            <input name="email" id="edit-email" type="email" placeholder="Email" required>
            <input name="adresse" id="edit-adresse" placeholder="Adresse" required>
            <button type="submit" class="btn-modifier">Enregistrer</button>
        </form>
    </div>
</div>

<!-- MODAL SUPPRIMER -->
<div id="modalSupprimer" class="modal">
    <div class="modal-content">
        <span class="close" onclick="fermerSupprimerModal()">&times;</span>
        <h3>Supprimer fournisseur</h3>
        <form method="post">
            <input type="hidden" name="action" value="supprimer">
            <input type="hidden" name="id" id="delete-id">
            <button type="submit" class="btn-supprimer">Supprimer</button>
        </form>
    </div>
</div>

<script src="scriptF.js">
    </script>
   

</body>
</html>
