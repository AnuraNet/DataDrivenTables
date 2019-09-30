function setupFilterable(tableId) {
    var table = document.getElementById(tableId);
    var filters = document.querySelector(".tableFilters[data-id='" + tableId+"']");
    var filters_input = filters.querySelectorAll("input");
    var filters_select = filters.querySelectorAll("select");
    for (var i = 0; i < filters_input.length; i++) {
        filters_input[i].addEventListener("input", function () {
            table.dataset.page = 1;
            var obj = JSON.parse(table.dataset.additionalParameters);
            if (this.type === "checkbox") {
                var parent = this.parentElement;
                var boxes = Array();
                parent.querySelectorAll("input[type='checkbox']").forEach(function (input) {
                    boxes.push(input.checked);
                });
                obj["tableFilter-" + parent.dataset.id] = JSON.stringify(boxes);
            } else {
                obj["tableFilter-" + this.dataset.id] = this.value;
            }
            table.dataset.additionalParameters = JSON.stringify(obj);
            updateTable(table.id);
        });
    }
    for (var i = 0; i < filters_select.length; i++) {
        filters_select[i].addEventListener("change", function () {
            table.dataset.page = 1;
            var obj = JSON.parse(table.dataset.additionalParameters);
            obj["tableFilter-" + this.dataset.id] = this.value;
            table.dataset.additionalParameters = JSON.stringify(obj);
            updateTable(table.id);
        });
    }
}
