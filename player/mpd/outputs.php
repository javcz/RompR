<?php
require_once ("player/".$prefs['player_backend']."/player.php");
$outputdata = array();
$player = new $PLAYER_TYPE();
if ($player->is_connected()) {
    $outputs = $player->get_outputs();
    foreach ($outputs as $i => $n) {
        if (is_array($n)) {
            foreach ($n as $a => $b) {
                logger::trace("AUDIO OUTPUT", $i,"-",$b.":".$a);
                $outputdata[$a][$i] = $b;
            }
        } else {
            logger::trace("AUDIO OUTPUT", $i,"-",$n);
            $outputdata[0][$i] = $n;
        }
    }
}
$player = null;

function printOutputCheckboxes() {
    global $outputdata;
    for ($i = 0; $i < count($outputdata); $i++) {
        print '<div class="styledinputs">';
        print '<input type="checkbox" id="outputbutton_'.$i.'"';
        if ($outputdata[$i]['outputenabled'] == 1) {
            print ' checked';
        }
        print '><label for="outputbutton_'.$i.'" onclick="player.controller.doOutput('.$i.')">'.
            $outputdata[$i]['outputname'].'</label>';
        print '</div>';
    }
}

?>
