<?php 
require "config.php";
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    /* ===== AJOUT ===== */
    if ($_POST['action'] === 'ajouter') {
        $nom = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);

        $check = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE email=?");
        $check->execute([$email]);

        if ($check->fetchColumn() > 0) {
            $message = "⚠️ Email déjà utilisé";
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO utilisateur (nom,email,mot_de_passe) VALUES (?,?,?)"
            );
            $stmt->execute([$nom, $email, $mot_de_passe]);
            $message = "✅ Utilisateur ajouté";}
    }

        if ($_POST['action'] === 'modifier') {
        $stmt = $pdo->prepare(
            "UPDATE utilisateur SET nom=?, email=? WHERE id=?"
        );
        $stmt->execute([
            $_POST['nom'],
            $_POST['email'],
            $_POST['id']
        ]);
        $message = "✏️ Utilisateur modifié";
    }

    if ($_POST['action'] === 'supprimer') {
        $stmt = $pdo->prepare(
            "UPDATE utilisateur SET etat=? WHERE id=?"
        );
        $stmt->execute([
            '0',
            $_POST['id']
        ]);
        $message = "✏️ Utilisateur modifié";
    }
}


$sql = "SELECT id, nom, email FROM utilisateur where etat='1'";
$stmt = $pdo->query($sql);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

 ?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Utilisateurs - SiteMStock</title>
    <link rel="stylesheet" href="style.css">
     <link rel="stylesheet" href="sidebar.css">
</head>
<!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<body>
     <?php include 'sidebar.php'; ?>
    <?php if (isset($_GET['deleted'])): ?>
   
<script>
    alert("Utilisateur supprimé avec succès !");
</script>
<?php endif; ?>
    
    

<h1>Liste des utilisateurs</h1>
<div class="text-center my-3">
    <button class="btn btn-success" onclick="ouvrirAjouterModal()">+ Ajouter</button>
</div>
<table>
    <tr>
        <th>Nom</th>
        <th>Email</th>
        <th>Action</th>
    </tr>

    <?php foreach ($utilisateurs as $u): ?>
    <tr data-id="<?= $u['id'] ?>">
        <td><?= htmlspecialchars($u['nom']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td>
             
    <button class="btn-modifier"
onclick="ouvrirModal(
<?= $u['id'] ?>,
'<?= addslashes($u['nom']) ?>',
'<?= addslashes($u['email']) ?>'
)">
Modifier</button>
            <button class="btn-supprimer" onclick="ouvrirModalSupp(<?= $u['id'] ?>)">
                Supprimer
            </button>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<link rel="stylesheet" href="style.css">
<!-- ================= MODAL AJOUT ================= -->
<div id="modalAjouter" class="modal" >
<div class="bg-white p-4 rounded" >
<h4>Ajouter utilisateur</h4>
<form method="post">
    <input type="hidden" name="action" value="ajouter">
    <input class="form-control mb-2" name="nom" placeholder="Nom" required>
    <input class="form-control mb-2" name="email" type="email" placeholder="Email" required>
    <input class="form-control mb-2" name="mot_de_passe" type="password" placeholder="Mot de passe" required>
    <button class="btn-success">Ajouter</button>
    <button type="button" class="btn-secondary" onclick="fermerAjouterModal()">Annuler</button>
</form>
</div>
</div>
<!-- MODAL MODIFIER -->
<div id="modalModifier" class="modal" >
    <div class="modal-content" >
        <span class="close" onclick="fermerModal()"
              style="position:absolute; top:10px; right:15px; font-size:20px; cursor:pointer;">&times;</span>
        <h2>Modifier utilisateur</h2>
        <form method="post">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="id" id="edit-id">
            <label>Nom</label>
            <input type="text" name="nom" id="edit-nom" required>
            <label>Email</label>
            <input type="email" name="email" id="edit-email" required>
            <button type="submit" class="btn-modifier">Enregistrer</button>
        </form>
    </div>
</div>

<!-- MODAL SUPP -->
<div id="modalSupp" class="modal" >
    <div class="modal-content" >
        <span class="close" onclick="fermerModalsup()"
              style="position:absolute; top:10px; right:15px; font-size:20px; cursor:pointer;">&times;</span>
        <h2>Supprimer utilisateur</h2>
        <form method="post">
            <input type="hidden" name="action" value="supprimer">
            <input type="hidden" name="id" id="user-id">
            <button type="submit" class="btn-supprimer">Supprimer</button>
        </form>
    </div>
</div>
<script src="script.js">
    </script>
   
</body>
</html>
