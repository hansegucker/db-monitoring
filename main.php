<?php
function isCalled ($key_word) {
    if(isset($_GET[$key_word])) {
        return True;
    } else {
        return False;
    }
}
if (isCalled('main')){
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
