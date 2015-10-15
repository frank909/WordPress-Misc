<?php
$tax_name = 'quote_category';
$args = array(
	'hide_empty' => false,
	'hierarchical' => false,
	'parent' => 0
);
$taxonomies = get_terms($tax_name, $args);
echo '<ul class="taxonomy">';
foreach( $taxonomies as $taxonomy ){
	$link = get_term_link( $taxonomy, $tax_name );
	echo '<li>';
	echo '<a href="'.$link.'">';
	echo $taxonomy->name;
	echo '</a>';
	$sub_args = array(
		'hide_empty' => false,
		'hierarchical' => false,
		'parent' => 0
	);
	$sub_taxonomies = get_terms($tax_name, $sub_args);
	$children = get_term_children( $taxonomy->term_id, $tax_name );
	echo ' c= '.count($children);
	echo '</li>';
}
echo '</ul>';
?>