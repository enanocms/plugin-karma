<?php
/**!info**
{
  "Plugin Name"  : "Karma",
  "Plugin URI"   : "http://enanocms.org/plugin/karma",
  "Description"  : "Karma is a plugin that enables in the user page a voting system, to evaluate the popularity of each member.",
  "Author"       : "Adriano Pereira",
  "Version"      : "1.0",
  "Author URI"   : "http://enanocms.org/"
}
**!*/

$plugins->attachHook('userpage_sidebar_left',  'karma();');

function karma()
{
  // Importing...
  global $db, $session, $paths;
  
  if($session->user_logged_in)
  {  

  // If the user votes, get the vote
  $vote = !empty($_GET['vote']) && in_array($_GET['vote'], array('Yes', 'No'))
            ? $_GET['vote']
            : null;
  
  // Get the user_id from the user that is voting
  $user_voting_id = $session->user_id;
  
  // Find the page_id that is the username of the current user page and gets the user_id from database
  $username = str_replace('_', ' ', dirtify_page_id($paths->page_id));
  
  $q = $db->sql_query('SELECT user_id FROM '. table_prefix. "users WHERE username = '$username'");
  if ( !$q )
    $db->_die();
  $voted = $db->fetchrow();
  $user_voted_id = $voted['user_id'];
  
  // Retrieves from database the total votes, yes votes, no votes and the karma from user
  $q = $db->sql_query('SELECT karma_yes_votes, karma_no_votes, (karma_yes_votes + karma_no_votes) AS karma_total_votes, (karma_yes_votes - karma_no_votes) AS karma FROM '. table_prefix."users_extra WHERE user_id = '$user_voted_id'");
  if ( !$q )
    $db->_die();
  $karma_info = $db->fetchrow();
  $total_votes = $karma_info['karma_total_votes'];
  $yes_votes = $karma_info['karma_yes_votes'];
  $no_votes = $karma_info['karma_no_votes'];
  $karma = $karma_info['karma'];
  
  // Search in the database if the user has already voted in this user
  $q = $db->sql_query('SELECT user_voted_id, user_voting_id FROM '. table_prefix."karma WHERE user_voted_id = '$user_voted_id' AND user_voting_id = '$user_voting_id'");
  if ( !$q )
    $db->_die();
  $num_votes = $db->numrows();
  $db->free_result();
  
  // If the user that votes and the user voted id is equal or the user has already voted, displays the commom page
  
  // If we're on our own user page, block voting
  $same_user = $user_voting_id === $user_voted_id;
  
  // If we have not yet voted on this user, allow that to take place below
  $can_vote = $num_votes == 0 && !$same_user && $session->user_level >= USER_LEVEL_MEMBER;
  
  echo "<th colspan='4'>$username's karma</th>";
  
  $did_vote = false;
  if ( $can_vote )
  {
    // Know if the vote is yes or no and do the respective action in database
    $increment_col = !empty($vote) && $vote == 'Yes' ? 'karma_yes_votes' : 'karma_no_votes';
    if ( !empty($vote) )
    {
      $q = $db->sql_query('INSERT INTO '. table_prefix."karma (user_voting_id, user_voted_id) VALUES ('$user_voting_id', '$user_voted_id')");
      if ( !$q )
        $db->_die();
      $q = $db->sql_query('UPDATE '. table_prefix."users_extra SET $increment_col = $increment_col + 1");
        if ( !$q )
          $db->_die();
        
      if ( $vote == 'Yes' )
        $yes_votes++;
      else
        $no_votes++;
        
      // recalculate
      $karma = $yes_votes - $no_votes;
      $total_votes = $yes_votes + $no_votes;
      
      $did_vote = true;
    }
    else
    {
      // Label to commom page title
      echo <<<EOF
        <tr>
        <td colspan="4" class="row3" style="text-align: center;">
        <b>Do you like me?</b><br/>
        <form action="">
          <input type="submit" value="Yes" name="vote" style="background-color: #00CA00; border: 2px solid #000000; width: 40px; color: #FFFFFF; font-size: 14px; text-align:center;">
          <input type="submit" value="No" name="vote" style="background-color: #FA1205; border: 2px solid #000000; width: 40px; color: #FFFFFF; font-size: 14px; text-align:center;">
        </form>
      </tr>
EOF;
    }
  }
  
  // Label to commom page content and page content
  
  if ($karma < 0)
  {
	  $karma_color = '#FA1205';
  }
  elseif ($karma > 0)
  { 
    $karma_color = '#00CA00';
  }
  else
  {
    $karma_color = '#000000';
  }
?>
  <?php if ( $did_vote ): ?>
    <tr>
      <td colspan="4" class="row3">
        <div class="info-box-mini">Thanks for voting for this user's karma.</div>
      </td>
    </tr>
  <?php endif; ?>
    
	<tr>
	
		<td colspan="2" class="row1">
      Your Karma is: <span style="color: <?php echo $karma_color; ?>;"><?php echo $karma;?><br/></font>
		</td>
		
		<td colspan="2" class="row2">
			'Yes' votes: <?php echo $yes_votes;?><br/>
			'No' votes: <?php echo $no_votes;?><br/>
			Number of votes: <?php echo $total_votes;?><br/>			
		</td>
	</tr>
<?php
}
}

/**!install dbms="mysql"; **
CREATE TABLE {{TABLE_PREFIX}}karma(
  vote_id int(18) NOT NULL auto_increment,
  user_voting_id int(12),
  user_voted_id int(12),  
  PRIMARY KEY ( vote_id )
 ) ENGINE=`MyISAM` CHARSET=`UTF8` COLLATE=`utf8_bin`;
 
ALTER TABLE {{TABLE_PREFIX}}users_extra ADD COLUMN karma_yes_votes int(12) NOT NULL DEFAULT 0;
ALTER TABLE {{TABLE_PREFIX}}users_extra ADD COLUMN karma_no_votes int(12) NOT NULL DEFAULT 0;

**!*/

/**!uninstall **
DROP TABLE {{TABLE_PREFIX}}karma;
ALTER TABLE {{TABLE_PREFIX}}users_extra DROP karma_yes_votes;
ALTER TABLE {{TABLE_PREFIX}}users_extra DROP karma_no_votes;
**!*/


?>