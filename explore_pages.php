<?
/*
Plugin Name: Explore pages
Plugin URI: http://mbyte.org.ua/
Description: explorer-like page navigation.
Version: 1.01
Author: mByte
Author URI: http://mbyte.org.ua/personal/
*/

/*  Copyright 2007-2009  mByte  (email : mbyte@mbyte.org.ua)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
    
*/


add_action('admin_menu', 'explore_pages_amenu');
add_action('admin_head', 'explore_pages_head');

function explore_pages_amenu() {
	add_menu_page('explore pages', 'explore pages', 3, __FILE__, 'explore_pages_it');
}

function explore_pages_head() {
if (preg_match("/explore\_pages/",$_SERVER['REQUEST_URI'])) {
	?>
<link rel="stylesheet" type="text/css" href="../wp-content/plugins/explore-pages/tree/tree_component.css" />
<script type="text/javascript" src="../wp-content/plugins/explore-pages/tree/css.js"></script>
<script type="text/javascript" src="../wp-content/plugins/explore-pages/tree/jquery.listen.js"></script>
<script type="text/javascript" src="../wp-content/plugins/explore-pages/tree/tree_component.js"></script>
<script type="text/javascript">

var last_edited = "";

function open_blank(href) {
	form = document.createElement("form");
	form.method = "GET";
	form.action = href;
	form.target = "_blank";
	document.body.appendChild(form);
	form.submit();
	jQuery(form).remove();
}

jQuery(document).ready(function() { 
	tree1 = new tree_component(); 
	tree1.init(jQuery("#explore_pages-tree"),{callback:{onchange:function(node, tree_obj){
		if (last_edited != "") {
			jQuery("#"+last_edited+" small:first").remove();
		}
		last_edited = jQuery(node).attr("id");
		
		jQuery("#"+last_edited+" a:first").after("<small>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			+ "| <b onClick='javascript:open_blank(\""+jQuery("#"+last_edited+" a:first").attr("rel")+"\");' class='link'><? print __("View");?><\/b> "
			+ "| <b onClick='document.location.href=\"admin.php?page=explore-pages/explore_pages.php&createin=1&post_parent="+jQuery("#"+last_edited).attr("rel")+"\";' class='link'><? print __("Add");?><\/b> "
			+ "| <b onClick='document.location.href=\"page.php?action=edit&post="+jQuery("#"+last_edited).attr("rel")+"\";' class='link'><? print __("Edit");?><\/b> "
			+ "| <b onClick='if(confirm(\"Do you realy want to delete this page?\")) {document.location.href=\"admin.php?page=explore-pages/explore_pages.php&delete="+jQuery("#"+last_edited).attr("rel")+"\";}' class='link' style='color: red;'><? print __("Delete");?><\/b><\/small>");
		
		
	}}});
}); 

</script>
<?
}
}

function explore_pages_it() {
	global $wpdb, $user_ID;
	
	if (isset($_GET['delete'])) {
		$id = intval($_GET['delete']);
		
		$par = $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE `ID`='$id' LIMIT 1;");
		$par = $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE `ID`='$par' LIMIT 1;");
		
		if ( !wp_delete_post($id) ) 
			wp_die( __('Error when deleting post...') );
		
		print "<script type='text/javascript'>document.location.href='admin.php?page=explore-pages/explore_pages.php&opento=$par&message=".urlencode("Page deleted success")."';</script>";
		exit();
	}
	
	if (isset($_GET['createin'])) {
		$par = intval($_GET['post_parent']);
		$newpage = array(
		  'post_status' => 'publish',
	  	  'post_type' => 'page',
  		  'post_author' => $user_ID,
		  'ping_status' => get_option('default_ping_status'),
		  'post_parent' => $par,
		  'menu_order' => 0,
		  'post_title' => 'New page',
		  'post_content' => '',
		  'post_category' => array(0)
		  
		);
		$newid = wp_insert_post($newpage);
		if ($newid) {
			print "<script type='text/javascript'>document.location.href='admin.php?page=explore-pages/explore_pages.php&opento=$par&message=".urlencode("".__("Page added success")." <a href='page.php?action=edit&post=".$newid."'>".__("Edit")."</a>")."';</script>";
		} else {
			wp_die( __('Can\'t create post post...') );
		}
		exit();
	}
	
	print "
	<div class='wrap'>
	<h2>Pages</h2>
	";
	
	if(isset($_GET['message'])) {
		$message = $_GET['message'];
		print '<div style="background-color: rgb(207, 235, 247);" id="message" class="updated fade">
					 <p>'.$message.'</p></div>';
	}
	
	if (isset($_GET['parent'])) {
		$parent = intval($_GET['parent']);
	} else {
		$parent = 0;
	}
	
	if ($parent != 0) { 
		$mypage = &get_post($parent); 
		$p_title = $mypage->post_title;
	} else {
		$p_title = "Root";
	}
	
	print "<h3><a href='admin.php?page=explore-pages/explore_pages.php&createin=1&post_parent=".$parent."'>Create page in \"".$p_title."\"</a></h3>";

	print "<div id='explore_pages-tree'>";
	explore_pages_make_tree($parent);
	print "</div>";
	
	
	print "</div>";
}

function explore_pages_make_tree($parent) {
	global $wpdb;
	$pages = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE `post_type`='page' AND `post_parent`='".$parent."' ORDER BY `ID` ASC;");
	if ($pages) {
		
		$opened = array();
		
		if (isset($_GET['opento'])) {
			$opento = intval($_GET['opento']);
			$tree = $opento.explore_pages_opentree($opento);
			if (preg_match("/\,/",$tree)) { 
				$opened = explode(",",$tree);
			} else {
				$opened = array($opento);
			}
		}
	
		print "<ul>";
		foreach($pages as $p) {
			$open = (in_array($p->ID, $opened)) ? " class='open'" : "";
			print "<li id='page-".$p->ID."' rel='$p->ID'$open><a href='#' rel='".get_permalink($p->ID)."'>".apply_filters('the_title',$p->post_title)."</a>";
			explore_pages_make_tree($p->ID);
			print "</li>\n";
		}
		print "</ul>";
	}
}

function explore_pages_opentree($id, $string = "") {
	global $wpdb;
	
	$parent = $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE `ID`='$id' LIMIT 1;");
	if ($parent != 0) {
		$string .= ",".$parent;
		$string = explore_pages_opentree($parent, $string);
	}
	
	return $string;
}

?>