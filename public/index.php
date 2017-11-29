<?php

require_once '../vendor/autoload.php';

/*
 * L'objet application représente le site. C'est l'objet principal de Silex par lequel
 * nous passerons pratiquement tout le temps pour déployer de nouvelles fonctionnalités
 */
$app = new \Silex\Application();

require_once '../config/db.php';

/*
 * La méthode get de l'objet Application permet de dire à Silex d'exécuter un code spécifique si 
 * une uri est atteinte par la méthode http GET. Ce code est inclus dans une fonction
 * ou n'importe quel autre callable
 */
$app->get('/home', function(\Silex\Application $app){
    return $app['twig']->render('home.html.twig');
})->bind('home');

// on crée une deuxième route associée à l'uri /listusers
$app->get('/listusers', function(\Silex\Application $app) {
    /*
     * Je récupère une liste d'utilisateurs grâce à mon modèle UserDAO
     */
    $users = $app['users.dao']->findMany();
    /*
     * Ma liste d'utilisateurs est transmise à mon template au moyen d'un 
     * tableau associatif
     */
    return $app['twig']->render('listusers.html.twig', [
        'users' => $users
    ]);
})->bind('listusers');

$app->get('/profile/{id}', function($id, \Silex\Application $app){
    $user = $app['users.dao']->find($id);
    
    return $app['twig']->render('profile.html.twig', [
        'user' => $user
    ]);
})->bind('profile');

/*
 * La classe Application implémente une interface spéciale propre à PHP,
 * appelée ArrayAccess. Cette interface permet d'utiliser notre objet comme si
 * il s'agissait d'un tableau. L'objet conserve malgré tout ses caractéristiques
 * d'objet (méthodes, champs...)
 */
/*
 * On passe par une fonction au lieu d'instancier directement notre objet afin
 * de n'instancier notre service qu'une seule fois et seulement si nécessaire.
 * Cette syntaxe permet d'économiser de la mémoire.
 */
$app['users.dao'] = function($app) {
    return new \DAO\UserDAO($app['pdo']);
};

$app['pdo'] = function($app) {
    $options = $app['pdo.options'];
    return new \PDO("{$options['sgbdr']}://host={$options['host']};dbname={$options['dbname']};charset={$options['charset']}",
            $options['username'], 
            $options['password'], 
            array(
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ));
};

/*
 * Les services peuvent être enregistrés via des Service Providers qui sont des classes
 * dont l'unique but est de déclarer des services.
 */

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/views',
    'twig.options' => array(
        'debug' => true
    )
));

// Pour lancer l'application il ne faut pas oublier d'appeler la méthode run de app
$app->run();