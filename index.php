<?php

/** 
 * require the file "config.php"; copy the file "config-sample.php" as a new file "config.php", and fill in appropriate values
 * require running data/db-with-dummy-data-mysql.sql
 */




// Redirect www.somedomain.com to somedomain.com, comment out the following lines if not applicable
if(substr($_SERVER['HTTP_HOST'], 0, 4) === 'www.') {
    header("HTTP/1.1 301 Moved Permanently"); 
    header('Location: http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 's':'').'://' . substr($_SERVER['HTTP_HOST'], 4).$_SERVER['REQUEST_URI']);
    exit;
}


// turn on error displaying
error_reporting(E_ALL);
ini_set("display_errors", 1);


require 'vendor/autoload.php';
require 'config.php';


// turn off error displaying is debug mode is off
if(!DEBUG_MODE) {
    error_reporting(0);
    ini_set("display_errors", 0);
}



/** 
 * Setup Idiorm (ORM)
 */
ORM::configure('mysql:host='.DB_HOST.';dbname='.DB_NAME);
ORM::configure('username', DB_USER);
ORM::configure('password', DB_PASSWORD);
ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
ORM::configure('logging', DB_LOG);


/** 
 * Setup Twig (Template Engine)
 */
Twig_Autoloader::register();
$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader, array(
    'cache' => false,
    'debug' => DEBUG_MODE,
));





$app = new \Slim\Slim();



// front page, loads the latest posts
$app->get(
    '/', 
    function () use ($app, $twig) {
    
    // Get posts
    $posts = ORM::for_table('post')
        ->order_by_desc('created_at')
        ->limit(100)
        ->offset(0)
        ->find_many();

    // Get categories to show on the sidebar
    $categories = ORM::for_table('category')
        ->find_many();

    echo $twig->render('front.html', array(
        'current_uri' => $app->request->getResourceUri(), 
        'base_url' => BASE_URL, 
        'navigation_bar_items' => Settings::$navigation_bar_items, 
        'categories' => $categories, 
        'posts' => $posts));


});



// post page, shows the post's content
$app->get(
    '/post/:id', 
    function ($id) use ($app, $twig) {
    
    // Get post
    $post = ORM::for_table('post')
        ->where('id', $id)
        ->find_one();

    // Get categories to show on the sidebar
    $categories = ORM::for_table('category')
        ->find_many();

    echo $twig->render('post.html', array(
        'current_uri' => $app->request->getResourceUri(), 
        'base_url' => BASE_URL, 
        'navigation_bar_items' => Settings::$navigation_bar_items, 
        'categories' => $categories, 
        'post' => $post));

})->conditions(array('id' => '\d+'));



// category page, shows the posts that belong to the category
$app->get(
    '/category/:id', 
    function ($id) use ($app, $twig) {


    // Get category
    $selected_category = ORM::for_table('category')
        ->where('id', $id)
        ->find_one();
    
    // Get posts
    $posts = ORM::for_table('post')
        ->where('category_id', $id)
        ->order_by_desc('created_at')
        ->limit(100)
        ->offset(0)
        ->find_many();

    // Get categories to show on the sidebar
    $categories = ORM::for_table('category')
        ->find_many();

    echo $twig->render('category.html', array(
        'current_uri' => $app->request->getResourceUri(), 
        'base_url' => BASE_URL, 
        'navigation_bar_items' => Settings::$navigation_bar_items, 
        'selected_category' => $selected_category, 
        'categories' => $categories, 
        'posts' => $posts));

})->conditions(array('id' => '\d+'));



// about page, shows the about page (a demo static page)
$app->get(
    '/about', 
    function () use ($app, $twig) {

    // Get categories to show on the sidebar
    $categories = ORM::for_table('category')
        ->find_many();
    
    echo $twig->render('about.html', array(
        'current_uri' => $app->request->getResourceUri(), 
        'base_url' => BASE_URL, 
        'navigation_bar_items' => Settings::$navigation_bar_items, 
        'categories' => $categories));

});




$app->run();
