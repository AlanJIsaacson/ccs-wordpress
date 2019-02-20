<?php
/**
 * Plugin Name:     CCS Salesforce Importer
 * Plugin URI:      http://www.studio24.net/
 * Description:     Imports required objects from Salesforce into Wordpress
 * Author:          Studio 24
 * Author URI:      http://www.studio24.net/
 * Text Domain:     ccs-salesforce-import
 * Version:         0.1.0
*/

// If this file is called directly, abort.
use App\Repository\FrameworkRepository;

if (!defined('WPINC')) {
    throw new Exception('You cannot access this file directly');
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
if (class_exists('WP_CLI')) {
    require __DIR__ . '/cli-commands.php';
}

require __DIR__ . '/PluginCore.php';

function get_framework_json(WP_REST_Request $request)
{
    if(isset($request['limit']))
    {
        $limit = (int) $request['limit'];
    }
    $limit = $limit ?? 10;

    if(isset($request['page']))
    {
        $page = (int) $request['page'];
    }
    $page = $page ?? 0;

    $frameworkRepository = new FrameworkRepository();
    $frameworkCount = $frameworkRepository->countAll();
    $frameworks = $frameworkRepository->findAll(true, $limit, $page);

    foreach ($frameworks as $index => $framework)
    {
        $frameworks[$index] = $framework->toArray();
    }

    $meta = [
      'total_results' => $frameworkCount,
      'limit'         => $limit,
      'results'       => count($frameworks),
      'page'          => $page == 0 ? 1 : $page
    ];


    header('Content-Type: application/json');
    echo json_encode(['meta' => $meta, 'results' => $frameworks]);
    exit;
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.1.0
 */
function run_plugin()
{
    $plugin = new PluginCore();

    register_activation_hook(__FILE__, array('PluginCore', 'activate'));
    register_deactivation_hook(__FILE__, array('PluginCore', 'deactivate'));

    add_action( 'rest_api_init', function () {
        register_rest_route( 'wp/v2', '/ccs/frameworks', array(
            'methods' => 'GET',
            'callback' => 'get_framework_json',
        ) );
    } );

    add_action( 'save_post', 'save_framework_meta' );

}

run_plugin();

function save_framework_meta($post_id) {
    $post_type = get_post_type($post_id);

    // If this isn't a 'framework' post, don't do anything
    if ($post_type != 'framework' ) {
        return;
    }

    $frameworkRepository = new FrameworkRepository();


    if (!$frameworkRepository->findById($post_id, 'wordpress_id')) {
        //add error
        return;
    };

    $framework = $frameworkRepository->findById($post_id, 'wordpress_id');

    if(isset($_POST['framework_summary']))
    {
        $framework->setSummary(sanitize_text_field( $_POST['framework_summary']));
    }

    if(isset($_POST['framework_description']))
    {
        $framework->setDescription(sanitize_text_field( $_POST['framework_description']));
    }

    if(isset($_POST['framework_benefits']))
    {
        $framework->setBenefits(sanitize_text_field( $_POST['framework_benefits']));
    }

    if(isset($_POST['framework_how_to_buy']))
    {
        $framework->setHowToBuy(sanitize_text_field( $_POST['framework_how_to_buy']));
    }

    $frameworkRepository->update('wordpress_id', $framework->getWordpressId(), $framework);

}


