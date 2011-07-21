<?php

/*
Plugin Name: WizCom
Plugin URI: http://www.wizcodersolution.com/
Description: Send out emails to commentor
Version: 1.0
Author: Bob
Author URI: http://www.wizcodersolution.com/
*/

/*  Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : Bob@wizcodersolution.com)
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
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function wizUrl($path, $qs = false, $qsAdd = false)
{
    $var_array = array();
    $varAdd_array = array();
    $url = $path;

    if($qsAdd)
    {
        $varAdd = explode('&', $qsAdd);
        foreach($varAdd as $varOne)
        {
            $name_value = explode('=', $varOne);

            $varAdd_array[$name_value[0]] = $name_value[1];
        }
    }

    if($qs)
    {
        $var = explode('&', $qs);
        foreach($var as $varOne)
        {
            $name_value = explode('=', $varOne);

            //remove duplicated vars
            if($qsAdd)
            {
                if(!array_key_exists($name_value[0], $varAdd_array))
                {
                    $var_array[$name_value[0]] = $name_value[1];
                }
            }
            else
            {
                $var_array[$name_value[0]] = $name_value[1];
            }
        }
    }

    //make url with querystring
    $delimiter = "?";

    foreach($var_array as $key => $value)
    {
        $url .= $delimiter.$key."=".$value;
        $delimiter = "&";
    }

    foreach($varAdd_array as $key => $value)
    {
        $url .= $delimiter.$key."=".$value;
        $delimiter = "&";
    }

    return $url;
}


function email_commentors($post_ID)  {
	global $wpdb;
    $comment = $wpdb->get_results("SELECT comment_author, comment_author_email  FROM $wpdb->comments WHERE comment_author_email is not null group  by comment_author, comment_author_email  order by comment_author, comment_author_email ");
	if ( !$comment )
		return;
	foreach ( $comment as $key )
	{
		if (isset($key->comment_author_email)&&!EMPTY($key->comment_author_email))
			send_out_mail($post_ID, $key->comment_author_email, $key->comment_author) ;
	}
    return $post_ID;
}

function send_out_mail($post_ID, $email, $fname)  {
	  $opt_message = get_option( 'opt_WizCOM_Message' );
	  $opt_subject = get_option( 'opt_WizCOM_Subject' );
	$opt_message=str_replace('$fname', $fname, $opt_message);
	$opt_subject=str_replace('$fname', $fname, $opt_subject);

    wp_mail($email, $opt_subject, $opt_message);

    return $post_ID;
}



function get_all_commentors_emails()
{
	global $wpdb;

	echo '<h3>EMAIL List</h3>';

   $comment = $wpdb->get_results("SELECT comment_author, comment_author_email  FROM $wpdb->comments WHERE comment_author_email is not null group  by comment_author, comment_author_email  order by comment_author, comment_author_email ");
	if ( !$comment )
		return;
	echo '<table class="widefat post fixed" cellspacing="0">';
	echo '<thead>
	<tr>
	<th scope="col" id="author" class="manage-column column-author" style="">Author</th>
	<th scope="col" id="categories" class="manage-column column-categories" style="">Email</th>
	</tr>
	</thead>';

	echo '<tbody>';
	foreach ( $comment as $key )
		 if (isset($key->comment_author_email)&&!EMPTY($key->comment_author_email))
			echo "<tr><td class='author column-author'>$key->comment_author</td><td class='author column-author'>$key->comment_author_email</td></tr>";
	echo '</tbody></table>';
	echo '<hr />';

}


//add menu page in settings

add_action('admin_menu', 'WizCOM_menu');
function WizCOM_menu() {
  add_options_page('WizCOM Options', 'WizCOM', 8, 'wizcom', 'WizCOM_options');

}

function WizCOM_options() {

	//add a variable to store values
	add_option('opt_WizCOM_Message', '');
	add_option('opt_WizCOM_Subject', '');

	$hidden_field_name = 'mt_submit_hidden';
	echo '<div class="wrap">';
	echo "<h2>" . __( 'WizCOM Options', 'WizCOM_Message' ) . "</h2>";

	$msg_uri=wizUrl('options-general.php', $_SERVER['QUERY_STRING'], 'page=wizcom&show=msg');
	$email_uri=wizUrl('options-general.php', $_SERVER['QUERY_STRING'], 'page=wizcom&show=email');
	echo "<a href='$msg_uri'>Message Management</a>" ."|"."<a href='$email_uri'>Email List</a>";

	echo "<hr />";

	if (isset($_GET['show']) && $_GET['show']=='email')
	{
		get_all_commentors_emails();
	}
	else
	{
		echo '<h3>Message Template</h3>';
		if(isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' )
		{
			// Read their posted value
			$opt_message = $_POST['msg'];
			$opt_subject = $_POST['subject'];

			// Save the posted value in the database
			update_option( 'opt_WizCOM_Message', $opt_message );
			update_option( 'opt_WizCOM_Subject', $opt_subject );

			// Put an options updated message on the screen
?>
<div class="updated"><p><strong><?php _e('Message Updated.', 'WizCOM_Message' ); ?></strong></p></div>
<?php

	}
	else
	{
	// Read in existing option value from database
	  $opt_message = get_option( 'opt_WizCOM_Message' );
	  $opt_subject = get_option( 'opt_WizCOM_Subject' );
	  if (empty($opt_message))
		$opt_message ='e.g. I just put something on my blog: http://blog.example.com';
	  if (empty($opt_subject))
		$opt_subject ="e.g.Blog updated";
	}
?>
<form name="frm" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
<table class="form-table">
<tr>

	<th><label>Subject</label></th><td><input type="textbox"class="regular-text" name="subject" value="<?php echo $opt_subject; ?>" size="50"></td>
</tr>
<tr>
	<th><label>Message</label></th>
	<td><textarea name="msg" rows="20" cols="50" ><?php echo $opt_message; ?></textarea></td>
</tr>
<tr>
	<th><label></label></th>
	<td>Note: use $fname in place of commentors name</td>
</tr>



</table>
<p class="submit">
<input type="submit" class="button-primary" name="Submit" value="<?php _e('Update Message', 'WizCOM_Message' ) ?>" />
</p>
</form>
<hr />
<?
	  echo '</div>';
	}
}

add_action ( 'publish_post', 'email_commentors' );

?>

