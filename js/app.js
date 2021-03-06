$(document).ready(function () {
    main();
});

function loadMain () {
    $.get("main.php?main", function(data) {
        $("main").html(data);
        console.log('Loaded main.')
    });
}

function loaderMain () {
    window.setTimeout(loaderMain, 5000);
    loadMain();
}

function loadHeader () {
    $.get("main.php?header", function(data) {
        $("header").html(data);
        console.log('Loaded header.')
    });
}

function loaderHeader () {
    window.setTimeout(loaderHeader, 5000);
    loadHeader();
}

function loadFooter () {
    $.get("main.php?footer", function(data) {
        $("footer").html(data);
        console.log('Loaded footer.')
    });
}

function loaderFooter () {
    window.setTimeout(loaderFooter, 5000);
    loadFooter();
}

function main () {
    console.log('main() started.');
    loaderMain();
    loaderHeader();
    loaderFooter();
}