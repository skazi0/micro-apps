<?php
$pw = explode(':', file_get_contents('/etc/nginx/statspasswd'));
$db = pg_connect("host=$pw[0] dbname=stats user=$pw[1] password=$pw[2]") or die('Could not connect: ' . pg_last_error());

$json = file_get_contents('php://input');
$post = json_decode($json, true);
// TODO: handle json decode errors

// write data
if ($post !== null) {
    $table = pg_escape_identifier($_GET['table']);
    // make sure requested table exists
    $res = pg_query("CREATE TABLE IF NOT EXISTS $table (time TIMESTAMP WITH TIME ZONE NOT NULL)") or die('Query failed: ' . pg_last_error());
    pg_free_result($res);
    // default to now
    $ts = $post['time'] ?? time();

    foreach ($post as $column => $value) {
        if ($column == 'time') continue;
        // avoid SQL injections
        $column = pg_escape_identifier($column);
        $value = pg_escape_literal($value);
        // basic value type detection
        $type = is_numeric($value) ? 'DOUBLE PRECISION' : 'CHARACTER VARYING';

        // make sure requested column exists
        $res = pg_query("ALTER TABLE $table ADD COLUMN IF NOT EXISTS $column $type") or die('Query failed: ' . pg_last_error());
        pg_free_result($res);

        // try updating first (if requested time row exists)
        $res = pg_query("UPDATE $table SET $column=$value WHERE time=to_timestamp($ts)") or die('Query failed: ' . pg_last_error());
        $updated = (pg_affected_rows($res) > 0);
        pg_free_result($res);
        // insert if update didn't do anything
        if (!$updated) {
            $res = pg_query("INSERT INTO $table (time, $column) VALUES (to_timestamp($ts), $value)") or die('Query failed: ' . pg_last_error());
            pg_free_result($res);
        }
    }
    print "OK";
}

pg_close($db);
