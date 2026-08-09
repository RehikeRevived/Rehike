<?php ?>
<script>

function updateRehikeConfig(obj)
{
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "/rehike/update_config");
    xhr.onload = function() {
        if (200 == xhr.status)
            window.location.reload();
    };
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.send(JSON.stringify(obj));
}

window.fatalDisableRehikeOnce = function() {
    var currentUrl = window.location.href;

    var urlParser = new URL(currentUrl);
    urlParser.searchParams.set("enable_polymer", "1");
    
    window.location.href = urlParser.toString();
};

window.fatalDisableRehike = function() {
    updateRehikeConfig({
        "hidden.disableRehike": true
    });
};

window.openChangeDns = function() {
    var containerEl = document.getElementById("change-dns-container");
    if (containerEl)
    {
        containerEl.classList.toggle("hid");
    }
};

window.saveDns = function() {
    var inputEl = document.querySelector("#change-dns-container input");
    if (inputEl)
    {
        updateRehikeConfig({
            "advanced.dnsAddress": inputEl.value
        });
    }
};

</script>