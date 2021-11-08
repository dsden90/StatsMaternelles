<?php
class Circo {

	public static $instances = [];
	public static $nbCircos = 0;
	public static $nbPeriodes = 5;

	public $nom = '';
	public $nbEleves = 0; // concernés par un aménagement
	public $effectifPS = 0;
	public $eleves = [];
	public $periodes = [];

	public function __construct($nom) {
		$existe = false;
		$indexInstance = 0;

		// VÉRIFIE SI LA CIRCO EST DÉJÀ INSTANCIÉE (MÊME NOM)
		if(Circo::$nbCircos > 0) {
			for($i = Circo::$nbCircos-1; $i >= 0; $i--) {
				if(Circo::$instances[$i]->nom == $nom) {
					$existe = true;
					$indexInstance = $i;
				}
			}
		}
		if(!$existe) {
			$this->nom = $nom;
			Circo::$instances[count(Circo::$instances)] = $this;
			Circo::$nbCircos++;

			for($i = 0; $i <= Circo::$nbPeriodes; $i++) {
				$this->periodes[$i]["nbEleves"] = 0;
				$this->periodes[$i]["eleves"] = [];
			}
		} else {
			return Circo::$instances[$indexInstance];
		}
	}

	// Ajoute un élève de nom $nomeleve (clé unique pour la période) dans la période $p
	public function ajouteEleveDansPeriode($eleve, $p) {
		
		$estDejaInscrit = false;

		// parcourt les élèves de la période pour vérifier s'il est déjà inscrit dans la période
		if($this->periodes[$p]["nbEleves"] > 0) {
			for($i = 0; $i < $this->periodes[$p]["nbEleves"]; $i++) {
				if($this->periodes[$p]["eleves"][$i]->nom == $eleve->nom) {
					$estDejaInscrit = true;
					$i = $this->periodes[$p]["nbEleves"]; // casse la boucle for
				}
			}
		}

		if(!$estDejaInscrit) {
			// ajoute l'élève à la période
			$this->periodes[$p]["eleves"][$this->periodes[$p]["nbEleves"]] = $eleve;
			$this->periodes[$p]["nbEleves"]++;
		}
	}

	public function ajouteEffectif($nb) {
		$this->effectifPS += $nb;
	}

	public function recupStatParJour($periode) {
		$nbEleve1Jour = 0;
		$nbEleve2Jour = 0;
		$nbEleve3Jour = 0;
		$nbEleve4Jour = 0;

		// parcourt les élèves pour la période donnée et les compte en fonction du total de jour
		foreach($this->periodes[$periode]["eleves"] as $eleve) {
			switch($eleve->periodes[$periode]["total"]) {
				case 1:
					$nbEleve1Jour++;
					break;
				case 2:
					$nbEleve2Jour++;
					break;
				case 3:
					$nbEleve3Jour++;
					break;
				case 4:
					$nbEleve4Jour++;
					break;
			}
		}
		return [$nbEleve1Jour, $nbEleve2Jour, $nbEleve3Jour, $nbEleve4Jour];
	}

	public static function cherche($nom) {
		$retour = null;
		for($i = Circo::$nbCircos-1; $i >= 0; $i--) {
			if(Circo::$instances[$i]->nom == $nom) {
				$retour = Circo::$instances[$i];
			}
		}
		return $retour;
	}

}
?>