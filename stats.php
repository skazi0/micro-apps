<?php
$pw = explode(':', file_get_contents('/etc/nginx/statspasswd'));
$db = pg_connect("host=$pw[0] dbname=stats user=$pw[1] password=$pw[2]") or die('Could not connect: ' . pg_last_error());

$json = file_get_contents('php://input');
$post = json_decode($json, true);
// TODO: handle json decode errors

function value_type($value) {
    return is_numeric($value) ? 'DOUBLE PRECISION' : 'CHARACTER VARYING';
}

function ensure_column($table, $column, $type) {
    $res = pg_query("ALTER TABLE $table ADD COLUMN IF NOT EXISTS $column $type") or die('Query failed: ' . pg_last_error());
    pg_free_result($res);
}

// write data
if ($post !== null) {
    $table = pg_escape_identifier($_GET['table']);
    // make sure requested table exists
    $res = pg_query("CREATE TABLE IF NOT EXISTS $table (time TIMESTAMP WITH TIME ZONE NOT NULL)") or die('Query failed: ' . pg_last_error());
    pg_free_result($res);
    // default to now
    $ts = $post['time'] ?? time();

    // extract tags
    $tags = $post['tags'] ?? array();
    unset($post['tags']);
    // make tags query
    $tags_query = '';
    $tags_cols = '';
    $tags_vals = '';
    if (!empty($tags)) {
        foreach ($tags as $tag => $value) {
            // make sure tag column exists
            ensure_column($table, $tag, value_type($value));
            // avoid SQL injections
            $tag = pg_escape_identifier($tag);
            $value = pg_escape_literal($value);

            $tags_query .= " AND $tag=$value";
            $tags_cols .= ", $tag";
            $tags_vals .= ", $value";
        }
    }

    foreach ($post as $column => $value) {
        if ($column == 'time') continue;
        // make sure requested column exists
        ensure_column($table, $column, value_type($value));
        // avoid SQL injections
        $column = pg_escape_identifier($column);
        $value = pg_escape_literal($value);

        // try updating first (if requested time row exists)
        $res = pg_query("UPDATE $table SET $column=$value WHERE time=to_timestamp($ts) $tags_query") or die('Query failed: ' . pg_last_error());
        $updated = (pg_affected_rows($res) > 0);
        pg_free_result($res);
        // insert if update didn't do anything
        if (!$updated) {
            $res = pg_query("INSERT INTO $table (time, $column $tags_cols) VALUES (to_timestamp($ts), $value $tags_vals)") or die('Query failed: ' . pg_last_error());
            pg_free_result($res);
        }
    }
    print "OK";
}

pg_close($db);
