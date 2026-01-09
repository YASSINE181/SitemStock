 //ajout
 function ouvrirAjouterModal(){
    modalAjouter.style.display="flex";
}
function fermerAjouterModal(){
    modalAjouter.style.display="none";
}
//modif
 function ouvrirModal(id, nom, email) {
    document.getElementById("edit-id").value = id;
    document.getElementById("edit-nom").value = nom;
    document.getElementById("edit-email").value = email;
        document.getElementById("modalModifier").style.display = "flex";

}

function fermerModal() {
    document.getElementById("modalModifier").style.display = "none";
}
    function supprimerLigne(button) {
        if (!confirm("Voulez-vous vraiment supprimer cet utilisateur de l'affichage ?")) return;

        let row = button.closest("tr");
        
    }



    //supp
    function ouvrirModalSupp(id) {
    document.getElementById("user-id").value = id;
        document.getElementById("modalSupp").style.display = "flex";

}
function fermerModalsup() {
    document.getElementById("modalSupp").style.display = "none";

}
