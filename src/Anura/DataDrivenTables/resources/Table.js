var timestamps = Array;

function updateTable(id, pagenr) {
    var timestamp = Date.now();
    timestamps[id] = timestamp;
    var e = document.getElementById(id);
    if (typeof pagenr === "undefined") {
        pagenr = e.dataset.page;
    }
    var additionalParameters = "";
    var obj = JSON.parse(e.dataset.additionalParameters);
    for (var key in obj) {
        additionalParameters += "&" + key + "=" + obj[key];
    }
    tableAjax(e.dataset.contentPage + "?" + id + "&tablePage=" + pagenr + additionalParameters).then((data) => {
        if (timestamps[id] === timestamp) {
            e.getElementsByTagName('tbody')[0].innerHTML = data.html;
            updateSwitcher(id, parseInt(pagenr), parseInt(data.pages));
            e.dataset.page = pagenr;
        }
    }, () => {});
}

function updateSwitcher(id, page, pages) {
    var html = "";
    if (pages > 1) {
        if (page - 1 > 0) {
            html += "<a data-page='1'>&lt;&lt;</a>&nbsp;<a data-page='" + (page - 1) + "' class='tablePageLink'>&lt; Vorherige Seite</a>&nbsp;|";
        }
        for (var i = page - 5; i <= page + 5; i++) {
            if (i > 0 && i <= pages) {
                if (i === page) {
                    html += "&nbsp;" + i + "&nbsp;";
                } else {
                    html += "&nbsp;<a data-page='" + i + "' class='tablePageLink'>" + i + "</a>&nbsp;";
                }
            }
        }
        if (page + 1 <= pages) {
            html += "|&nbsp;<a data-page='" + (page + 1) + "' class='tablePageLink'>Nächste Seite &gt;</a>&nbsp;<a data-page='" + pages + "' class='tablePageLink'>&gt;&gt;</a>";
        }
        html += "<br/>" + pages + " Seiten";
    }
    document.querySelector(".tableSwitcher[data-id='" + id + "']").innerHTML = html;
}

document.addEventListener("click", function(ev) {
    var thiz = ev.target;
    if (thiz.tagName.toLowerCase() === "a" && thiz.parentElement.classList.contains("tableSwitcher")) {
        updateTable(thiz.parentElement.dataset.id, thiz.dataset.page);
    }
});

function tableAjax(url, method = "GET", data = "", tryJson = true) {
    return new Promise((resolve, reject) => {
        var httpRequest = new XMLHttpRequest();
        httpRequest.onreadystatechange = function() {
            if (httpRequest.readyState === XMLHttpRequest.DONE) {
                if (httpRequest.status === 200) {
                    var response = httpRequest.responseText;
                    if (tryJson) {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {}
                    }
                    resolve(response);
                } else {
                    reject();
                }
            }
        };
        httpRequest.open(method, url, true);
        if (method === "POST") {
            httpRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            httpRequest.send(data);
        } else {
            httpRequest.send();
        }
    });
}
