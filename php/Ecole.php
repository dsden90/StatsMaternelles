<?php
class Ecole {

	public static $instances = [];
	public static $nbEcole = 0;

	public $nom = '';
	public $qualif = '';
	public $eleves = [];
	public $nbEleves = 0;
	public $effectif = 0;
	public $circo = 0;
	public $periodes = [];
	public $moyenneNbAmenagements = 0;
	public $moyenneTauxAmenagements = 0;

	public function __construct($nom = '', $nomQualif = 'Vide') {
		$this->nom = $nom;
		$this->qualif = $nomQualif;

		for($i = 0; $i <= 5; $i++) {
			$this->periodes[$i]['nbEleves'] = 0;
			$this->periodes[$i]['eleves'] = [];
		}

		Self::$instances[count(Self::$instances)] = $this;
		Self::$nbEcole = count(Self::$instances);
	}

	// Ajoute un élève de nom $nomeleve (clé unique pour la période) dans la période $p
	public function ajouteEleveDansPeriode($eleve, $p) {
		
		$estDansPeriode = false;
		$estDansEcole = false;

		for($iEleve = 0; $iEleve < $this->nbEleves; $iEleve++) {
			if($this->eleves[$iEleve] == $eleve) {
				$estDansEcole = true;
				$iEleve = $this->nbEleves; // sort de la boucle
			}
		}

		// parcourt les élèves de la période
		for($i = 0; $i < $this->periodes[$p]['nbEleves']; $i++) {
			if($this->periodes[$p]['eleves'][$i] == $eleve->nom) {
				$estDansPeriode = true;
				$i = $this->periodes[$p]['nbEleves'];
			}
		}

		// si l'élève n'est pas déjà inscrit dans l'école, on l'ajoute
		if(!$estDansEcole) {
			$this->eleves[$this->nbEleves] = $eleve;
			$this->nbEleves++;
		}

		// si l'élève n'est pas déjà inscrit dans la période, on l'ajoute
		if(!$estDansPeriode) {
			$this->periodes[$p]['eleves'][$this->periodes[$p]['nbEleves']] = $eleve->nom;
			$this->periodes[$p]['nbEleves']++;
		}
	}

	public static function init() {
		new Ecole('Argiesans', 'RPI');
		new Ecole('Belfort Saint Exupéry', 'REP');
		new Ecole('Belfort V Schoelcher', 'QPV');
		new Ecole('Bethonvilliers', 'RPI');
		new Ecole('Boron', 'RPI');
		new Ecole('Dreyfus Schmidt', 'REP');
		new Ecole('Foussemagne', 'RPI');
		new Ecole('Gehant', 'QPV');
		new Ecole('Grosmagny', 'RPI');
		new Ecole('Jaures', 'AQPV');
		new Ecole('Martin Luther King', 'REP+');
		new Ecole('Pergaud', 'REP+');
		new Ecole('Rucklin', 'REP');
		new Ecole('Sermamagny', 'RPI');
		new Ecole('Vetrigne', 'RPI');
		new Ecole('Vezelois', 'RPI');
		new Ecole('Villars Le Sec', 'RPI');
	}

	public static function cherche($nomEcole) {
		$ecole = false;
		for($i = count(Self::$instances)-1; $i >= 0; $i--) {
			if(Self::$instances[$i]->nom == $nomEcole) {
				$ecole = Self::$instances[$i];
			}
		}
		return $ecole;
	}

	public static function calculTouteMoyenne() {
		for($iEcole = count(Self::$instances)-1; $iEcole >= 0; $iEcole--) {
			// On calcul la moyenne d'aménagement par période pour les périodes où il y a des aménagements
			$totalAmenagements = 0;
			$totalPeriodes = 0;
			for($periode = 1; $periode <= 5; $periode++) {
				if(Self::$instances[$iEcole]->periodes[$periode]['nbEleves'] > 0) {
					$totalAmenagements += Self::$instances[$iEcole]->periodes[$periode]['nbEleves'];
					$totalPeriodes++;
				}
			}
			if($totalPeriodes != 0) {
				Self::$instances[$iEcole]->moyenneNbAmenagements = $totalAmenagements/$totalPeriodes;
				Self::$instances[$iEcole]->moyenneTauxAmenagements = Self::$instances[$iEcole]->moyenneNbAmenagements/Self::$instances[$iEcole]->effectif;
			}
		}
	}

	public static function ordonnePar($nomVariable, $ordre = 'croissant') {
		$nbInstances = count(Self::$instances);
		if($nbInstances > 0) {
			$type = gettype(Self::$instances[0]->$nomVariable);
			switch($type) {
				case 'integer':
				case 'double':
					if($ordre == 'croissant') {
						for($i = 0; $i < $nbInstances-1; $i++) {
							for($j = 0; $j < $nbInstances-1-$i; $j++) {
								if(Self::$instances[$j]->$nomVariable > Self::$instances[$j+1]->$nomVariable) {
									$instanceTampon = Self::$instances[$j];
									Self::$instances[$j] = Self::$instances[$j+1];
									Self::$instances[$j+1] = $instanceTampon;
								}
							}
						}
					} else if($ordre == 'decroissant') {
						for($i = 0; $i < $nbInstances-1; $i++) {
							for($j = 0; $j < $nbInstances-1-$i; $j++) {
								if(Self::$instances[$j]->$nomVariable < Self::$instances[$j+1]->$nomVariable) {
									$instanceTampon = Self::$instances[$j];
									Self::$instances[$j] = Self::$instances[$j+1];
									Self::$instances[$j+1] = $instanceTampon;
								}
							}
						}
					}
					break;
				case 'string':
					if($ordre == 'croissant') {
						for($i = 0; $i < $nbInstances-1; $i++) {
							for($j = 0; $j < $nbInstances-1-$i; $j++) {
								if(strcasecmp(Self::$instances[$j]->$nomVariable, Self::$instances[$j+1]->$nomVariable) > 0) {
									$instanceTampon = Self::$instances[$j];
									Self::$instances[$j] = Self::$instances[$j+1];
									Self::$instances[$j+1] = $instanceTampon;
								}
							}
						}
					} else if($ordre == 'decroissant') {
						for($i = 0; $i < $nbInstances-1; $i++) {
							for($j = 0; $j < $nbInstances-1-$i; $j++) {
								if(strcasecmp(Self::$instances[$j]->$nomVariable, Self::$instances[$j+1]->$nomVariable) < 0) {
									$instanceTampon = Self::$instances[$j];
									Self::$instances[$j] = Self::$instances[$j+1];
									Self::$instances[$j+1] = $instanceTampon;
								}
							}
						}
					}
					break;
				default:
					Mrgr::log("\nAppel de la méthode Ecole::ordonnePar($nomVariable) avec une variable autre que integer, double ou string. (".$nomVariable." de type ".$type.")", 'screen', 'error');
					break;

			}
		} else {
			Mrgr::log("\nTentative d\'appel de la méthode Ecole::ordonnePar() alors qu\'aucune instance n\'est enregistrée", 'screen', 'error');
		}
		
		// Mrgr::log("\nAppel de Ecole::ordonnePar(".$nomVariable.', '.$ordre.'). Retour : ');
		// for($i = 0; $i < $nbInstances-1; $i++) {
		// 	Mrgr::log("   ".Self::$instances[$i]->nom.' ('.Self::$instances[$i]->$nomVariable.')');
		// }
	}
}
?>