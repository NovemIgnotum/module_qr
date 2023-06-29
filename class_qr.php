<?php

use Com\Tecnick\Pdf\Tcpdf;
require_once('phpqrcode/qrlib.php');

class QR {
    
    private $serverName;
    private $user;
    private $passWord;
    private $bddName;
    private $connexion;    
    
    public function __construct($nomServer, $nomBdd, $userBdd, $pwd)
    {
        $this->serverName = $nomServer;
        $this->bddName = $nomBdd;
        $this->user = $userBdd;
        $this->passWord = $pwd;
    }

    // Fonction pour la connexion à la BDD
    private function getConnexion() {
        // Ouvrir la connexion à la base de données
        $connexion = mysqli_connect($this->serverName, $this->user, $this->passWord, $this->bddName);

        // Vérifier que la connexion à la base de données s'est bien déroulée
        if (!$connexion) {
            die('Erreur de connexion à la base de données : ' . mysqli_connect_error());
        }

        return $connexion;
    }

    private function closeConnexion() {
       mysqli_close($this->connexion);
    }

    // Vérifie l'unicité du nom de fichier du QR code
    public function verfUniciteQrcode($nomFichier) {
        $cheminFichier = "C:/dolibarr/www/dolibarr/htdocs/custom/".$nomFichier;
        if(file_exists($cheminFichier)) {
            echo "Enregistrement impossible, ce Qr code existe déjà.";
            $verif =  false;
        }
        else {
            $verif =  true;
        }
        return $verif;
    }

    // Génère et enregistre le QR code en fonction des données de commande
    public function createAndSaveQrcode() {
        $connexion = $this->getConnexion();

        // Récupérer l'ID de la dernière commande
        $requete = "SELECT MAX(rowid) AS last_id FROM llx_commande";
        $resultat = mysqli_query($connexion, $requete);
        $last_id = mysqli_fetch_assoc($resultat)['last_id'];

        if ($last_id > 0) {
            // Récupérer les informations de la commande
            $requete = "SELECT ref, date_creation, fk_soc, total_ht FROM llx_commande WHERE rowid = ?";
            $stmt = mysqli_prepare($connexion, $requete);
            if (!$stmt) {
                die('Erreur de préparation de la requête : ' . mysqli_error($connexion));
            }
            mysqli_stmt_bind_param($stmt, 'i', $last_id);
            if (!mysqli_stmt_execute($stmt)) {
                die('Erreur lors de l\'exécution de la requête : ' . mysqli_stmt_error($stmt));
            }
            $resultat = mysqli_stmt_get_result($stmt);
            $commande = mysqli_fetch_assoc($resultat);

            if ($commande !== null) {
                // Récupérer les produits de la commande
                $requete_produits = "SELECT `description`, qty, price FROM llx_commandedet WHERE fk_commande = ?";
                $stmt_produits = mysqli_prepare($connexion, $requete_produits);
                if (!$stmt_produits) {
                    die('Erreur de préparation de la requête : ' . mysqli_error($connexion));
                }
                mysqli_stmt_bind_param($stmt_produits, 'i', $last_id);
                if (!mysqli_stmt_execute($stmt_produits)) {
                    die('Erreur lors de l\'exécution de la requête : ' . mysqli_stmt_error($stmt_produits));
                }
                $resultat_produits = mysqli_stmt_get_result($stmt_produits);
                $produits = mysqli_fetch_all($resultat_produits, MYSQLI_ASSOC);
                if (!empty($produits)) {
                    $limite = count($produits);
                    $data = '';

                    // Générer les données à encoder dans le QR code
                    for ($i = 0; $i < $limite; $i++) {
                        $data .= "Nom produit : " . $produits[$i]["description"] . " | " . "Quantité : " . $produits[$i]["qty"] . " | " . " Prix :" . $produits[$i]["price"] . "€" . " | " . "\n";
                    }

                    $nom_fichier = $commande['ref'] . '.png';
                    $verif = $this->verfUniciteQrcode($nom_fichier);
                    if ($verif == true) {
                        // Générer le QR code et l'enregistrer dans un fichier
                        QRcode::png($data, './QrCode/' . $nom_fichier);

                        // Vérifier que le fichier QR code a bien été généré
                        if (file_exists('./QrCode/' . $nom_fichier)) {
                            echo 'Le QR code a été généré avec succès.';
                            $chemin_qrcode = './QrCode/' . $nom_fichier;

                            // Insérer le chemin du QR code dans la base de données
                            $requete = 'INSERT INTO llx_qrcode (chemin_qrcode) VALUES (?)';
                            $stmt = mysqli_prepare($connexion, $requete);

                            if (!$stmt) {
                                die('Erreur de préparation de la requête : ' . mysqli_error($connexion));
                            }

                            mysqli_stmt_bind_param($stmt, 's', $chemin_qrcode); // Lier le paramètre avec le chemin du QR code

                            if (!mysqli_stmt_execute($stmt)) {
                                die('Erreur lors de l\'exécution de la requête : ' . mysqli_stmt_error($stmt));
                            }

                            echo 'Le chemin du QR code a été enregistré dans la base de données.';
                        } else {
                            echo 'Une erreur s\'est produite lors de la génération du QR code.';
                        }
                    } else {
                        echo 'L\'enregistrement du QR code a échoué car il existe déjà.';
                    }
                } else {
                    echo 'La commande ne contient aucun produit.';
                }
            } else {
                echo 'La commande n\'existe pas.';
            }
        } else {
            echo 'Aucune commande n\'a été trouvée.';
        }

        $this->closeConnexion();
    }

    // Récupère les chemins des QR codes enregistrés dans la base de données
    public function getQrCodePaths() {
        $connexion = $this->getConnexion();

        $requete = "SELECT chemin_qrcode FROM llx_qrcode";
        $resultat = mysqli_query($connexion, $requete);

        $options = '';

        while ($row = mysqli_fetch_assoc($resultat)) {
            $chemin_qrcode = $row['chemin_qrcode'];
            $options .= "<option value=\"$chemin_qrcode\">$chemin_qrcode</option>";
        }

        $this->closeConnexion();

        return $options;
    }
}
/*
// Exemple d'utilisation
$qr = new QR('localhost', 'ma_bdd', 'mon_utilisateur', 'mon_mot_de_passe');

// Générer et enregistrer le QR code
$qr->createAndSaveQrcode();

// Obtenir les chemins des QR codes enregistrés
$qrCodePaths = $qr->getQrCodePaths();

// Utiliser les chemins des QR codes selon vos besoins
*/
?>
