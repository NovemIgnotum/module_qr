<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       qrcode2/qrcode2index.php
 *	\ingroup    qrcode2
 *	\brief      Home page of qrcode2 top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("qrcode2@qrcode2"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->qrcode2->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

/****************************************************** 
		Affichage du module
******************************************************/


llxHeader("", $langs->trans("Qrcode2"));

print load_fiche_titre($langs->trans("Module Qrcode"), '', 'qrcode2.png@qrcode2');

print '<div class="fichecenter"><div class="fichethirdleft">';

print '<h1>Bienvenue dans le module de création de Qr Code</h1>';

print'<p> Ce module à pour but de créer les QR codes après avoir créer
une commande. <br> Pour cela rien de plus simple il suffit de cliquer sur générer.</p>';

echo '<form action="action.php" method="POST">';
echo '<label for="qrCodePath">Chemin du code QR :</label>';
echo '<select id="qrCodePath" name="qrCodePath">';
echo '<option value="derniere_commande">Générer le QR code de la dernière commande</option>';

// Chemin du dossier contenant les QR codes
$qrCodeFolder = "QrCode/";

// Vérifier si le dossier existe
if (is_dir($qrCodeFolder)) {
    // Ouvrir le dossier
    $dirHandle = opendir($qrCodeFolder);
    
    // Parcourir les fichiers du dossier
    while (($file = readdir($dirHandle)) !== false) {
        $filePath = $qrCodeFolder . $file;
        
        // Vérifier si le fichier est un code QR (par exemple, en vérifiant l'extension du fichier)
        if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) == "png") {
            $fileName = $file;
            echo "<option value='$fileName'>$fileName</option>";
        }
    }
    
    // Fermer le gestionnaire de dossier
    closedir($dirHandle);
}

echo '</select><br><br>';

echo '<label for="quantity">Nombre de QR code (Max 24):</label>';
echo '<input type="number" id="quantity" name="quantity" min="1" max="24"><br><br>';


echo '<input type="submit" name="generatePrintPage" value="Générer la page pour impression">';
echo '</form>';

echo '<form action="genererQRcode.php" method="POST">';
echo '<input type="submit" name="generateQRCode" value="Générer le QR code">';
echo '</form>';



/*FIN DU MODULE DEVELOPPER PAR NOS SOINS */


print '</div><div class="fichetwothirdright">';


$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div>';

// End of page
llxFooter();
$db->close();
