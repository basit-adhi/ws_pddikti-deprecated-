<?php
require_once ("config.inc.php");
$pasz = filter_input(INPUT_POST, "pass", FILTER_SANITIZE_STRING);
echo $pasz;
if ($pasz == "yourpassword")
{
    /* set the cache expire to 30 minutes */
    session_start();
    session_cache_expire(($pddikti["ws"]["expire"] / 60) + 1);
    $_SESSION["passthru"] = "leres";
    session_write_close();
    header("location: init.inc.php");
}
else
{
    echo '<div style="margin:auto;width:200px;padding:50"><form action="" method="post">
            <input type="password" id="pass" name="pass" /><br/><br/>
            <div style="margin:auto;width:100px"><input type="submit" value="Login"/></div>
          </form></div>';
}
