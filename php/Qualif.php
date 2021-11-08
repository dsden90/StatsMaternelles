<?php
class Qualif {

	public static $instances = [];
	public static $nbInstances = 0; // permet d'accéder au nombre d'instances dans une boucle sans calculer à chaque fois la longueur du tableau

	public $nom = '';
	public $effectif = 0;
	public $periodes = [];

	public function __construct($nom = '') {
		$this->nom = $nom;
		Qualif::$instances[count(Qualif::$instances)] = $this;
		Qualif::$nbInstances++;
		for($i = 0; $i <= 5; $i++) {
			$this->periodes[$i]["nbEleves"] = 0;
			$this->periodes[$i]["eleves"] = [];
		}
	}

	public static function init() {
		new Qualif('AQPV');
		new Qualif('QPV');
		new Qualif('REP');
		new Qualif('REP+');
		new Qualif('RPI');
		new Qualif('Vide');
	}

	public static function cherche($nom) {
		$qualif = null;
		for($i = Qualif::$nbInstances-1; $i >= 0; $i--) {
			if(Qualif::$instances[$i]->nom == $nom) {
				$qualif = Qualif::$instances[$i];
			}
		}
		return $qualif;
	}

	// ajoute $nb élèves dans l'effectif de la qualif pour la période $p
	public function ajouteEffectif($nb, $p = 0) {
		$this->effectif += $nb;
	}

	// Ajoute un élève de nom $nomeleve (clé unique pour la période) dans la période $p
	public function ajouteEleveDansPeriode($nomEleve, $p) {
		
		$estDejaInscrit = false;

		// parcourt les élèves de la période
		if($this->periodes[$p]["nbEleves"] > 0) {
			for($i = 0; $i < $this->periodes[$p]["nbEleves"]; $i++) {
				if($this->periodes[$p]["eleves"][$i] == $nomEleve) {
					$estDejaInscrit = true;
					$i = $this->periodes[$p]["nbEleves"];
				}
			}
		}

		// si l'élève n'est pas déjà inscrit dans la période, on l'ajoute
		if(!$estDejaInscrit) {
			$this->periodes[$p]["eleves"][$this->periodes[$p]["nbEleves"]] = $nomEleve;
			$this->periodes[$p]["nbEleves"]++;
		}
	}

}
?>