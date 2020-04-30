<?php

// Renseigner le nom de votre boutique Shopify

$shop = $_GET['shop'];

// Renseigner les différentes A.P.I génerées en ligne après la création de l'application
$api_key = "eba38ac7de6d2500d0b6397f1c32bd70";
$scopes = "read_orders,write_orders,write_products,read_themes,write_themes,write_script_tags,read_script_tags";

// Renseigner l'adresse correct menant vers votre fichier de génération de jeton d'A.P.I
/**
 *Veuillez noter que cette adresse correspond à votre application locale ou distante, bien faire attention 
 * Ici par exemple, l'application est hébergée en ligne et l'U.R.L est par conséquent celui en ligne
 */
$redirect_uri = "https://shopify-apps.bananafw.com/thecobblers/my-account/generate_token.php";

// Ne rien toucher ici, Pas besoin d'encoder votre U.R.L
$install_url = "https://" . $shop . ".myshopify.com/admin/oauth/authorize?client_id=" . $api_key . "&scope=" . $scopes . "&redirect_uri=" .$redirect_uri;

// Redirect
header("Location: " . $install_url);
die();