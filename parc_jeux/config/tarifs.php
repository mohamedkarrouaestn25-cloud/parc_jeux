<?php
// Tarifs des billets (en DH). Le "type" correspond à l'attribut type de l'entité BILLET.
// validite_jours : 0 = valable le jour de la visite uniquement.
return [
    'enfant' => ['nom' => 'Enfant', 'prix' => 60, 'note' => 'par enfant',
        'description' => "Pour les 3 à 11 ans, avec accès aux jeux adaptés à leur âge.", 'validite_jours' => 0],
    'adulte' => ['nom' => 'Adulte', 'prix' => 90, 'note' => 'par personne',
        'description' => "Entrée au parc et accès aux jeux selon les conditions de chacun.", 'validite_jours' => 0],
    'famille' => ['nom' => 'Famille', 'prix' => 260, 'note' => 'pour 4 personnes', 'badge' => 'Le plus choisi',
        'description' => "2 adultes et 2 enfants, à un tarif inférieur à 4 billets séparés.", 'validite_jours' => 0],
    'pass_journee' => ['nom' => 'Pass journée', 'prix' => 160, 'note' => 'par personne',
        'description' => "Tous les jeux en illimité, de l’ouverture à la fermeture.", 'validite_jours' => 0],
    'abonnement' => ['nom' => 'Abonnement', 'prix' => 700, 'note' => 'par personne, 30 jours',
        'description' => "Accès illimité au parc pendant 30 jours à partir de la date choisie.", 'validite_jours' => 30],
];
