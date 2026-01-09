//ajout
function ouvrirAjouterModal() {
    document.getElementById('modalAjouter').style.display = 'flex';
}
function fermerAjouterModal() {
    document.getElementById('modalAjouter').style.display = 'none';
}
//modif
function ouvrirModifierModal(id, nom, nomLivreur, telephone, email, adresse) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nom').value = nom;
    document.getElementById('edit-nomLivreur').value = nomLivreur;
    document.getElementById('edit-telephone').value = telephone;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-adresse').value = adresse;
    document.getElementById('modalModifier').style.display = 'flex';
}
function fermerModifierModal() { document.getElementById('modalModifier').style.display='none'; }
//supp
function ouvrirSupprimerModal(id) {
    document.getElementById('delete-id').value = id;
    document.getElementById('modalSupprimer').style.display = 'flex';
}
function fermerSupprimerModal() { document.getElementById('modalSupprimer').style.display='none'; }
