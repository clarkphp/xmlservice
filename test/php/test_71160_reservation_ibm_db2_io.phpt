--TEST--
XML i Toolkit: IBM_DB2 inout multi reservation processing
--SKIPIF--
<?php require_once('skipifdb2.inc'); ?>
--FILE--
<?php
// see connection.inc param details ...
require_once('connection.inc');
// call IBM i
if ($i5persistentconnect) $conn = db2_pconnect($database,$user,$password);
else $conn = db2_connect($database,$user,$password);
if (!$conn) die("Bad connect: $database,$user");
for ($i=0;$i<4;$i++) {
$stmt = db2_prepare($conn, "call $procLib.iPLUG5M(?,?,?,?)");
if (!$stmt) die("Bad prepare: ".db2_stmt_errormsg());
echo "=========================\n";
switch($i) {
  case 0:
    // $ctlstart = $ctl;
    // $ctl .= " *debugproc";
    echo "INPUT getxml0 (wrkactjob): good exclusive key IPC ...";
    $clobIn = getxml0();
    break;
  case 1:
    // $ctl = $ctlstart;
    echo "INPUT getxml1 (ls /tmp): bad wrong key IPC ...";
    $clobIn = getxml1();
    break;
  case 2:
    echo "INPUT CALL getxml2 (ZZCALL): good exclusive key IPC stop ...";
    $clobIn = getxml2();
    break;
  case 3:
    echo "INPUT getxml3 (ls /tmp): free key IPC ...";
    $clobIn = getxml3();
    break;
  default:
    die("OUTPUT (empty): nothing ...");
    break;
}
echo "ctl = $ctl\n";
$clobOut = "";
$ret=db2_bind_param($stmt, 1, "ipc", DB2_PARAM_IN);
$ret=db2_bind_param($stmt, 2, "ctl", DB2_PARAM_IN);
$ret=db2_bind_param($stmt, 3, "clobIn", DB2_PARAM_IN);
$ret=db2_bind_param($stmt, 4, "clobOut", DB2_PARAM_OUT);
$ret=db2_execute($stmt);
if (!$ret) die("Bad execute: ".db2_stmt_errormsg());
// ------------------
// output processing
// ------------------
echo "\n";
switch($i) {
  case 0:
    echo "OUTPUT getxml0 (wrkactjob): good exclusive key IPC ...";
    var_dump($clobOut);
    if (strpos($clobOut,"Work with Active Jobs")<1) die("Missing Work with Active Jobs");
    break;
  case 1:
    echo "OUTPUT getxml1 (ls /tmp): bad wrong key IPC ...";
    var_dump($clobOut);
    if (strpos($clobOut,"1301060")<1) die("Missing busy 1301060");
    break;
  case 2:
    echo "OUTPUT CALL getxml2 (ZZCALL): good exclusive key IPC stop ...";
    var_dump($clobOut);
    if (strpos($clobOut,"4444444444.44")<1) die("Missing ZZCALL 4444444444.44");
    break;
  case 3:
    echo "OUTPUT getxml3 (ls /tmp): good free key IPC ...";
    var_dump($clobOut);
    if (strpos($clobOut,"zsemfile")<1) die("Missing ls /tmp/zsemfile");
    break;
  default:
    break;
}
echo "\n";
}
// good
echo "Success\n";

function getxml0() {
$clob = <<<ENDPROC
<?xml version='1.0'?>
<script>
<start>myspecialkey</start>
<sh rows='on'>/QOpenSys/usr/bin/system -i 'wrkactjob'</sh>
</script>
ENDPROC;
return $clob;
}

function getxml1() {
$clob = <<<ENDPROC
<?xml version='1.0'?>
<script>
<use>mywrongkey</use>
<sh>/QOpenSys/usr/bin/ls /tmp</sh>
</script>
ENDPROC;
return $clob;
}

function getxml2() {
$clob = <<<ENDPROC
<?xml version='1.0'?>
<script>
<use>myspecialkey</use>
<pgm name='ZZCALL' lib='xyzlibxmlservicexyz'>
 <parm  io='both'>
   <data type='1A' var='INCHARA'>a</data>
 </parm>
 <parm  io='both'>
   <data type='1A' var='INCHARB'>b</data>
 </parm>
 <parm  io='both'>
   <data type='7p4' var='INDEC1'>11.1111</data>
 </parm>
 <parm  io='both'>
   <data type='12p2' var='INDEC2'>222.22</data>
 </parm>
 <parm  io='both'>
  <ds>
   <data type='1A' var='INDS1.DSCHARA'>x</data>
   <data type='1A' var='INDS1.DSCHARB'>y</data>
   <data type='7p4' var='INDS1.DSDEC1'>66.6666</data>
   <data type='12p2' var='INDS1.DSDEC2'>77777.77</data>
  </ds>
 </parm>
 <return>
  <data type='10i0'>0</data>
 </return>
</pgm>
<stop>myspecialkey</stop>
</script>
ENDPROC;
return test_lib_replace($clob);
}

function getxml3() {
$clob = <<<ENDPROC
<?xml version='1.0'?>
<script>
<sh>/QOpenSys/usr/bin/ls /tmp</sh>
</script>
ENDPROC;
return $clob;
}

?>

--EXPECTF--
%s
Success

