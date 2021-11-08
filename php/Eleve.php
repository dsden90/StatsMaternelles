<?php
class Eleve {

	public static $instances = [];
	public static $nbEleves = 0;

	public $circo = 0;
	public $ecole = '';
	public $nom = '';
	public $periodes = [];

	public function __construct($nom, $ecole, $circo) {
		$existe = false;
		$indexInstance = 0;

		// VÉRIFIE SI L'ÉLÈVE EST DÉJÀ INSTANCIÉ (MÊME NOM, MÊME ÉCOLE)
		if(Eleve::$nbEleves > 0) {
			for($i = Eleve::$nbEleves-1; $i >= 0; $i--) {
				if(Eleve::$instances[$i]->nom == $nom) {
					if(Eleve::$instances[$i]->ecole == $ecole) {
						$existe = true;
						$indexInstance = $i;
					}
				}
			}
		}
		if(!$existe) {
			$this->nom = $nom;
			$this->ecole = $ecole;
			$this->circo = $circo;
			$this->periodes[0] = 0; // réservée pour infos diverses
			for($i = 1; $i <= 5; $i++) {
				$this->periodes[$i] = array(
					"lundi" => 0,
					"mardi" => 0,
					"jeudi" => 0,
					"vendredi" => 0,
					"total" => 0
				);
			}
			Eleve::$instances[count(Eleve::$instances)] = $this;
			Eleve::$nbEleves++;
		} else {
			return Eleve::$instances[$indexInstance];
		}
	}


	// $valeurs est un tableau contenant des 1 pour les jours avec aménagement type [1, 0, 0, 1]
	public function setPeriode($periode, $valeurs) {
		$this->periodes[$periode]["lundi"] = $valeurs[0];
		$this->periodes[$periode]["mardi"] = $valeurs[1];
		$this->periodes[$periode]["jeudi"] = $valeurs[2];
		$this->periodes[$periode]["vendredi"] = $valeurs[3];
		$this->periodes[$periode]["total"] = $valeurs[0]+$valeurs[1]+$valeurs[2]+$valeurs[3];
	}

	public static function cherche($nom, $nomEcole) {
		$retour = null;
		for($i = count(Eleve::$instances)-1; $i >= 0; $i--) {
			if(Eleve::$instances[$i]->nom == $nom) {
				if(Eleve::$instances[$i]->ecole == $nomEcole) {
					$retour = Eleve::$instances[$i];
				}
			}
		}
		return $retour;
	}

	public static function getEleve($nom) {
		$eleve = null;
		for($i = Eleve::$nbEleves-1; $i >= 0; $i--) {
			if(Eleve::$instances[$i]->nom == $nom) {
				$eleve = Eleve::$instances[$i];
			}
		}
		return $eleve;
	}
}
?>