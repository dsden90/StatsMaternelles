<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Sheet {
	
	public static $sheets = [];
	public static $fichier;
	public static $nomsColonnes = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z', 'AA', 'AB', 'AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];

	public $nom;
	public $libele;
	public $sheet;
	public $ligne = 1;

	public function __construct($nom, $libele) {
		$this->nom = $nom;
		$this->libele = $libele;
		$this->sheet = new Worksheet(Sheet::$fichier, $libele);

		Sheet::$fichier->addSheet($this->sheet, count(Sheet::$sheets));
		Sheet::$sheets[count(Sheet::$sheets)] = $this;
	}

	public function ajouteLigne($valeurs) {
		$nbValeurs = count($valeurs);
		for($i = 0; $i < $nbValeurs; $i++) {
			$this->sheet->setCellValue(Sheet::$nomsColonnes[$i].$this->ligne, $valeurs[$i]);
		}
		$this->ligne++;
	}

	public function ajusteLargeurColonne($listeColonne) {
		foreach ($listeColonne as $iColonne) {
			$this->sheet->getColumnDimension($iColonne)->setAutoSize(true);
		}
	}

	public function ajouteFiltres() {
		$nbLettres = count(Sheet::$nomsColonnes);
		$lettreMax = Sheet::$nomsColonnes[0]; // cherche la colonne max où il y a une en-tête

		// on parcours les colonnes dans l'ordre
		for($lettreCoordIndex = 0; $lettreCoordIndex <= $nbLettres; $lettreCoordIndex++) {
			// si on arrive sur une colonne qui n'a pas d'en-tête
			if($this->sheet->getCell(Sheet::$nomsColonnes[$lettreCoordIndex].'1')->getValue() == '') {
				// on retient la lettre de la colonne précédente
				$lettreMax = $lettreCoordIndex-1;
				// et on sort de la boucle
				$lettreCoordIndex = $nbLettres+1;
			}
		}

		// on fait pareil avec les lignes
		$nombreMax = 1;
		// on parcours les lignes dans l'ordre (jusqu'à 6000 max)
		for($nombreCoordIndex = 1; $nombreCoordIndex <= 6000; $nombreCoordIndex++) {
			// si on arrive sur une ligne qui n'a pas de contenu
			if($this->sheet->getCell('A'.$nombreCoordIndex)->getValue() == '') {
				// on retient le numéro de ligne
				$nombreMax = $nombreCoordIndex-1;
				// et on sort de la boucle
				$nombreCoordIndex = 6001;
			}
		}

		$this->sheet->setAutoFilter('A1:'.Sheet::$nomsColonnes[$lettreMax].''.$nombreMax);
	}

	public static function getSheet($nom) {
		for($i = count(Sheet::$sheets)-1; $i >= 0; $i--) {
			if(Sheet::$sheets[$i]->nom == $nom) {
				return Sheet::$sheets[$i];
			}
		}
	}

}
?>