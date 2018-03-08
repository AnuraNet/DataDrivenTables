function exportTable(id, format) {
    var e = document.getElementById(id);
    var additionalParameters = "";
    var obj = JSON.parse(e.dataset.additionalParameters);
    for (var key in obj) {
        additionalParameters += "&" + key + "=" + obj[key];
    }
    document.location.href = e.dataset.contentPage + "?" + id + "&export=" + format + additionalParameters;
}

document.addEventListener("click", function (ev) {
    var thiz = ev.target;
    if (thiz.tagName.toLowerCase() === "a" && thiz.parentElement.classList.contains("tableExport")) {
        exportTable(thiz.parentElement.dataset.id, thiz.dataset.export);
    }
});
