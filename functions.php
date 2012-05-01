<?php

define('RUTTER_URL_VERSION', '1');
define('RUTTER_TAX_PREFIX_PROJECT', '@');
define('RUTTER_TAX_PREFIX_TAG', '#');

include('controller.php');

function cfrutter_gatekeeper() {
	if (!current_user_can('publish_posts')) {
		$login_page = site_url('wp-login.php');
		is_ssl() ? $proto = 'https://' : $proto = 'http://';
		$requested = $proto.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		if (substr($requested, 0, strlen($login_page)) != $login_page) {
			auth_redirect();
		}
	}
}
add_action('init', 'cfrutter_gatekeeper');	

function cfrutter_resources() {
	wp_enqueue_script('jquery');
	wp_enqueue_script(
		'rutter',
		trailingslashit(get_bloginfo('template_url')).'js/rutter.js',
		array('jquery'),
		RUTTER_URL_VERSION,
		true
	);
	wp_localize_script('rutter', 'rutter', array(
			'endpointAjax' => home_url('index.php')
	));
}
add_action('wp_enqueue_scripts', 'cfrutter_resources');

function cfrutter_register_taxonomies() {
	register_taxonomy(
		'projects',
		'post',
		array(
			'hierarchical' => true,
			'label' => __('Projects'),
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array(
				'slug' => 'projects',
				'with_front' => false,
			),
		)
	);
	register_taxonomy(
		'evergreen',
		'post',
		array(
			'hierarchical' => true,
			'label' => __('Evergreen'),
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array(
				'slug' => 'evergreen',
				'with_front' => false,
			),
		)
	);
}
add_action('init', 'cfrutter_register_taxonomies');

function cfrutter_get_the_terms($terms, $id, $taxonomy) {
	// this was getting called twice for post_tag, not sure why
	global $RUTTER_TAX_FILTERED;
	if (!isset($RUTTER_TAX_FILTERED)) {
		$RUTTER_TAX_FILTERED = array();
	}
	if (is_array($terms) && count($terms) && !in_array($taxonomy, $RUTTER_TAX_FILTERED)) {
		$RUTTER_TAX_FILTERED[] = $taxonomy;
		switch ($taxonomy) {
			case 'projects':
				$_terms = array();
				foreach ($terms as $term_id => $term) {
					$term->name = RUTTER_TAX_PREFIX_PROJECT.$term->name;
					$_terms[$term_id] = $term;
				}
				$terms = $_terms;
				break;
			case 'post_tag':
				$_terms = array();
				foreach ($terms as $term_id => $term) {
					$term->name = RUTTER_TAX_PREFIX_TAG.$term->name;
					$_terms[$term_id] = $term;
				}
				$terms = $_terms;
				break;
		}
	}
	return $terms;
}
add_filter('get_the_terms', 'cfrutter_get_the_terms', 10, 3);
