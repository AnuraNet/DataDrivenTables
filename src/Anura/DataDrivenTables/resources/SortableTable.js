function setupSortable(table, sortables) {
    var data_id = table.querySelectorAll("th[data-id]");
    for (var i = 0; i < data_id.length; i++) {
        var thiz = data_id[i];
        if (sortables.indexOf(thiz.dataset.id) !== -1) {
            thiz.addEventListener("click", function () {
                var table = document.getElementById(this.dataset.table);
                var obj = JSON.parse(table.dataset.additionalParameters);
                var data_id_div = table.querySelectorAll("th[data-id] div");
                for (var j = 0; j < data_id_div.length; j++) {
                    data_id_div[j].className = "";
                }
                if (obj.tableSortBy === this.dataset.id) {
                    if (obj.tableSortDir === "DESC") {
                        obj.tableSortDir = "ASC";
                        this.querySelector("div").classList.add("arrowUp");
                    } else {
                        obj.tableSortDir = "DESC";
                        this.querySelector("div").classList.add("arrowDown");
                    }
                } else {
                    obj.tableSortBy = this.dataset.id;
                    obj.tableSortDir = "ASC";
                    this.querySelector("div").classList.add("arrowUp");
                }
                table.dataset.additionalParameters = JSON.stringify(obj);
                updateTable(table.id);
            });
            thiz.dataset.table = table.id;
            thiz.classList.add("clickCursor");
            var obj = JSON.parse(table.dataset.additionalParameters);
            if (obj.tableSortBy === thiz.dataset.id) {
                if (obj.tableSortDir === "ASC") {
                    thiz.querySelector("div").classList.add("arrowUp");
                } else {
                    thiz.querySelector("div").classList.add("arrowDown");
                }
            }
        }
    }
}
