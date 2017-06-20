<?php
/* stuff here! hooray */
define('THIS_PAGE', basename($_SERVER['PHP_SELF']));
#define('BASE_URL', 'http://localhost:8019/test/');
define('BASE_URL', 'http://nethack.raech.net/');
#define('SCRIPT_PATH', '/var/www/html/test/');
define('SCRIPT_PATH', '/srv/www/nethack/nhdpscript/');
define('SCRIPT_NAME', 'dizzyprice.py');

if (!isset($_SESSION)) {
    session_start();
}

/*
 * Only do this if loading with _POST
 * Rewrite; if $_SESSION variable not set, set to default instead. Otherwise leave it alone.
 */
if (!empty($_POST)) { /* $_POST received; set new session vars and defaults for vars not received */
    if (isset($_POST['objType'])) {
	$_SESSION['objType'] = $_POST['objType'];
    } else { $_SESSION['objType'] = 'potions'; }

    if (isset($_POST['charisma'])) {
	$_SESSION['charisma'] = (int)$_POST['charisma'];
    } else { $_SESSION['charisma']  = 10; }

    if (isset($_POST['buymode'])) {
	$_SESSION['buymode'] = $_POST['buymode'];
    } else { $_SESSION['buymode'] = 'buying'; }

    if (isset($_POST['dunce'])) {
	$_SESSION['dunce'] = true;
    } else { $_SESSION['dunce'] = false; }

    if (isset($_POST['tourist'])) {
	$_SESSION['tourist'] = true;
    } else { $_SESSION['tourist'] = false; }

    ########
    ######## Update this to quietly change uppercase letters to lowercase
    ######## then allow [A-Z][a-z] in desc box
    ########

    if (isset($_POST['desc'])) {
	/* fail if unacceptable letters are present? just "blanks" desc field? */
	$_SESSION['desc'] = testInput($_POST['desc'], true);
    } else { $_SESSION['desc'] = ''; } # REMOVE ME
    if (isset($_POST['price'])) {

	$_SESSION['price'] = testInput($_POST['price'], false);
    } else { $_SESSION['price'] = ''; } # REMOVE ME

    $_SESSION['query'] = formatQuery();
#    exec(sprintf('python3 %s%s %s', SCRIPT_PATH, SCRIPT_NAME, $_SESSION['query']),
#	 $output, $return_code);
    $_SESSION['output'] = shell_exec('nohup ' . SCRIPT_PATH . SCRIPT_NAME . $_SESSION['query']);
    header( 'Location: ' . BASE_URL . THIS_PAGE );
    die();
} else { /* $_POST is empty; Good, set defaults IF session variables not present */
    if (!isset($_SESSION['objType'])) {
	$_SESSION['objType'] = 'potions'; }
    if (!isset($_SESSION['charisma'])) {
	$_SESSION['charisma'] = 10; }
    if (!isset($_SESSION['buymode'])) {
	$_SESSION['buymode'] = 'buying'; }
    if (!isset($_SESSION['dunce'])) {
	$_SESSION['dunce'] = false; }
    if (!isset($_SESSION['tourist'])) {
	$_SESSION['tourist'] = false; }
    if (!isset($_SESSION['desc'])) { # Should check if it's a fresh load or something
	$_SESSION['desc'] = ''; }
    if (!isset($_SESSION['price'])) {
	$_SESSION['price'] = ''; }
    $_SESSION['query'] = formatQuery();
} /* Then continue on below as if everything is normal :) */

// This is a DUMB use of bool :-/
function testInput($input, $isDesc) {
    $input = trim($input);
    if ($isDesc) {
	$input = strtolower($input);
	$pattern = '/^[a-z -]*$/'; // can be empty
	if (preg_match($pattern, $input) === 1) {
	    return htmlspecialchars($input); // superfluous?
	} else {
	    return '';
	}
    } else { // is price
	if (!is_numeric($input)) {
	    return '0';
	}
#	/* set price to 0 if fucktards put letters in here somehow? */
#	$pattern = "^[0-9]+$";
#	if (preg_match($pattern, $input)) {
#	    return htmlspecialchars($input);
#	} else {
#	    return '0';
#	}
    }
    return htmlspecialchars($input);
}

function formatQuery()
{
    $query = ' --html ';

    if ($_SESSION['dunce']) { $query .= '--dunce '; }
    if ($_SESSION['tourist']) { $query .= '--tourist '; }

    if ($_SESSION['buymode'] == 'buying') {
	$query .= '--buying ';
    } else {
	$query .= '--selling ';
    }

    $query .= '--charisma ' . $_SESSION['charisma'] . ' ';
    $query .= $_SESSION['objType'] . ' ';
    $query .= $_SESSION['desc'] . ' ';
    $query .= $_SESSION['price'];

    return $query;
}

/*
 * Everything below here is for _GET loads ONLY
 */

function showForm()
{
    $items['potions'] = '! - Potion';
    $items['scrolls'] = '? - Scroll';
    $items['wands'] = '/ - Wand';
    $items['rings'] = '= - Ring';
    $items['armor'] = '[ - Armor';
    $items['weapons'] = ') - Weapon';
    $items['amulets'] = '" - Amulet';
    $items['spellbooks'] = '+ - Spellbook';
    $items['tools'] = '( - Tool';
    $items['stones'] = '* - Gray stone';

    $buymode['buying'] = 'Buying';
    $buymode['selling'] = 'Selling';

    echo '
  <form action="' . THIS_PAGE . '" method="post">
    <table>
      <tr>
	<th>Charisma</th>
	<td colspan="2">
	  <select name="charisma">';
    for ($i = 3; $i <= 19; $i++) {
	$line = '<option value="' . $i . '"';
	if ($i == $_SESSION['charisma']) {
	    $line .= ' selected';
	}
	$line .= '>';
	if ($i == 19) {
	    $line .= '&gt; 18';
	} else {
	    $line .= $i;
	}
	$line .= '</option>';
	echo $line;
    }
    echo '
          </select>
	</td>
      </tr>
      <tr>
	<th>Item type</th>
	<td colspan="2">
	  <select id="objselect" onchange="toggleDesc()" name="objType">';
    foreach ($items as $key => $display) {
	$line = '<option value="' . $key . '"';
	if ($_SESSION['objType'] == $key) {
	    $line .= ' selected';
	}
	$line .= '>' . $display . '</option>';
	echo $line;
    }

    echo '
	  </select>
	</td>
      <tr>
	<th>Description</th>
	<td colspan="2">
	  <input id="descinput" type="text" pattern="[a-z -]*" name="desc"';
#    if (isset($_SESSION['desc'])) {
	echo ' value="' . $_SESSION['desc'] . '" ';
 #   }
    echo '/>
	</td>
      </tr>
<tr>
  <th>Price</th>
  <td colspan="2">
    <input type="text" required pattern="[0-9]+" name="price"';
    echo ' value="' . $_SESSION['price'] . '" ';
    echo '/>
  </td>
</tr>
      <tr>
	<th>Buy/Sell</th>
	<td colspan="2">
	  <select name="buymode">';
    foreach ($buymode as $key => $display) {
	$line = '<option value="' . $key . '"';
	if ($_SESSION['buymode'] == $key) {
	    $line .= ' selected';
	}
	$line .= '>' . $display . '</option>';
	echo $line;
    }
    echo '
	  </select>
	</td>
      </tr>
      <tr>
        <td>
          <input type="checkbox" name="dunce"';
    if ($_SESSION['dunce']) { echo ' checked'; }
    echo '>Dunce cap</input>
        </td>
        <td><input type="checkbox" name="tourist"';
    if ($_SESSION['tourist']) { echo ' checked'; }
    echo '>Tourist</input></td>
        <td></td>
      </tr>
      <tr>

	<td align="right" colspan="3">
	  <input type="submit" name="submit" value="Check price" />
	</td>
      </tr>
    </table>
  </form>
';
} /* End showForm() */
?>
<!doctype html>
<html>
    <head>
	<title>DizzyPrice NetHack Price ID</title>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" href="dizzystyle.css" />
	<script>
	 document.getElementById("objselect").onchange = function() { toggleDesc() };
	 function toggleDesc() {
	     var a = document.getElementById("objselect");
	     var b = document.getElementById("descinput");
	     if (a.selectedIndex == 4 || a.selectedIndex == 5) {
		 b.disabled = false;
	     } else {
		 b.value = '';
		 b.disabled = true;
	     }
	 }
	</script>
    </head>
    <body onload="toggleDesc()">
	<div id="program">
	    <h1>DizzyPrice NetHack 3.6.0 Price ID Tool <span class="beta">(beta)</span></h1>
	    <p class="banner">&ldquo;The shopkeeper's gaze confuses you!&rdquo;</p>
	    <?php
	    showForm();
#	    echo '<pre>Contents of _POST:
#';
#	    echo var_dump($_POST);
#	    echo '</pre>';
#	    if (isset($_SESSION['query'])) {
#		echo '<pre>Run string:
#';
#		echo 'dizzylizzy ' . $_SESSION['query'];
#		echo '</pre>';
#	    }
#	    echo '<pre>Contents of _SESSION:
#';
#	    echo var_dump($_SESSION);
#	    echo '</pre>';
	    if (isset($_SESSION['output'])) {
		echo '<pre>' . $_SESSION['output'] . '</pre>';
	    }
	    ?>
	</div> <!-- /program -->
	<div id="about">
	    <h2>About DizzyPrice</h2>

	    <p>DizzyPrice is a NetHack price identification tool. Using some
		information about your character and the item you're trying to
		sell or buy in a shop, you can narrow down what the item
		might  be.</p>
	    <p>Enter your character's <strong>charisma</strong> score, the
		<strong>item type</strong>, the <strong>price</strong> given
		by the shop, and whether you are <strong>buying</strong> or
		<strong>selling</strong>, and get a list of the items that
		match your input. If you're playing a <strong>tourist</strong>
		whose level is less than 15, or are wearing a
		<strong>dunce cap</strong>, be sure to check those boxes.</p>
	    <p>The <strong>description</strong> field is
		special. Enter only letters and spaces here. The
		values you send will filter the results based on the
		items' names and descriptions. For example, if you're
		trying to identify a pair of "buckled boots", enter
		"buckled boots" into this box and only get items that
		could match that description. Note that this box is
		currently only available for weapons and armor.</p>
	    <p>DizzyPrice is free software licensed under the AGPLv3+,
		and was written by Aubrey Raech (<a href="https://alt.org/nethack/plr.php?player=dizzylizzy">dizzylizzy</a>). If
		you want to download the Python script yourself, visit
		its <a href="https://github.com/araech/dizzyprice">git repository</a>.</p>
	    <p>This is very much not done and very much still in
		development. If you find any bugs, please email me: aubrey at
		raech dot net</p>
        </div>
    </body>
</html>
