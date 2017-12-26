<?php

include 'api.php';

function isCalled($key_word)
{
    if (isset($_GET[$key_word])) {
        return True;
    } else {
        return False;
    }
}

if (isCalled('main')) {
    echo <<<HEREDOC
        <table class="table">
            <thead>
                <tr>
                    <th>Abfahrt um</th>
                    <th>Zugnummer</th>
                    <th>Zwischenhalte</th>
                    <th>Ziel</th>
                    <th>Gleis</th>
                    <th>Aktuelles</th>
                </tr>
            </thead>
            <tr>
                <td class='db-time'>
                    19:30 → 20:00
                </td>
                <td class='db-train-key'>
                    RB 8181
                </td>
                <td class='db-stops'>
                    Reinfeld – Bad Oldesloe – Ahrensburg
                </td>
                <td class='db-destination'>
                    Hamburg Hbf
                </td>
                <td class='db-platform'>
                    9
                </td>
            </tr>
        
HEREDOC;

    $db = new DB('7e75089d7fc7f1076621bdc63f2b66f4');
    $station = $db->getStation('Hamburg Hbf');
    $station_id = $station['id'];
    $timetable = $db->getTimetable($station_id);

    foreach ($timetable as $trip) {
        if (isset($trip['departure'])) {
            $dp = $trip['departure'];
            echo '<tr>';

            // Time
            echo '<td class="db-time">';
            if (isset($dp['changed']['time']) && $dp['changed']['time'] != $dp['planned']['time']) {
                echo '<span class="db-old">';
                echo $dp['planned']['time'];
                echo '</span> → ';
                echo $dp['changed']['time'];
            } else {
                echo $dp['planned']['time'];
            }
            echo '</td>';

            echo '<td class="db-train-key">';
            echo $trip['train']['class'] . ' ' . $trip['train']['number'];
            echo '</td>';

            echo '<td class="db-stops">';
            if (isset($dp['changed']['route']) && $dp['changed']['route'] != $dp['planned']['route']) {
                echo '<span class="db-new">';
                $destination = array_pop($dp['changed']['route']);
                echo implode(' – ', $dp['changed']['route']);
                echo '</span> → ';
            } else {
                $destination = array_pop($dp['planned']['route']);
                echo implode(' – ', $dp['planned']['route']);
            }
            echo '</td>';

            echo '<td class="db-destination">';
            echo $destination;
            echo '</td>';


            //debug($trip);
            echo '</tr>';
        }

    }

    echo <<<HEREDOC
</table>
HEREDOC;


} else if (isCalled('header')) {
    echo <<<HEREDOC
        <div class="page-header">
            <h1>Abfahrten für Lübeck Hbf </h1>
        </div>
HEREDOC;
} else if (isCalled('footer')) {
    echo <<<HEREDOC
        Letztes Update am um 
HEREDOC;
}
